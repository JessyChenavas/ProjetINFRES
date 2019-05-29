<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Entity\Image;
use App\Form\EvenementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/api", name="api_")
 */
class ApiEvenementController extends AbstractController
{
    /**
     * @Rest\Get("/events", name="liste_evenements")
     *
     * @return Response
     */
    public function listeEvenements()
    {
        $repository = $this->getDoctrine()->getRepository(Evenement::class);
        $events = $repository->findall();

        $data =  $this->get('serializer')->serialize($events, 'json', ['attributes' =>
            [ 'id', 'titre', 'description', 'lieu', 'date', 'image', 'auteur' => ['id','username','email']]]);

        $response = new Response($data);
        return $response;
    }

    /**
     * @Rest\Get("/event/{id}", name="afficher_evenement")
     *
     * @return Response
     */
    public function afficherEvenement(Evenement $event)
    {
        $data =  $this->get('serializer')->serialize($event, 'json', ['attributes' =>
            [ 'id', 'titre', 'description', 'lieu', 'date', 'image', 'auteur' => ['id','username','email']]]);

        $response = new Response($data);
        return $response;
    }


    /**
     * @Rest\Post("/event", name="creer_evenement")
     *
     * @return JsonResponse
     *
     * @Security("has_role('ROLE_EDITOR')")
     *
     */
    public function creerEvenement(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $event = new Evenement();
        $form = $this->createForm(EvenementType::class, $event);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setAuteur($this->getUser());

            if (isset($data['image'])) {
                $image = new Image();
                $image->setUrl($data['image']['url']);
                $image->setAlt($data['image']['alt']);
                $em->persist($image);
                $event->setImage($image);
            }

            $em->persist($event);
            $em->flush();
            return new JsonResponse(['success' => sprintf('L\'évènement %s a bien été ajouté !', $data['titre'])], 201);
        }

        return new JsonResponse(['error' => $form->getErrors(true, false)->__toString()], 500);
    }

    /**
     * @Rest\Put("/event/{id}", name="modifier_evenement")
     *
     * @return JsonResponse
     *
     * @Security("event.estAuteur(user) or has_role('ROLE_ADMIN')")
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

        return new JsonResponse(['error' => $form->getErrors(true, false)->__toString()], 500);
    }

    /**
     *  @Rest\Delete("/event/{id}", name="supprimer_evenement")
     *
     *  @return JsonResponse
     *
     *  @Security("event.estAuteur(user) or has_role('ROLE_ADMIN')")
     *
     */
    public function supprimerEvenement(Evenement $event) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();

        return new JsonResponse(["success" => "L'évènement a été supprimé !"], 200);
    }
}