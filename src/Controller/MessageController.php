<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\MessageFormType;
use App\Form\MessageType;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    private $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    private function getTypedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('User must be logged in and be an instance of App\Entity\User');
        }
        return $user;
    }

    #[Route('/', name: 'app_messages_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getTypedUser();
        $conversations = $this->messageService->getUserConversations($user);

        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_messages_conversation', methods: ['GET', 'POST'])]
    public function conversation(Request $request, User $otherUser): Response
    {
        $user = $this->getTypedUser();
        $messages = $this->messageService->getConversation($user, $otherUser);

        $this->messageService->markConversationAsRead($user, $otherUser);

        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->get('content')->getData();
            $this->messageService->sendMessage($user, $otherUser, $content);

            $this->addFlash('success', 'Message envoyé avec succès.');
            return $this->redirectToRoute('app_messages_conversation', ['id' => $otherUser->getId()]);
        }

        return $this->render('message/conversation.html.twig', [
            'messages' => $messages,
            'otherUser' => $otherUser,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new/{id}', name: 'app_messages_new', methods: ['GET', 'POST'])]
    public function new(Request $request, User $recipient): Response
    {
        $user = $this->getTypedUser();
        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->get('content')->getData();
            $this->messageService->sendMessage($user, $recipient, $content);

            $this->addFlash('success', 'Message envoyé avec succès.');
            return $this->redirectToRoute('app_messages_conversation', ['id' => $recipient->getId()]);
        }

        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
            'recipient' => $recipient,
        ]);
    }
}