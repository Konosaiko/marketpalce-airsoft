<?php

namespace App\Service;

use AllowDynamicProperties;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AllowDynamicProperties] class MessageService
{
    private $entityManager;
    private $userRepository;
    private $messageRepository;

    private $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository, NotificationService $notificationService, UserRepository $userRepository, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->urlGenerator = $urlGenerator;

    }

    public function sendMessage(User $sender, User $recipient, string $content): Message
    {
        $message = new Message();
        $message->setSender($sender);
        $message->setRecipient($recipient);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Créer une notification pour le destinataire
        $notificationMessage = "Vous avez reçu un nouveau message de {$sender->getUsername()}.";
        $notificationLink = $this->urlGenerator->generate('app_messages_conversation', [
            'id' => $recipient->getId()
        ]);
        $this->notificationService->createNotification($recipient, $notificationMessage, $notificationLink);

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
        $conversationUserIds = $this->messageRepository->findUserConversations($user);
        $conversations = [];

        foreach ($conversationUserIds as $result) {
            $otherUserId = $result['otherUserId'];
            $otherUser = $this->userRepository->find($otherUserId);
            if ($otherUser) {
                $conversations[$otherUserId] = $otherUser;
            }
        }

        return $conversations;
    }

    public function markConversationAsRead(User $user, User $otherUser): void
    {
        $unreadMessages = $this->messageRepository->findUnreadMessagesInConversation($user, $otherUser);
        foreach ($unreadMessages as $message) {
            $message->setIsRead(true);
        }
        $this->entityManager->flush();
    }
}