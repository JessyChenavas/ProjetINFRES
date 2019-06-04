<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    protected $serializer;
    protected $paginator;

    public function __construct()
    {
        $this->serializer = new SerializationController();
        $this->paginator = new PaginatingController();
    }
}