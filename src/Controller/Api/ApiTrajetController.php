<?php

namespace App\Controller\Api;

use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/covoit")
 */
class ApiTrajetController extends AbstractController
{
    /**
     *  @Route("/trajets", name="api_auth_register",  methods={"POST"})
     *  @param Request $request
     *
     *  @return JsonResponse
     */
    public function creerTrajet(Request $request)
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

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet est enregistré !"], 201);
    }

    /**
     *  @Route("/trajet/{trajet_id}/passager/add/{passager_id}", name="add_passager")
     *
     *  @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     *  @return JsonResponse
     */
    public function ajouterPassager(Trajet $trajet, User $user) {
        $trajet->addPassager($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

       # gérer passagers max

        return new JsonResponse(["success" => "Le passager " . $user->getUsername() . " est ajouté au trajet de " . $trajet->getCreator()->getUsername() . "!"], 201);
    }

    /**
     *  @Route("/trajet/{trajet_id}/passager/remove/{passager_id}", name="remove_passager")
     *
     *  @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     *  @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     *  @return JsonResponse
     */
    public function supprimerPassager(Trajet $trajet, User $user) {
        $trajet->removePassager($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        # gérer passagers max

        return new JsonResponse(["success" => "Le passager " . $user->getUsername() . " est supprimé du trajet de " . $trajet->getCreator()->getUsername() . "!"], 201);
    }

    /**
     * @Route("/trajet/{id}", name="trajet_show", methods={"GET"})
     */
    public function afficherTrajet(Trajet $trajet)
    {
        $data =  $this->get('serializer')->serialize($trajet, 'json');

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}