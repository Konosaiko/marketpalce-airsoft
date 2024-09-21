<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications')]
    public function index(NotificationService $notificationService): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à vos notifications.');
        }

        $notifications = $notificationService->getUnreadNotifications($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/mark-as-read', name: 'app_notification_mark_as_read')]
    public function markAsRead(Notification $notification, NotificationService $notificationService): Response
    {
        $notificationService->markAsRead($notification);

        return $this->redirectToRoute('app_notifications');
    }
}