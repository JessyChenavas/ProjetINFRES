<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    public function getSerializer() {
        return new SerializationController();
    }

    public function getPaginator() {
        return new PaginatingController();
    }
}