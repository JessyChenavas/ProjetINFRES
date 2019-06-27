<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class ConversationRepository extends EntityRepository
{
    public function findConvByUser(User $user) {
        return $this->createQueryBuilder('conversation')
            ->join('conversation.participants', 'users')
            ->where('users = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}