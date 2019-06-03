<?php

namespace App\Controller\Api;

use App\Entity\Trajet;
use App\Entity\User;
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
class ApiTrajetController extends AbstractController
{
    /**
     * @Rest\Get("/trajets/{id}", name="afficher_trajet")
     *
     * @return Response
     */
    public function afficherTrajet(Trajet $trajet)
    {
        $data =  $this->get('serializer')->serialize($trajet, 'json', ['attributes' =>
            [ 'id', 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'createur' => ['id', 'username', 'email', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']],  'passagers' => ['id','username','email']]]);

        $response = new Response($data);

        return $response;
    }

    /**
     * @Rest\Get("/trajets/users/{id}", name="afficher_trajets_utilisateur")
     *
     * @return Response
     */
    public function afficherTrajetParUtilisateur(User $user) {
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findBy(['createur' => $user]);

        $data =  $this->get('serializer')->serialize($trajets, 'json', ['attributes' =>
            [ 'id', 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'createur' => ['id', 'username', 'email', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']],  'passagers' => ['id','username','email']]]);

        $response = new Response($data);

        return $response;
    }

    /**
     * @Rest\Get("/trajets/depart/{depart}/arrive/{arrive}", name="afficher_trajets_lieu_depart_arrive")
     *
     * @return Response
     */
    public function afficherTrajetParLieuDepartArrive(string $depart, string $arrive) {
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findBy(['lieuDepart' => $depart, 'lieuArrive' => $arrive]);

        $data =  $this->get('serializer')->serialize($trajets, 'json', ['attributes' =>
            [ 'id', 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'createur' => ['id', 'username', 'email', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']],  'passagers' => ['id','username','email']]]);

        $response = new Response($data);

        return $response;
    }

    /**
     * @Rest\Get("/trajets", name="liste_trajets")
     *
     * @return Response
     */
    public function listeTrajets()
    {
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findAll();

        $data =  $this->get('serializer')->serialize($trajets, 'json', ['attributes' =>
            [ 'id', 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'createur' => ['id', 'username', 'email', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                'voiture' => ['modele', 'marque', 'couleur']],  'passagers' => ['id','username','email']]]);

        $response = new Response($data);

        return $response;
    }

    /**
     *  @Rest\Post("/trajets", name="creer_trajet")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('ROLE_USER')", statusCode=401, message="Vous devez être connecté pour effectuer cette action")
     */
    public function creerTrajet(Request $request, ValidatorInterface $validator)
    {
        $trajet = new Trajet();

        $data = json_decode($request->getContent(), true);

        $trajet->setLieuDepart($data['lieu_depart'])
            ->setLieuArrive($data['lieu_arrive'])
            ->setHeureDepart(new \DateTime($data["heure_depart"]))
            ->setPassagersMax($data['passagers_max'])
            ->setTarif($data['tarif'])
            ->setCreateur($this->getUser());

        $listErrors = $validator->validate($trajet);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet est enregistré !"], 201);
    }

    /**
     *  @Rest\Put("/trajets/{id}", name="modifier_trajet")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     */
    public function modifierTrajet(Request $request, Trajet $trajet, ValidatorInterface $validator)
    {
        $data = json_decode($request->getContent(), true);

        $trajet->setLieuDepart($data['lieu_depart'])
            ->setLieuArrive($data['lieu_arrive'])
            ->setHeureDepart(new \DateTime($data["heure_depart"]))
            ->setPassagersMax($data['passagers_max'])
            ->setTarif($data['tarif']);

        $listErrors = $validator->validate($trajet);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet a été modifié !"], 200);
    }

    /**
     *  @Rest\Delete("/trajets/{id}", name="supprimer_trajet")
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     */
    public function supprimerTrajet(Trajet $trajet) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet a été supprimé !"], 200);
    }

    /**
     *  @Rest\Post("/trajets/{trajet_id}/passagers/{passager_id}", name="ajouter_passager")
     *
     *  @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     */
    public function ajouterPassager(Trajet $trajet, User $user) {
        if($trajet->getCreateur()->getId() == $user->getId()) {
            return new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le créateur ne peut pas être passagé."],
                409);
        }

        if ($trajet->addPassager($user)) {
            $json_response = new JsonResponse(
                ["success" => sprintf("Le passager %s a été ajouté au trajet de %s ! ", $user->getUsername(), $trajet->getCreateur()->getUsername())],
                201);
        } else {
            return new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le trajet est complet ou le passager est déjà inscrit."],
                409);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return $json_response;
    }

    /**
     *  @Rest\Delete("/trajets/{trajet_id}/passagers/{passager_id}", name="supprimer_passager")
     *
     *  @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     *  @return JsonResponse
     *
     *  @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *  @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     */
    public function supprimerPassager(Trajet $trajet, User $user) {
        if ($trajet->removePassager($user)) {
            $json_response = new JsonResponse(
                ["success" => sprintf("Le passager %s a été supprimé du trajet de %s ! ", $user->getUsername(), $trajet->getCreateur()->getUsername())],
                200);
        } else {
            $json_response = new JsonResponse(
                ["error" => "Pas de suppression possible : l'utilisateur n'est pas inscrit"],
                409);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return $json_response;
    }
}