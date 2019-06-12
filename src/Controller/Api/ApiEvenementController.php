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
    public function listeEvenements(Request $request, $page)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        $events = $this->getDoctrine()
            ->getRepository(Evenement::class)
            ->findAll();

        if (!$events) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Aucun évènement trouvé !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Aucun évènement trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($events, $page, 5);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        $response = new Response($data);

        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$data);
        
        return $response;
    }

    /**
     * @Rest\Get("/events/{id}", name="afficher_evenement", requirements={"id" = "\d+"})
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherEvenement(Request $request, Evenement $event = null)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$event) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Évènement non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Évènement non existant !');
        }

        $data = $this->serializer->serialize($event, 'json');
        $response = new Response($data);

        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$data);
        
        return $response;
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
    public function afficherAgendaUtilisateur (Request $request, $page, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $events = $this->getDoctrine()
            ->getRepository(Evenement::class)
            ->findEventByUser($user);

        if (!$events) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Aucun évènement trouvé !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Aucun évènement trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($events, $page, 5);

        $data = $this->serializer->serialize($paginatedCollection, 'json');
        $response = new Response($data);

        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$data);
        
        return $response;
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
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
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

            $responsejson = new JsonResponse(['success' => sprintf('L\'évènement %s a bien été ajouté !', $data['titre'])], 201);
            $response = json_decode($responsejson->getContent(), true);
            
            // Log de la response
            $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;

        }

        $responsejson = new JsonResponse(['error' => $form->getErrors(true, false)->__toString()], 500);
        $response = json_decode($responsejson->getContent(), true);
        
        // Log de la response
        $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;

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
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$event) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Évènement non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
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

            $responsejson = new JsonResponse(['success' => sprintf('L\'évènement %s a bien été modifié !', $data['titre'])], 200);
            $response = json_decode($responsejson->getContent(), true);
            
            // Log de la response
            $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $responsejson = new JsonResponse(['error' => $form->getErrors(true, false)->__toString()], 500);
        $response = json_decode($responsejson->getContent(), true);
                    
            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
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
    public function supprimerEvenement(Request $request, Evenement $event = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$event) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Évènement non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Évènement non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();

        $responsejson = new JsonResponse(["success" => "L'évènement a été supprimé !"], 200);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /** @Route("/events/{id}/join", name="rejoindre_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *
     * @throws ResourceValidationException
     */
    public function rejoindreEvenement(Request $request, Evenement $event = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$event) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Évènement non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Évènement non existant !');
        }

        if (!$event->addParticipant($this->getUser())) {
            $responsejson = new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : l'évènement est plein ou l'utilisateur est déjà inscrit."],
                409);
            $response = json_decode($responsejson->getContent(), true);
                    
            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();

        $responsejson = new JsonResponse(
            ["success" => sprintf("Vous avez rejoint l'évènement %s !", $event->getTitre())],
            200);
            $response = json_decode($responsejson->getContent(), true);
                    
            // Log de la response
            $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
    }

    /** @Route("/events/{id}/leave", name="quitter_evenement")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     *
     * @throws ResourceValidationException
     */
    public function quitterEvenement(Request $request, Evenement $event = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$event) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Évènement non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Évènement non existant !');
        }

        $event->removeParticipant($this->getUser());

        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();

        $responsejson = new JsonResponse(
            ["success" => sprintf("Vous avez quitté l'évènement %s !", $event->getTitre())],
            200);
            $response = json_decode($responsejson->getContent(), true);
                    
            // Log de la response
            $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
    }
}