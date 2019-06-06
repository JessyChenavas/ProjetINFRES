<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ApiController extends AbstractController
{
    protected $serializer;
    protected $paginator;
    protected $log; //Log de l'API

    public function __construct()
    {
        $this->serializer = new SerializationController();
        $this->paginator = new PaginatingController();
        $this->log = new Logger('API');
        $this->log->pushHandler(new StreamHandler('test.api.log', Logger::DEBUG));
    }
}