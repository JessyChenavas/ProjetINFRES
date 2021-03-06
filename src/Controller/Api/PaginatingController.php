<?php

namespace App\Controller\Api;

use App\Entity\PaginatedCollection;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class PaginatingController
{
    public function paginate(array $entities, $page, $limit) {
        $adapter = new ArrayAdapter($entities);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($limit)
            ->setCurrentPage($page)
        ;
        $paginatedCollection = new PaginatedCollection($pagerfanta);

        return $paginatedCollection;
    }
}