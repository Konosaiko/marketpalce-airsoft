<?php

namespace App\Service;

use AllowDynamicProperties;
use App\Entity\Listing;
use App\Entity\ListingPhoto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AllowDynamicProperties] class ListingService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        string $listingsPhotoDirectory,
        MessageService $messageService
    ) {
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
        $this->listingsPhotoDirectory = $listingsPhotoDirectory;
        $this->messageService = $messageService;
    }

    public function createListing(Listing $listing, User $user): Listing
    {
        $listing->setUser($user);
        $listing->setCreatedAt(new \DateTimeImmutable());

        $slug = $this->slugger->slug($listing->getTitle())->lower();
        $listing->setSlug($slug);

        $this->handlePhotoUploads($listing, $listing->getPhotoFiles());

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        return $listing;
    }

    public function updateListing(Listing $listing): void
    {
        $slug = $this->slugger->slug($listing->getTitle())->lower();
        $listing->setSlug($slug);

        $this->handlePhotoUploads($listing, $listing->getPhotoFiles());

        $this->entityManager->flush();
    }

    private function handlePhotoUploads(Listing $listing, ?array $photoFiles): void
    {
        if ($photoFiles) {
            foreach ($photoFiles as $photoFile) {
                if ($photoFile instanceof UploadedFile) {
                    $newFilename = $this->uploadPhoto($photoFile);

                    $photo = new ListingPhoto();
                    $photo->setFilename($newFilename);
                    $listing->addListingPhoto($photo);
                }
            }
        }
    }

    private function uploadPhoto(UploadedFile $photoFile): string
    {
        $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

        try {
            $photoFile->move($this->listingsPhotoDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Une erreur est survenue lors de l\'upload de l\'image.');
        }

        return $newFilename;
    }

    public function contactSeller(User $sender, Listing $listing, string $content): void
    {
        $recipient = $listing->getUser();

        if ($sender === $recipient) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous envoyer un message à vous-même.');
        }

        $this->messageService->sendMessage($sender, $recipient, $content);
    }
}