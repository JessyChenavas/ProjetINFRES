<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/api")
 */
class ApiUserController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="user_show", methods={"GET"})
     */
    public function afficherUtilisateur(User $user)
    {
        $data =  $this->get('serializer')->serialize($user, 'json', ['attributes' => ['id', 'username', 'email']]);

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/users", name="users_list", methods={"GET"})
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