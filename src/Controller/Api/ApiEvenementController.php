<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Form\EvenementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/api", name="api_")
 */
class ApiEvenementController extends AbstractController
{
    /**
     * @Rest\Get("/events")
     *
     * @return Response
     */
    public function listeEvenements()
    {
        $repository = $this->getDoctrine()->getRepository(Evenement::class);
        $events = $repository->findall();

        $data =  $this->get('serializer')->serialize($events, 'json', ['attributes' =>
            [ 'titre', 'description', 'lieu', 'date']]);

        $response = new Response($data);
        return $response;
    }

    /**
     * @Rest\Get("/event/{id}")
     *
     * @return Response
     */
    public function afficherEvenement(Evenement $event)
    {
        $data =  $this->get('serializer')->serialize($event, 'json', ['attributes' =>
            [ 'titre', 'description', 'lieu', 'date']]);

        $response = new Response($data);
        return $response;
    }


    /**
     * @Rest\Post("/event")
     *
     * @return JsonResponse
     */
    public function creerEvenement(Request $request)
    {
        $event = new Evenement();
        $form = $this->createForm(EvenementType::class, $event);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            return new JsonResponse(['success' => sprintf('L\'évènement %s a bien été ajouté !', $data['titre'])], 201);
        }

        return new JsonResponse(['error' => $form->getErrors()], 500);
    }

    /**
     * @Rest\Put("/event/{id}")
     *
     * @return JsonResponse
     */
    public function modifierEvenement(Request $request, Evenement $event)
    {
        $form = $this->createForm(EvenementType::class, $event);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush();
            return new JsonResponse(['success' => sprintf('L\'évènement %s a bien été modifié !', $data['titre'])], 200);
        }

        return new JsonResponse(['error' => $form->getErrors()], 500);
    }

    /**
     *  @Rest\Delete("/event/{id}")
     *
     *  @return JsonResponse
     */
    public function supprimerEvenement(Evenement $event) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();

        return new JsonResponse(["success" => "L'évènement a été supprimé !"], 200);
    }
}