<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Voiture;
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
     *  @Security("has_role('ROLE_USER')")
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

        return new JsonResponse(["success" => $user->getUsername(). " a été modifié !"], 200);
    }
}