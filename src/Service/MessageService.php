<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class MessageService
{
    private $entityManager;
    private $messageRepository;

    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
    }

    public function sendMessage(User $sender, User $recipient, string $content): Message
    {
        $message = new Message();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setContent($content);
        $message->setSentAt(new \DateTimeImmutable());
        $message->setIsRead(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function markAsRead(Message $message): void
    {
        $message->setIsRead(true);
        $this->entityManager->flush();
    }

    public function getConversation(User $user1, User $user2): array
    {
        return $this->messageRepository->findConversation($user1, $user2);
    }

    public function getUnreadMessages(User $user): array
    {
        return $this->messageRepository->findUnreadMessages($user);
    }

    public function deleteMessage(Message $message): void
    {
        $this->entityManager->remove($message);
        $this->entityManager->flush();
    }

    public function getUserConversations(User $user): array
    {
        $sentMessages = $this->messageRepository->findBy(['sender' => $user], ['sentAt' => 'DESC']);
        $receivedMessages = $this->messageRepository->findBy(['recipient' => $user], ['sentAt' => 'DESC']);

        $conversations = [];
        foreach (array_merge($sentMessages, $receivedMessages) as $message) {
            $otherUser = $message->getSender() === $user ? $message->getRecipient() : $message->getSender();
            $conversations[$otherUser->getId()] = $otherUser;
        }

        return $conversations;
    }
}