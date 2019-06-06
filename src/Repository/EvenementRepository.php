<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class EvenementRepository extends EntityRepository
{
    public function findEventByUser(User $user) {
        return $this->createQueryBuilder('evenement')
            ->join('evenement.participants', 'users')
            ->where('users = :user')
            ->setParameter('user', $user)
            ->orderBy('evenement.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}