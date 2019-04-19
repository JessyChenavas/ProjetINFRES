<?php

namespace App\Controller\Api;

use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/covoit")
 */
class ApiTrajetController extends AbstractController
{
    /**
     *  @Route("/trajets", name="api_trajet_create",  methods={"POST"})
     *  @param Request $request
     *
     *  @return JsonResponse
     */
    public function creerTrajet(Request $request, ValidatorInterface $validator)
    {
        $trajet = new Trajet();

        $data = json_decode($request->getContent(), true);

        $user = $this->getDoctrine()
            ->getRepository("App\Entity\User")
            ->find($data['creator_id']);

        $trajet->setLieuDepart($data['lieu_depart']);
        $trajet->setLieuArrive($data['lieu_arrive']);
        $trajet->setHeureDepart(new \DateTime($data["heure_depart"]));
        $trajet->setPassagersMax($data['passagers_max']);
        $trajet->setTarif($data['tarif']);
        $trajet->setCreator($user);

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
     *  @Route("/trajet/{id}", name="api_trajet_update",  methods={"PUT"})
     *  @param Request $request
     *
     *  @return JsonResponse
     */
    public function modifierTrajet(Request $request, Trajet $trajet, ValidatorInterface $validator)
    {
        $data = json_decode($request->getContent(), true);

        $trajet->setLieuDepart($data['lieu_depart']);
        $trajet->setLieuArrive($data['lieu_arrive']);
        $trajet->setHeureDepart(new \DateTime($data["heure_depart"]));
        $trajet->setPassagersMax($data['passagers_max']);
        $trajet->setTarif($data['tarif']);

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
     *  @Route("/trajet/{id}", name="api_trajet_delete", methods={"DELETE"})
     *
     *  @return JsonResponse
     */
    public function supprimerTrajet(Trajet $trajet) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet a été supprimé !"], 200);
    }

    /**
     *  @Route("/trajet/{trajet_id}/passager/{passager_id}", name="add_passager", methods={"POST"})
     *
     *  @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     *  @return JsonResponse
     */
    public function ajouterPassager(Trajet $trajet, User $user) {
        if ($trajet->addPassager($user)) {
            $json_response = new JsonResponse(
                ["success" => sprintf("Le passager %s a été ajouté au trajet de %s ! ", $user->getUsername(), $trajet->getCreator()->getUsername())],
                201);
        } else {
            $json_response = new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le trajet est complet ou le passager est déjà inscrit."],
                409);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return $json_response;
    }

    /**
     *  @Route("/trajet/{trajet_id}/passager/{passager_id}", name="remove_passager", methods={"DELETE"})
     *
     *  @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     *  @return JsonResponse
     */
    public function supprimerPassager(Trajet $trajet, User $user) {
        if ($trajet->removePassager($user)) {
            $json_response = new JsonResponse(
                ["success" => sprintf("Le passager %s a été supprimé du trajet de %s ! ", $user->getUsername(), $trajet->getCreator()->getUsername())],
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

    /**
     * @Route("/trajet/{id}", name="trajet_show", methods={"GET"})
     */
    public function afficherTrajet(Trajet $trajet)
    {
        $data =  $this->get('serializer')->serialize($trajet, 'json', ['attributes' =>
            [ 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'creator' => ['username'],  'passagers' => ['username']]]);

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/trajets", name="trajets_list", methods={"GET"})
     */
    public function listeTrajets()
    {
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findAll();

        $data =  $this->get('serializer')->serialize($trajets, 'json', ['attributes' =>
            [ 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'creator' => ['username'],  'passagers' => ['username']]]);

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}