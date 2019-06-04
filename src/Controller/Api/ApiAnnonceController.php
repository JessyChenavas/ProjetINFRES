<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\Image;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/api", name="api_")
 */
class ApiAnnonceController extends ApiController
{
    /**
     * @Rest\Get("/annonces/{id}", name="afficher_annonce", requirements={"id" = "\d+"})
     *
     * @return Response
     */
    public function afficherAnnonce(Annonce $annonce)
    {
        $data =  $this->get('serializer')->serialize($annonce, 'json', $this->getSerializer()->serialize('annonce'));

        return new Response($data);
    }

    /**
     * @Rest\Get("/annonces", defaults={"page" = 1}, name="liste_annonces")
     * @Rest\Get("/annonces/page{page}", name="liste_annonces_pagine")
     *
     * @return Response
     */
    public function listeAnnonces($page)
    {
        $annonces = $this->getDoctrine()
            ->getRepository(Annonce::class)
            ->findAll();

        $paginatedCollection = $this->getPaginator()->paginate($annonces, $page, 10);
        $serialization = $this->getSerializer()->serialize('annonce', true);

        $data =  $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
    }

    /**
     *  @Rest\Post("/annonces", name="creer_annonce")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('ROLE_USER')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !")
     */
    public function creerAnnonce(Request $request) {
        $annonce = new Annonce();

        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        foreach ($data['images'] as $i) {
            $image = new Image();
            $image->setUrl($i['url']);
            $image->setAlt($i['alt']);

            $em->persist($image);
            $annonce->addImage($image);
        }

        $annonce->setCreateur($this->getUser())
            ->setDescription($data['description'])
            ->setPrix($data['prix'])
            ->setTitre($data['titre']);

        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => "L'annonce est enregistrée !"], 201);
    }

    /**
     *  @Rest\Put("/annonces/{id}", name="modifier_annonce")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     */
    public function modifierAnnonce(Request $request, Annonce $annonce) {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        $annonce->setDescription($data['description'])
            ->setPrix($data['prix'])
            ->setTitre($data['titre']);

        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => "L'annonce a été modifiée !"], 200);
    }

    /**
     *  @Rest\Post("/annonces/{id}/images", name="ajouter_image")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     */
    public function ajouterImage(Request $request, Annonce $annonce) {
        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        $image = $this->getDoctrine()
            ->getRepository(Image::class)
            ->findOneBy(['url' => $data['url'], 'alt' => $data['alt']]);

        if (!$image) {
            $image = new Image();
            $image->setAlt($data['alt']);
            $image->setUrl($data['url']);
        }

        $em->persist($image);
        $annonce->addImage($image);

        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => sprintf("L'image %s a été ajoutée ! ", $image->getAlt())], 200);
    }

    /**
     *  @Rest\Delete("/annonces/{annonce_id}/images/{image_id}", name="enlever_image")
     *
     *  @ParamConverter("annonce", options={"mapping": {"annonce_id": "id"}})
     *  @ParamConverter("image", options={"mapping": {"image_id": "id"}})
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     */
    public function supprimerImage(Annonce $annonce, Image $image) {
        $em = $this->getDoctrine()->getManager();
        $annonce->removeImage($image);
        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => sprintf("L'image %s a été enlevée !", $image->getAlt())], 200);
    }

    /**
     *  @Rest\Delete("/annonces/{id}", name="supprimer_annonce")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     */
    public function supprimerAnnonce(Annonce $annonce) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($annonce);
        $em->flush();

        return new JsonResponse(["success" => "L'annonce a été supprimée !"], 200);
    }
}