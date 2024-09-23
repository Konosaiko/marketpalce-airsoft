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



    /**
     * Update a user's profile information.
     *
     * @param User $user The user whose profile is being updated
     * @param User $updatedData The new user data
     * @param string|null $newPassword The new password, if being changed
     */
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

    /**
     * Get all listings for a user.
     *
     * @param User $user The user to get listings for
     * @return array The user's listings
     */
    public function getUserListings(User $user): array
    {
        return $user->getSells()->toArray();
    }
}