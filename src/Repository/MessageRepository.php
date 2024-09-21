<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findUserConversations(User $user): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb->select('DISTINCT CASE 
                WHEN m.sender = :user THEN IDENTITY(m.recipient)
                ELSE IDENTITY(m.sender)
            END AS otherUserId')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('m.sender', ':user'),
                $qb->expr()->eq('m.recipient', ':user')
            ))
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findUnreadMessagesInConversation(User $user, User $otherUser): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.recipient = :user')
            ->andWhere('m.sender = :otherUser')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('otherUser', $otherUser)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getResult();
    }

    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}