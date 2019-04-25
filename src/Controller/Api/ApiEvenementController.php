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
    public function afficherEvenements()
    {
        $repository = $this->getDoctrine()->getRepository(Evenement::class);
        $events = $repository->findall();

        $data =  $this->get('serializer')->serialize($events, 'json', ['attributes' =>
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
}