<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;

class ApiSecurityController extends ApiController
{
    private $client_manager;
    public function __construct(ClientManagerInterface $client_manager)
    {
        parent::__construct();
        $this->client_manager = $client_manager;
    }

    /**
     * @Rest\Post("/createClient")
     *
     * @return Response
     */
    public function AuthenticationAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        // Log de la request
        $this->log->info(sprintf('REQUEST;%s;%s|', $request->getRequestUri(), $request->getMethod()),$data);

        if (empty($data['redirect-uri']) || empty($data['grant-type'])) {
            $responsejson = new JsonResponse(['error' => 'Field(s) empty']);
            $response = json_decode($responsejson->getContent(), true);

            // Log de la response
            $this->log->error(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
            return $responsejson;
        }

        $clientManager = $this->client_manager;
        $client = $clientManager->createClient();
        $client->setRedirectUris([$data['redirect-uri']]);
        $client->setAllowedGrantTypes([$data['grant-type']]);
        $clientManager->updateClient($client);

        $rows = [
            'client_id' => $client->getPublicId(), 'client_secret' => $client->getSecret()
        ];

        $responsejson = new JsonResponse($rows);
        $response = json_decode($responsejson->getContent(), true);
            
        // Log de la response
        $this->log->info(sprintf('RESPONSE;%s;%s|', $request->getRequestUri(), $request->getMethod()),$response);
        return $responsejson;
    }
}