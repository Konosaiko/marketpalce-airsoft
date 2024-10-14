<?php

namespace App\Service;

use AllowDynamicProperties;
use App\Entity\Listing;
use App\Entity\ListingPhoto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

#[AllowDynamicProperties]
class ListingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private string $listingsPhotoDirectory,
        private MessageService $messageService,
        private LoggerInterface $logger
    ) {}

    public function createListing(Listing $listing, User $user): Listing
    {
        $this->logger->info('Création d\'une nouvelle annonce', ['user_id' => $user->getId()]);

        $listing->setUser($user);
        $listing->setCreatedAt(new \DateTimeImmutable());

        $slug = $this->slugger->slug($listing->getTitle())->lower();
        $listing->setSlug($slug);

        $this->handlePhotoUploads($listing, $listing->getPhotoFiles());

        try {
            $this->entityManager->persist($listing);
            $this->entityManager->flush();
            $this->logger->info('Annonce créée avec succès', ['listing_id' => $listing->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'annonce', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }

        return $listing;
    }

    private function handlePhotoUploads(Listing $listing, ?array $photoFiles): void
    {
        if ($photoFiles) {
            foreach ($photoFiles as $photoFile) {
                if ($photoFile instanceof UploadedFile) {
                    try {
                        $newFilename = $this->uploadPhoto($photoFile);
                        $photo = new ListingPhoto();
                        $photo->setFilename($newFilename);
                        $listing->addListingPhoto($photo);
                    } catch (RuntimeException $e) {
                        $this->logger->warning('Échec de l\'upload d\'une photo', [
                            'listing_id' => $listing->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }
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
            return $newFilename;
        } catch (FileException $e) {
            $this->logger->error('Erreur lors de l\'upload de l\'image', [
                'filename' => $newFilename,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Une erreur est survenue lors de l\'upload de l\'image.');
        }
    }

    /**
     * Update an existing listing.
     *
     * @param Listing $listing The listing to update
     */
    public function updateListing(Listing $listing): void
    {
        $slug = $this->slugger->slug($listing->getTitle())->lower();
        $listing->setSlug($slug);

        $this->handlePhotoUploads($listing, $listing->getPhotoFiles());

        $this->entityManager->flush();
    }


    /**
     * Allow a user to contact the seller of a listing.
     *
     * @param User $sender The user sending the message
     * @param Listing $listing The listing being inquired about
     * @param string $content The content of the message
     * @throws InvalidArgumentException If the sender tries to message themselves
     */
    public function contactSeller(User $sender, Listing $listing, string $content): void
    {
        $recipient = $listing->getUser();

        if ($sender === $recipient) {
            throw new InvalidArgumentException('Vous ne pouvez pas vous envoyer un message à vous-même.');
        }

        $this->messageService->sendMessage($sender, $recipient, $content);
    }
}