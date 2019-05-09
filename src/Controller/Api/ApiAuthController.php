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
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/auth", name="auth_")
 */
class ApiAuthController extends AbstractController
{
    /**
     * @Rest\Post("/register")

     * @return JsonResponse
     */
    public function register(Request $request, UserManagerInterface $userManager, ValidatorInterface $validator)
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $username = $data['username'];
        $password = $data['password'];
        $email = $data['email'];

        $user = new User();

        if (preg_match("/(CMC|MKX|FI|INFRES)/", $data['promotion'])) {
            $user->setPromotion($data['promotion']);
        }

       if (isset($data['voiture'])) {
           $voiture = new Voiture();

           $voiture->setCouleur($data['voiture']['couleur']);
           $voiture->setMarque($data['voiture']['marque']);
           $voiture->setModele($data['voiture']['modele']);

            $user->setVoiture($voiture);
        }

        $user
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setEnabled(true)
            ->setRoles(['ROLE_USER'])
            ->setSuperAdmin(false)
        ;

        $listErrors = $validator->validate($user);
        if(count($listErrors) > 0) {
            return new JsonResponse(["error" => (string)$listErrors], 500);
        }

        try {
            $userManager->updateUser($user, true);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "ERROR : ".$e->getMessage()], 500);
        }

        return new JsonResponse(["success" => $user->getUsername(). " has been registered!"], 200);
    }
}