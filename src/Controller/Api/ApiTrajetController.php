<?php

namespace App\Controller\Api;

use App\Entity\Trajet;
use App\Entity\User;
use App\Exception\ResourceValidationException;
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
class ApiTrajetController extends ApiController
{
    /**
     * @Rest\Get("/trajets/{id}", name="afficher_trajet", requirements={"id" = "\d+"})
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherTrajet(Request $request, Trajet $trajet = null)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$trajet) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Trajet non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Trajet non existant !');
        }

        $data = $this->serializer->serialize($trajet, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }

    /**
     * @Rest\Get("/users/{id}/trajets", defaults={"page" = 1}, name="afficher_trajets_utilisateur")
     * @Rest\Get("/users/{id}/trajets/page{page}", name="afficher_trajets_utilisateur_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherTrajetParUtilisateur(Request $request, $page, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findBy(['createur' => $user]);

            if (!$trajets) {
                // Log de l'error
                $responsejson = new JsonResponse(["error" => "Aucun trajet trouvé !"], 404);
                $response = json_decode($responsejson->getContent(), true);
                $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
                
                throw new ResourceValidationException('Aucun trajet trouvé !');
            }

        $paginatedCollection = $this->paginator->paginate($trajets, $page, 10);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }

    /**
     * @Rest\Get("/trajets/depart/{depart}/arrive/{arrive}", defaults={"page" = 1}, name="afficher_trajets_lieu_depart_arrive")
     * @Rest\Get("/trajets/depart/{depart}/arrive/{arrive}/page{page}", name="afficher_trajets_lieu_depart_arrive_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherTrajetParLieuDepartArrive(Request $request, string $depart, string $arrive, $page) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findBy(['lieuDepart' => $depart, 'lieuArrive' => $arrive]);

        if (!$trajets) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Aucun trajet trouvé !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Aucun trajet trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($trajets, $page, 10);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
        return $response;
    }

    /**
     * @Rest\Get("/trajets", defaults={"page" = 1}, name="liste_trajets")
     * @Rest\Get("/trajets/page{page}", name="liste_trajets_pagine")
     * @throws ResourceValidationException
     */
    public function listeTrajets(Request $request, $page)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findAll();

            if (!$trajets) {
                // Log de l'error
                $responsejson = new JsonResponse(["error" => "Aucun trajet trouvé !"], 404);
                $response = json_decode($responsejson->getContent(), true);
                $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
                
                throw new ResourceValidationException('Aucun trajet trouvé !');
            }

        $paginatedCollection = $this->paginator->paginate($trajets, $page, 10);
        $data = $this->serializer->serialize($paginatedCollection, 'json');

        $response = new Response($data);

        // Log de la response
        $responsejson = json_decode($response->getContent(), true);
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$responsejson);
        
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
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        $trajet = new Trajet();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['tarif'])) {
            $data['tarif'] = 0;
        }

        $trajet->setLieuDepart($data['lieu_depart'])
            ->setLieuArrive($data['lieu_arrive'])
            ->setHeureDepart(new \DateTime($data["heure_depart"]))
            ->setPassagersMax($data['passagers_max'])
            ->setTarif($data['tarif'])
            ->setCreateur($this->getUser());

        $listErrors = $validator->validate($trajet);
        if(count($listErrors) > 0) {
            $responsejson = new JsonResponse(["error" => (string)$listErrors], 500);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        $responsejson = new JsonResponse(["success" => "Le trajet est enregistré !"], 201);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /**
     * @Rest\Put("/trajets/{id}", name="modifier_trajet")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     * @throws ResourceValidationException
     */
    public function modifierTrajet(Request $request, ValidatorInterface $validator, Trajet $trajet = null)
    {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$trajet) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Trajet non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Trajet non existant !');
        }

        $data = json_decode($request->getContent(), true);

        $trajet->setLieuDepart($data['lieu_depart'])
            ->setLieuArrive($data['lieu_arrive'])
            ->setHeureDepart(new \DateTime($data["heure_depart"]))
            ->setPassagersMax($data['passagers_max'])
            ->setTarif($data['tarif']);

        $listErrors = $validator->validate($trajet);
        if(count($listErrors) > 0) {
            $responsejson = new JsonResponse(["error" => (string)$listErrors], 500);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        $responsejson = new JsonResponse(["success" => "Le trajet a été modifié !"], 200);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /**
     * @Rest\Delete("/trajets/{id}", name="supprimer_trajet")
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     * @throws ResourceValidationException
     */
    public function supprimerTrajet(Request $request, Trajet $trajet = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$trajet) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Trajet non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Trajet non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($trajet);
        $em->flush();

        $responsejson = new JsonResponse(["success" => "Le trajet a été supprimé !"], 200);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }

    /**
     * @Rest\Post("/trajets/{trajet_id}/passagers/{passager_id}", name="ajouter_passager")
     *
     * @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     * @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     * @throws ResourceValidationException
     */
    public function ajouterPassager(Request $request, Trajet $trajet = null, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$trajet) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Trajet non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Trajet non existant !');
        }

        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        if($trajet->getCreateur()->getId() == $user->getId()) {
            $responsejson = new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le créateur ne peut pas être passagé."],
                409);
            $response = json_decode($responsejson->getContent(), true);
                    
            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        if (!$trajet->addPassager($user)) {
            $responsejson = new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le trajet est complet ou le passager est déjà inscrit."],
                409);
                $response = json_decode($responsejson->getContent(), true);
                    
                // Log de la response
                $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
                return $responsejson;
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        $responsejson = new JsonResponse(
            ["success" => sprintf("Le passager %s a été ajouté au trajet de %s ! ", $user->getUsername(), $trajet->getCreateur()->getUsername())],
            201);
            $response = json_decode($responsejson->getContent(), true);
            
            // Log de la response
            $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
    }

    /**
     * @Rest\Delete("/trajets/{trajet_id}/passagers/{passager_id}", name="supprimer_passager")
     *
     * @ParamConverter("trajet", options={"mapping": {"trajet_id": "id"}})
     * @ParamConverter("user", options={"mapping": {"passager_id": "id"}})
     *
     * @return JsonResponse
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')", statusCode=401, message="Vous devez être connecté pour effectuer cette action !"))
     * @Security("trajet.estCreateur(user) or is_granted('ROLE_ADMIN')", statusCode=403, message="Seul le créateur du trajet peut effectuer cette action !")
     * @throws ResourceValidationException
     */
    public function supprimerPassager(Request $request, Trajet $trajet = null, User $user = null) {
        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()));
        
        if (!$trajet) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Trajet non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Trajet non existant !');
        }

        if (!$user) {
            // Log de l'error
            $responsejson = new JsonResponse(["error" => "Utilisateur non existant !"], 404);
            $response = json_decode($responsejson->getContent(), true);
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            
            throw new ResourceValidationException('Utilisateur non existant !');
        }

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

        $response = json_decode($json_response->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $json_response;
    }
}