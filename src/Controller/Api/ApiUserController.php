<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\User;
use App\Entity\Voiture;
use App\Entity\Message;
use App\Exception\ResourceValidationException;
use FOS\UserBundle\Model\UserManagerInterface;
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
class ApiUserController extends ApiController
{
    /**
     * @Rest\Get("/users/{id}", name="afficher_utilisateur", requirements={"id" = "\d+"})
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherUtilisateur(Request $request, User $user = null)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $data = $this->serializer->serialize($user, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }

    /**
     * @Rest\Get("/users", defaults={"page" = 1}, name="liste_utlisateurs")
     * @Rest\Get("/users/page{page}", name="liste_utlisateurs_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function listeUtilisateurs(Request $request, $page)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();

        if (!$users) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Aucun utilisateur trouvé !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Aucun utilisateur trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($users, $page, 5);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }

    /**
     * @Rest\Put("/users/{id}", name="modifier_utilisateur")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("user.getId() == id or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul l'utilisateur concerné peut effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function modifierUtilisateur(Request $request, ValidatorInterface $validator, UserManagerInterface $userManager, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $data = json_decode($request->getContent(), true);
        $em = $this->getDoctrine()->getManager();

        if (isset($data['voiture'])) {

            $voiture = $this->getDoctrine()
                ->getRepository(Voiture::class)
                ->findOneBy(['couleur' => $data['voiture']['couleur'], 'marque' => $data['voiture']['marque'], 'modele' => $data['voiture']['modele']]);

            if (!$voiture) {
                $voiture = new Voiture();
                $voiture->setCouleur($data['voiture']['couleur']);
                $voiture->setMarque($data['voiture']['marque']);
                $voiture->setModele($data['voiture']['modele']);

                $em->persist($voiture);
            }

            $user->setVoiture($voiture);
        }

        if (isset($data['telephone'])) {
            $user->setTelephone($data['telephone']);
        }

        $user->setPlainPassword($data['password'])
            ->setGenre($data['genre'])
            ->setPrenom($data['prenom'])
            ->setNom($data['nom'])
            ->setDateNaissance(new \DateTime($data['dateNaissance']))
            ->setPromotion($data['promotion']);;

        if (isset($data['role'])) {
            if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                $user->updateRole($data['role']);
            }
        }

        $listErrors = $validator->validate($user);
        if(count($listErrors) > 0) {
            $responsejson = new JsonResponse(["error" => (string)$listErrors], 500);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        try {
            $em->flush();
            $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            $responsejson = new JsonResponse(["error" => "ERROR : ".$e->getMessage()], 500);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $responsejson = new JsonResponse(["success" => sprintf("%s a été modifié !", $user->getUsername())], 200);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /**
     * @Rest\Delete("/users/{id}", name="supprimer_utilisateur")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('ROLE_ADMIN')", statusCode=403, message="Seul un administrateur est autorisé !")
     * @throws ResourceValidationException
     */
    public function supprimerUtilisateur(Request $request, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        $responsejson = new JsonResponse(["success" => "L'utilisateur a été supprimé !"], 200);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /**
     * @Rest\Post("/users/{id}/conversations", name="envoyer_message")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('ROLE_USER')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function envoyerMessage(Request $request, ValidatorInterface $validator, User $destinataire = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$destinataire) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Destinataire non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Destinataire non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);

        $actualConv = new Conversation();

        $conversations = $this->getDoctrine()
            ->getRepository(Conversation::class)
            ->findAll();

        foreach ($conversations as $c) {
            if ($c->getParticipants()->contains($destinataire) && $c->getParticipants()->contains($this->getUser())) {
                $actualConv = $c;
            }
        }

        if (!$actualConv->getId()) {
            $actualConv->addParticipant($destinataire)
                ->addParticipant($this->getUser());
        }

        $message = new Message();
        $message->setAuteur($this->getUser())
            ->setDate(new \DateTime())
            ->setTexte($data['texte'])
            ->setAuteur($this->getUser())
            ->setConversation($actualConv)
        ;

        $actualConv->addMessage($message);

        $listErrors = $validator->validate($actualConv);
        if(count($listErrors) > 0) {
            $responsejson = new JsonResponse(["error" => (string)$listErrors], 500);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $em->persist($message);
        $em->persist($actualConv);
        $em->flush();

        $responsejson = new JsonResponse(["success" => sprintf("Le message a bien été envoyé à %s !", $destinataire->getUsername())], 201);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /**
     * @Rest\Get("/users/{user_id}/conversations/{conversation_id}", name="afficher_conversation", requirements={"user_id" = "\d+", "conversation_id"= "\d+"})
     *
     * @ParamConverter("conversation", options={"mapping": {"conversation_id": "id"}})
     * @ParamConverter("user", options={"mapping": {"user_id": "id"}})
     *
     * @return Response
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("conversation.estMembre(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seuls les membres de la conversation peuvent effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function afficherConversation(Request $request, Conversation $conversation = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$conversation) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Conversation non existante !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            
            throw new ResourceValidationException('Conversation non existante !');
        }

        $data = $this->serializer->serialize($conversation, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }

    /**
     * @Rest\Get("/users/{id}/conversations", defaults={"page" = 1}, name="liste_conversations")
     * @Rest\Get("/users/{id}/conversations/page{page}", name="liste_conversations_pagine")
     *
     * @return Response
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("user.getId() == id or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul l'utilisateur concerné peut effectuer cette action !"))
     * @throws ResourceValidationException
     */
    public function listeConversationsParUtilisateur(Request $request, $page, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $conversations = $this->getDoctrine()
            ->getRepository(Conversation::class)
            ->findConvByUser($user);

        if (!$conversations) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Aucune conversation à ce jour !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Aucune conversation à ce jour !');
        }

        $paginatedCollection = $this->paginator->paginate($conversations, $page, 4);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }
}