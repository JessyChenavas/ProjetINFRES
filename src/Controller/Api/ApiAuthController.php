<?php
/**
 * Created by PhpStorm.
 * User: Jessy
 * Date: 14/03/2019
 * Time: 14:53
 */

namespace App\Controller\Api;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/auth")
 */
class ApiAuthController extends AbstractController
{
    /**
     * /**
     * @Route("/register", name="api_auth_register",  methods={"POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        // Entity Manager
        $em = $this->getDoctrine()->getManager();

        $data = json_decode(
            $request->getContent(),
            true
        );

        // Ajouter validation des données ici

        /*$validator = Validation::createValidator();

        $constraint = new Assert\Collection(array(
            // the keys correspond to the keys in the input array
            'username' => new Assert\Length(array('min' => 1)),
            'password' => new Assert\Length(array('min' => 1)),
            'email' => new Assert\Email(),
        ));

        var_dump($data);
        $violations = $validator->validate($data, $constraint);

        if ($violations->count() > 0) {
            return new JsonResponse(["error" => (string)$violations], 500);
        } */

        $username = $data['username'];
        $password = $data['password'];
        $email = $data['email'];

        $user = new User($username, $email);
        $user->setPassword($encoder->encodePassword($user, $password));

        try {
            $em->persist($user);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => "ERROR : ".$e->getMessage()], 500);
        }
        $em->flush();

        return new JsonResponse(["success" => $user->getUsername(). " has been registered!"], 200);
    }
}