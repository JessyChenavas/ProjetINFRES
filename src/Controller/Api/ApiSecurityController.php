<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;

class ApiSecurityController extends AbstractController
{
    private $client_manager;
    public function __construct(ClientManagerInterface $client_manager)
    {
        $this->client_manager = $client_manager;
    }

    /**
     * @Route("/createClient", name="api_create_client",  methods={"POST"})
     *
     * @return Response
     */
    public function AuthenticationAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
//        if (empty($data['redirect-uri']) || empty($data['grant-type'])) {
//            return $this->handleView($this->view($data));
//        }
        $clientManager = $this->client_manager;
        $client = $clientManager->createClient();
        $client->setRedirectUris([$data['redirect-uri']]);
        $client->setAllowedGrantTypes([$data['grant-type']]);
        $clientManager->updateClient($client);
        $rows = [
            'client_id' => $client->getPublicId(), 'client_secret' => $client->getSecret()
        ];
        return new Response('Ok');
    }
}