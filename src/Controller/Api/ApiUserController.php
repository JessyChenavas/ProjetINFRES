<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/api", name="api_")
 */
class ApiUserController extends AbstractController
{
    /**
     * @Rest\Get("/user/{id}", name="afficher_utlisateur")
     *
     * @return Response
     */
    public function afficherUtilisateur(User $user)
    {
        $data =  $this->get('serializer')->serialize($user, 'json', ['attributes' => ['id', 'username', 'email']]);

        $response = new Response($data);

        return $response;
    }

    /**
     * @Rest\Get("/users", name="liste_utlisateurs")
     *
     * @return Response
     */
    public function listeUtilisateurs()
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();

        $data =  $this->get('serializer')->serialize($users, 'json', ['attributes' => ['id', 'username', 'email']]);

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}