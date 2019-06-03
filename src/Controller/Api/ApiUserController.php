<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\User;
use App\Entity\Voiture;
use App\Entity\Message;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
class ApiUserController extends AbstractController
{
    /**
     * @Rest\Get("/users/{id}", name="afficher_utlisateur")
     *
     * @return Response
     */
    public function afficherUtilisateur(User $user)
    {
        $data =  $this->get('serializer')->serialize($user, 'json',
            ['attributes' => ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']]]);

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

        $data =  $this->get('serializer')->serialize($users, 'json',
            ['attributes' => ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']]]);

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     *  @Rest\Put("/users/{id}", name="modifier_utilisateur")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("user.getId() == id or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul l'utilisateur concerné peut effectuer cette action !"))
     */
    public function modifierUtilisateur(Request $request, User $user, ValidatorInterface $validator, UserManagerInterface $userManager) {
        $data = json_decode($request->getContent(), true);

        if (preg_match("/(CMC|MKX|FI|INFRES)/", $data['promotion'])) {
            $user->setPromotion($data['promotion']);
        }

        if (isset($data['voiture'])) {
            $voiture = new Voiture();
            $voiture->setCouleur($data['voiture']['couleur']);
            $voiture->setMarque($data['voiture']['marque']);
            $voiture->setModele($data['voiture']['modele']);
            $user->setVoiture($voiture);
        }

        $user
            ->setPlainPassword($data['password'])
            ->setGenre($data['genre'])
            ->setPrenom($data['prenom'])
            ->setNom($data['nom'])
            ->setDateNaissance(new \DateTime($data['dateNaissance']));

        $listErrors = $validator->validate($user);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        if (isset($data['role'])) {
            if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                $user->updateRole($data['role']);
            }
        }

        try {
            $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "ERROR : ".$e->getMessage()], 500);
        }

        return new JsonResponse(["success" => sprintf("%s a été modifié !", $user->getUsername())], 200);
    }

    /**
     *  @Rest\Post("/users/{id}/conversations", name="envoyer_message")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('ROLE_USER')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     */
    public function envoyerMessage(Request $request, User $destinataire) {
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

        $em->persist($message);
        $actualConv->addMessage($message);
        $em->persist($actualConv);
        $em->flush();

        return new JsonResponse(["success" => sprintf("Le message a bien été envoyé à %s !", $destinataire->getUsername())], 201);
    }

    /**
     *  @Rest\Get("/users/{user_id}/conversations/{conversation_id}", name="afficher_conversation")
     *
     *  @ParamConverter("conversation", options={"mapping": {"conversation_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"user_id": "id"}})
     *
     *  @return Response
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("conversation.estMembre(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seuls les membres de la conversation peuvent effectuer cette action !"))
     */
    public function afficherConversation(Conversation $conversation) {
        $data =  $this->get('serializer')->serialize($conversation, 'json',
            ['attributes' => ['id', 'messages' => ['texte', 'date', 'auteur' => ['username']], 'participants' => ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']]]]);

        $response = new Response($data);

        return $response;
    }

    /**
     *  @Rest\Get("/users/{id}/conversations", name="liste_conversations")
     *
     *  @return Response
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("user.getId() == id or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul l'utilisateur concerné peut effectuer cette action !"))
     */
    public function listeConversationsParUtilisateur(User $user) {
        $conversations = $this->getDoctrine()
            ->getRepository(Conversation::class)
            ->findConvByUser($user);

        $data =  $this->get('serializer')->serialize($conversations, 'json',
            ['attributes' => ['id', 'messages' => ['texte', 'date', 'auteur' => ['username']], 'participants' => ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']]]]);

        $response = new Response($data);

        return $response;
    }
}