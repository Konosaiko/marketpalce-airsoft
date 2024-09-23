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

    /**
     * Send a message from one user to another.
     *
     * @param User $sender The user sending the message
     * @param User $recipient The user receiving the message
     * @param string $content The content of the message
     * @return Message The sent message
     */
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

    /**
     * Mark a message as read.
     *
     * @param Message $message The message to mark as read
     */
    public function markAsRead(Message $message): void
    {
        $message->setIsRead(true);
        $this->entityManager->flush();
    }

    /**
     * Get the conversation between two users.
     *
     * @param User $user1 The first user
     * @param User $user2 The second user
     * @return array The messages in the conversation
     */
    public function getConversation(User $user1, User $user2): array
    {
        return $this->messageRepository->findConversation($user1, $user2);
    }

    /**
     * Get all unread messages for a user.
     *
     * @param User $user The user to get unread messages for
     * @return array The unread messages
     */
    public function getUnreadMessages(User $user): array
    {
        return $this->messageRepository->findUnreadMessages($user);
    }

    /**
     * Delete a message.
     *
     * @param Message $message The message to delete
     */
    public function deleteMessage(Message $message): void
    {
        $this->entityManager->remove($message);
        $this->entityManager->flush();
    }


    /**
     * Get all conversations for a user.
     *
     * @param User $user The user to get conversations for
     * @return array The conversations, indexed by the other user's ID
     */
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

    /**
     * Mark all messages in a conversation as read.
     *
     * @param User $user The user marking the messages as read
     * @param User $otherUser The other user in the conversation
     */
    public function markConversationAsRead(User $user, User $otherUser): void
    {
        $unreadMessages = $this->messageRepository->findUnreadMessagesInConversation($user, $otherUser);
        foreach ($unreadMessages as $message) {
            $message->setIsRead(true);
        }
        $this->entityManager->flush();
    }
}