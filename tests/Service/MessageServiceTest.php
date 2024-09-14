<?php

namespace App\Tests\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MessageServiceTest extends TestCase
{
    private $entityManager;
    private $messageRepository;
    private $messageService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageRepository = $this->createMock(MessageRepository::class);
        $this->messageService = new MessageService($this->entityManager, $this->messageRepository);
    }

    public function testSendMessage()
    {
        $sender = new User();
        $recipient = new User();
        $content = "Test message";

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Message::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $message = $this->messageService->sendMessage($sender, $recipient, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($sender, $message->getSender());
        $this->assertEquals($recipient, $message->getRecipient());
        $this->assertEquals($content, $message->getContent());
        $this->assertFalse($message->isRead());
    }

    public function testMarkAsRead()
    {
        $message = new Message();
        $message->setIsRead(false);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->messageService->markAsRead($message);

        $this->assertTrue($message->isRead());
    }

    public function testGetConversation()
    {
        $user1 = new User();
        $user2 = new User();
        $expectedMessages = [new Message(), new Message()];

        $this->messageRepository->expects($this->once())
            ->method('findConversation')
            ->with($user1, $user2)
            ->willReturn($expectedMessages);

        $conversation = $this->messageService->getConversation($user1, $user2);

        $this->assertEquals($expectedMessages, $conversation);
    }

    public function testGetUnreadMessages()
    {
        $user = new User();
        $expectedMessages = [new Message(), new Message()];

        $this->messageRepository->expects($this->once())
            ->method('findUnreadMessages')
            ->with($user)
            ->willReturn($expectedMessages);

        $unreadMessages = $this->messageService->getUnreadMessages($user);

        $this->assertEquals($expectedMessages, $unreadMessages);
    }
}