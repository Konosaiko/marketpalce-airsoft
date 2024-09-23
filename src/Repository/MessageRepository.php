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

    /**
     * Find all conversations for a given user.
     *
     * @param User $user The user to find conversations for
     * @return array An array of user IDs representing the other participants in each conversation
     */
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

    /**
     * Find all unread messages in a conversation between two users.
     *
     * @param User $user The user who is the recipient of the unread messages
     * @param User $otherUser The user who sent the unread messages
     * @return array An array of unread Message entities
     */
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

    /**
     * Find all messages in a conversation between two users.
     *
     * @param User $user1 The first user in the conversation
     * @param User $user2 The second user in the conversation
     * @return array An array of Message entities, sorted by creation date in ascending order
     */
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