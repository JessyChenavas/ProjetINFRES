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
     * @Rest\Get("/users/{id}", name="afficher_utlisateur", requirements={"id" = "\d+"})
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherUtilisateur(User $user = null)
    {
        if (!$user) {
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $data = $this->get('serializer')->serialize($user, 'json', $this->serializer->serialize('user'));

        return new Response($data);
    }

    /**
     * @Rest\Get("/users", defaults={"page" = 1}, name="liste_utlisateurs")
     * @Rest\Get("/users/page{page}", name="liste_utlisateurs_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function listeUtilisateurs($page)
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();

        if (!$users) {
            throw new ResourceValidationException('Aucun utilisateur trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($users, $page, 5);
        $serialization = $this->serializer->serialize('user', true);

        $data = $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
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
        if (!$user) {
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
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        try {
            $em->flush();
            $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "ERROR : ".$e->getMessage()], 500);
        }

        return new JsonResponse(["success" => sprintf("%s a été modifié !", $user->getUsername())], 200);
    }

    /**
     * @Rest\Delete("/users/{id}", name="supprimer_utilisateur")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('ROLE_ADMIN')", statusCode=403, message="Seul un administrateur est autorisé !")
     * @throws ResourceValidationException
     */
    public function supprimerUtilisateur(User $user = null) {
        if (!$user) {
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return new JsonResponse(["success" => "L'utilisateur a été supprimé !"], 200);
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
        if (!$destinataire) {
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
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em->persist($message);
        $em->persist($actualConv);
        $em->flush();

        return new JsonResponse(["success" => sprintf("Le message a bien été envoyé à %s !", $destinataire->getUsername())], 201);
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
    public function afficherConversation(Conversation $conversation = null) {
        if (!$conversation) {
            throw new ResourceValidationException('Conversation non existante !');
        }

        $data =  $this->get('serializer')->serialize($conversation, 'json', $this->serializer->serialize('conversation'));

        return new Response($data);
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
    public function listeConversationsParUtilisateur($page, User $user = null) {
        if (!$user) {
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $conversations = $this->getDoctrine()
            ->getRepository(Conversation::class)
            ->findConvByUser($user);

        if (!$conversations) {
            throw new ResourceValidationException('Aucune conversation à ce jour !');
        }

        $paginatedCollection = $this->paginator->paginate($conversations, $page, 4);
        $serialization = $this->serializer->serialize('conversation', true);

        $data =  $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
    }
}