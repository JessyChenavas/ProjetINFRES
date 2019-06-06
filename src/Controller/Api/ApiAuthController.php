<?php

namespace App\Controller\Api;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Voiture;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * @Route("/auth", name="auth_")
 */
class ApiAuthController extends ApiController
{
    /**
     * @Rest\Post("/register")
     * @return JsonResponse
     */
    public function register(Request $request, UserManagerInterface $userManager, ValidatorInterface $validator)
    {
        $data = json_decode($request->getContent(), true);

        // Log de la request
        $this->log->info('REQUEST;AuthRegister;POST|',$data);

        
        $em = $this->getDoctrine()->getManager();

        $user = new User();

       if (isset($data['voiture'])) {

           $voiture = $this->getDoctrine()
               ->getRepository(Voiture::class)
               ->findOneBy(['couleur' => $data['voiture']['couleur'], 'marque' => $data['voiture']['marque'], 'modele' => $data['voiture']['modele']]);

           if (!$voiture) {
               $voiture = new Voiture();
               $voiture->setCouleur($data['voiture']['couleur']);
               $voiture->setMarque($data['voiture']['marque']);
               $voiture->setModele($data['voiture']['modele']);

               $em->persist($voiture);
           }

           $user->setVoiture($voiture);
        }

        $user
            ->setUsername($data['username'])
            ->setPlainPassword($data['password'])
            ->setEmail($data['email'])
            ->setEnabled(true)
            ->setRoles(['ROLE_USER'])
            ->setSuperAdmin(false)
            ->setGenre($data['genre'])
            ->setPrenom($data['prenom'])
            ->setNom($data['nom'])
            ->setDateNaissance(new \DateTime($data['dateNaissance']))
            ->setPromotion($data['promotion']);

        $listErrors = $validator->validate($user);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        try {
            $em->flush();
            $userManager->updateUser($user, true);
        } catch (\Exception $e) {
          # return new JsonResponse(["error" => "ERROR : ".$e->getMessage()], 500);
            $responsejson = new JsonResponse(["error" => "L'email/username est déjà utilisé !"], 500);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error('RESPONSE;AuthRegister;POST|',$response);
            return $responsejson;
        }

        $responsejson = new JsonResponse(["success" => sprintf("%s a bien été inscrit ! ", $user->getUsername())], 201);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info('RESPONSE;AuthRegister;POST|',$response);
        return $responsejson;
        
    }
}