<?php

namespace App\Controller\Api;

use App\Entity\Evenement;
use App\Entity\Image;
use App\Entity\User;
use App\Exception\ResourceValidationException;
use App\Form\EvenementType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/api", name="api_")
 */
class ApiEvenementController extends ApiController
{
    /**
     * @Rest\Get("/events", defaults={"page" = 1}, name="liste_evenements")
     * @Rest\Get("/events/page{page}", name="liste_evenements_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function listeEvenements($page)
    {
        $events = $this->getDoctrine()
            ->getRepository(Evenement::class)
            ->findAll();

        if (!$events) {
            throw new ResourceValidationException('Aucun évènement trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($events, $page, 5);
        $serialization = $this->serializer->serialize('evenement', true);

        $data =  $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
    }

    /**
     * @Rest\Get("/events/{id}", name="afficher_evenement", requirements={"id" = "\d+"})
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherEvenement(Evenement $event = null)
    {
        if (!$event) {
            throw new ResourceValidationException('Évènement non existant !');
        }

        $data =  $this->get('serializer')->serialize($event, 'json', $this->serializer->serialize('evenement'));

        return new Response($data);
    }

    /**
     * @Rest\Get("/users/{id}/agenda", defaults={"page" = 1}, name="afficher_agenda_utilisateur")
     * @Rest\Get("/users/{id}/agenda/page{page}", name="afficher_agenda_utilisateur_pagine")
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("user.getId() == id or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul l'utilisateur concerné peut effectuer cette action !"))
     *
     * @return Response
     *
     * @throws ResourceValidationException
     */
    public function afficherAgendaUtilisateur ($page, User $user = null) {
        if (!$user) {
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $events = $this->getDoctrine()
            ->getRepository(Evenement::class)
            ->findEventByUser($user);

        if (!$events) {
            throw new ResourceValidationException('Aucun évènement trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($events, $page, 5);
        $serialization = $this->serializer->serialize('evenement', true);

        $data = $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
    }

    /**
     * @Rest\Post("/events", name="creer_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('ROLE_EDITOR')", statusCode=403, message="Vous devez être éditeur pour pouvoir créer un évènement !")
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
                $image = $this->getDoctrine()
                    ->getRepository(Image::class)
                    ->findOneBy(['url' => $data['image']['url'], 'alt' => $data['image']['alt']]);

                if (!$image) {
                    $image = new Image();
                    $image->setUrl($data['image']['url']);
                    $image->setAlt($data['image']['alt']);
                    $em->persist($image);
                }

                $event->setImage($image);
            }

            $em->persist($event);
            $em->flush();
            return new JsonResponse(['success' => sprintf('L\'évènement %s a bien été ajouté !', $data['titre'])], 201);
        }

        return new JsonResponse(['error' => $form->getErrors(true, false)->__toString()], 500);
    }

    /**
     * @Rest\Put("/events/{id}", name="modifier_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("event.estAuteur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'évènement peut effectuer cette action !")
     * @throws ResourceValidationException
     */
    public function modifierEvenement(Request $request, Evenement $event = null)
    {
        if (!$event) {
            throw new ResourceValidationException('Évènement non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(EvenementType::class, $event);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($data['image'])) {
                $image = $this->getDoctrine()
                    ->getRepository(Image::class)
                    ->findOneBy(['url' => $data['image']['url'], 'alt' => $data['image']['alt']]);

                if (!$image) {
                    $image = new Image();
                    $image->setUrl($data['image']['url']);
                    $image->setAlt($data['image']['alt']);
                    $em->persist($image);
                }

                $event->setImage($image);
            }

            $em->persist($event);
            $em->flush();
            return new JsonResponse(['success' => sprintf('L\'évènement %s a bien été modifié !', $data['titre'])], 200);
        }

        return new JsonResponse(['error' => $form->getErrors(true, false)->__toString()], 500);
    }

    /**
     * @Rest\Delete("/events/{id}", name="supprimer_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("event.estAuteur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur de l'évènement peut effectuer cette action !"))
     *
     * @throws ResourceValidationException
     */
    public function supprimerEvenement(Evenement $event = null) {
        if (!$event) {
            throw new ResourceValidationException('Évènement non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();

        return new JsonResponse(["success" => "L'évènement a été supprimé !"], 200);
    }

    /** @Route("/events/{id}/join", name="rejoindre_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *
     * @throws ResourceValidationException
     */
    public function rejoindreEvenement(Evenement $event = null) {
        if (!$event) {
            throw new ResourceValidationException('Évènement non existant !');
        }

        if (!$event->addParticipant($this->getUser())) {
            return new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : l'évènement est plein ou l'utilisateur est déjà inscrit."],
                409);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();

        return new JsonResponse(
            ["success" => sprintf("Vous avez rejoint l'évènement %s !", $event->getTitre())],
            200);
    }

    /** @Route("/events/{id}/leave", name="quitter_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *
     * @throws ResourceValidationException
     */
    public function quitterEvenement(Evenement $event = null) {
        if (!$event) {
            throw new ResourceValidationException('Évènement non existant !');
        }

        $event->removeParticipant($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();

        return new JsonResponse(
            ["success" => sprintf("Vous avez quitté l'évènement %s !", $event->getTitre())],
            200);
    }
}