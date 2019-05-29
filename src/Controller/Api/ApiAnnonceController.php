<?php

namespace App\Controller\Api;

use App\Entity\Annonce;
use App\Entity\Image;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/api", name="api_")
 */
class ApiAnnonceController extends AbstractController
{
    /**
     *  @Rest\Post("/annonces", name="creer_annonce")
     *
     *  @return JsonResponse
     *
     *  @Security("has_role('ROLE_USER')")
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
     * @Rest\Get("/annonces/{id}", name="afficher_annonce")
     *
     * @return Response
     */
    public function afficherAnnonce(Annonce $annonce)
    {
        $data =  $this->get('serializer')->serialize($annonce, 'json', ['attributes' =>
            [ 'id', 'titre', 'description', 'prix', 'createur', 'images']]);

        $response = new Response($data);

        return $response;
    }

    /**
     * @Rest\Get("/annonces", name="liste_annonces")
     *
     * @return Response
     */
    public function listeAnnonces()
    {
        $annonces = $this->getDoctrine()
            ->getRepository(Annonce::class)
            ->findAll();

        $data =  $this->get('serializer')->serialize($annonces, 'json', ['attributes' =>
            [ 'id', 'titre', 'description', 'prix', 'createur', 'images']]);

        $response = new Response($data);

        return $response;
    }

}