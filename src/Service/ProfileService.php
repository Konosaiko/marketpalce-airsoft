<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileService
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function updateProfile(User $user, User $updatedData, ?string $newPassword = null): void
    {
        $user->setUsername($updatedData->getUsername());
        $user->setEmail($updatedData->getEmail());

        if ($newPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();
    }

    public function getUserListings(User $user): array
    {
        return $user->getSells()->toArray();
    }
}