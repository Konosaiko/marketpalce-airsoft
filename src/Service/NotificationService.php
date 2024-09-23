<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Create a new notification for a user.
     *
     * @param User $user The user to create the notification for
     * @param string $message The notification message
     * @param string|null $link An optional link associated with the notification
     */
    public function createNotification(User $user, string $message, ?string $link = null): void
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setLink($link);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    /**
     * Mark a notification as read.
     *
     * @param Notification $notification The notification to mark as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->setIsRead(true);
        $this->entityManager->flush();
    }

    /**
     * Get all unread notifications for a user.
     *
     * @param User $user The user to get unread notifications for
     * @return array The unread notifications
     */
    public function getUnreadNotifications(User $user): array
    {
        return $this->entityManager->getRepository(Notification::class)
            ->findBy(['user' => $user, 'isRead' => false], ['createdAt' => 'DESC']);
    }




}