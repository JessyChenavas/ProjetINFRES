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
    public function afficherTrajet(Trajet $trajet = null)
    {
        if (!$trajet) {
            throw new ResourceValidationException('Trajet non existant !');
        }

        $data =  $this->get('serializer')->serialize($trajet, 'json', $this->serializer->serialize('trajet'));

        return new Response($data);
    }

    /**
     * @Rest\Get("/trajets/users/{id}", defaults={"page" = 1}, name="afficher_trajets_utilisateur")
     * @Rest\Get("/trajets/users/{id}/page{page}", name="afficher_trajets_utilisateur_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherTrajetParUtilisateur($page, User $user = null) {
        if (!$user) {
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findBy(['createur' => $user]);

        if (!$trajets) {
            throw new ResourceValidationException('Aucun trajet trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($trajets, $page, 10);
        $serialization = $this->serializer->serialize('trajet', true);

        $data = $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
    }

    /**
     * @Rest\Get("/trajets/depart/{depart}/arrive/{arrive}", defaults={"page" = 1}, name="afficher_trajets_lieu_depart_arrive")
     * @Rest\Get("/trajets/depart/{depart}/arrive/{arrive}/page{page}", name="afficher_trajets_lieu_depart_arrive_pagine")
     *
     * @return Response
     * @throws ResourceValidationException
     */
    public function afficherTrajetParLieuDepartArrive(string $depart, string $arrive, $page) {
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findBy(['lieuDepart' => $depart, 'lieuArrive' => $arrive]);

        if (!$trajets) {
            throw new ResourceValidationException('Aucun trajet trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($trajets, $page, 10);
        $serialization = $this->serializer->serialize('trajet', true);

        $data = $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
    }

    /**
     * @Rest\Get("/trajets", defaults={"page" = 1}, name="liste_trajets")
     * @Rest\Get("/trajets/page{page}", name="liste_trajets_pagine")
     * @throws ResourceValidationException
     */
    public function listeTrajets($page)
    {
        $trajets = $this->getDoctrine()
            ->getRepository(Trajet::class)
            ->findAll();

        if (!$trajets) {
            throw new ResourceValidationException('Aucun trajet trouvé !');
        }

        $paginatedCollection = $this->paginator->paginate($trajets, $page, 10);
        $serialization = $this->serializer->serialize('trajet', true);

        $data = $this->get('serializer')->serialize($paginatedCollection, 'json', $serialization);

        return new Response($data);
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
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet est enregistré !"], 201);
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
        if (!$trajet) {
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
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet a été modifié !"], 200);
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
    public function supprimerTrajet(Trajet $trajet = null) {
        if (!$trajet) {
            throw new ResourceValidationException('Trajet non existant !');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($trajet);
        $em->flush();

        return new JsonResponse(["success" => "Le trajet a été supprimé !"], 200);
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
    public function ajouterPassager(Trajet $trajet = null, User $user = null) {
        if (!$trajet) {
            throw new ResourceValidationException('Trajet non existant !');
        }

        if (!$user) {
            throw new ResourceValidationException('Utilisateur non existant !');
        }

        if($trajet->getCreateur()->getId() == $user->getId()) {
            return new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le créateur ne peut pas être passagé."],
                409);
        }

        if (!$trajet->addPassager($user)) {
            return new JsonResponse(
                ["error" => "L'ajout n'a pas été effectué : le trajet est complet ou le passager est déjà inscrit."],
                409);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($trajet);
        $em->flush();

        return new JsonResponse(
            ["success" => sprintf("Le passager %s a été ajouté au trajet de %s ! ", $user->getUsername(), $trajet->getCreateur()->getUsername())],
            201);
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
    public function supprimerPassager(Trajet $trajet = null, User $user = null) {
        if (!$trajet) {
            throw new ResourceValidationException('Trajet non existant !');
        }

        if (!$user) {
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

        return $json_response;
    }
}