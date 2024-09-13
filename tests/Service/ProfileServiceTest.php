<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Listing;
use App\Service\ProfileService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileServiceTest extends TestCase
{
    private $entityManager;
    private $passwordHasher;
    private $profileService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->profileService = new ProfileService($this->entityManager, $this->passwordHasher);
    }

    public function testUpdateProfileWithoutPassword()
    {
        $user = new User();
        $user->setUsername('oldUsername');
        $user->setEmail('old@email.com');

        $updatedData = new User();
        $updatedData->setUsername('newUsername');
        $updatedData->setEmail('new@email.com');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->profileService->updateProfile($user, $updatedData);

        $this->assertEquals('newUsername', $user->getUsername());
        $this->assertEquals('new@email.com', $user->getEmail());
    }

    public function testUpdateProfileWithPassword()
    {
        $user = new User();
        $user->setUsername('oldUsername');
        $user->setEmail('old@email.com');
        $user->setPassword('oldHashedPassword');

        $updatedData = new User();
        $updatedData->setUsername('newUsername');
        $updatedData->setEmail('new@email.com');

        $newPassword = 'newPassword';
        $newHashedPassword = 'newHashedPassword';

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $newPassword)
            ->willReturn($newHashedPassword);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->profileService->updateProfile($user, $updatedData, $newPassword);

        $this->assertEquals('newUsername', $user->getUsername());
        $this->assertEquals('new@email.com', $user->getEmail());
        $this->assertEquals($newHashedPassword, $user->getPassword());
    }

    public function testGetUserListings()
    {
        $user = new User();
        $listing1 = new Listing();
        $listing2 = new Listing();

        $user->addSell($listing1);
        $user->addSell($listing2);

        $result = $this->profileService->getUserListings($user);

        $this->assertCount(2, $result);
        $this->assertContains($listing1, $result);
        $this->assertContains($listing2, $result);
    }
}