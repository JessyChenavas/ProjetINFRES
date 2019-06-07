<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\Image;
use App\Exception\ResourceValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api", name="api_")
 */
class ApiAnnonceController extends ApiController
{
    /**
     * @Rest\Get("/annonces/{id}", name="afficher_annonce", requirements={"id" = "\d+"})
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherAnnonce(Annonce $annonce = null)
    {
        if (!$annonce) {
            throw new ResourceValidationException('Annonce non existante !');
        }

        $data = $this->serializer->serialize($annonce, 'json');

        return new Response($data);
    }

    /**
     * @Rest\Get("/annonces", defaults={"page" = 1}, name="liste_annonces")
     * @Rest\Get("/annonces/page{page}", name="liste_annonces_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function listeAnnonces($page)
    {
        $annonces = $this->getDoctrine()
            ->getRepository(Annonce::class)
            ->findAll();

        if (!$annonces) {
            throw new ResourceValidationException('Aucune annonce trouvée !');
        }

        $paginatedCollection = $this->paginator->paginate($annonces, $page, 10);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        return new Response($data);
    }

    /**
     *  @Rest\Post("/annonces", name="creer_annonce")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('ROLE_USER')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !")
     */
    public function creerAnnonce(Request $request, ValidatorInterface $validator) {
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

        $listErrors = $validator->validate($annonce);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => "L'annonce est enregistrée !"], 201);
    }

    /**
     * @Rest\Put("/annonces/{id}", name="modifier_annonce")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function modifierAnnonce(Request $request, ValidatorInterface $validator, Annonce $annonce = null) {
        if (!$annonce) {
            throw new ResourceValidationException('Annonce non existante !');
        }

        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        $annonce->setDescription($data['description'])
            ->setPrix($data['prix'])
            ->setTitre($data['titre']);

        $listErrors = $validator->validate($annonce);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => "L'annonce a été modifiée !"], 200);
    }

    /**
     * @Rest\Post("/annonces/{id}/images", name="ajouter_image")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function ajouterImage(Request $request, ValidatorInterface $validator, Annonce $annonce = null) {
        if (!$annonce) {
            throw new ResourceValidationException('Annonce non existante !');
        }

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

        $listErrors = $validator->validate($annonce);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => sprintf("L'image %s a été ajoutée ! ", $image->getAlt())], 200);
    }

    /**
     * @Rest\Delete("/annonces/{annonce_id}/images/{image_id}", name="enlever_image")
     *
     * @ParamConverter("annonce", options={"mapping": {"annonce_id": "id"}})
     * @ParamConverter("image", options={"mapping": {"image_id": "id"}})
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function supprimerImage(Image $image = null, Annonce $annonce = null) {
        if (!$annonce) {
            throw new ResourceValidationException('Annonce non existante !');
        }

        if (!$image) {
            throw new ResourceValidationException('Image non existante !');
        }

        $em = $this->getDoctrine()->getManager();
        $annonce->removeImage($image);
        $em->persist($annonce);
        $em->flush();

        return new JsonResponse(["success" => sprintf("L'image %s a été enlevée !", $image->getAlt())], 200);
    }

    /**
     * @Rest\Delete("/annonces/{id}", name="supprimer_annonce")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("annonce.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'annonce peut effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function supprimerAnnonce(Annonce $annonce) {
        if (!$annonce) {
            throw new ResourceValidationException('Annonce non existante !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($annonce);
        $em->flush();

        return new JsonResponse(["success" => "L'annonce a été supprimée !"], 200);
    }
}