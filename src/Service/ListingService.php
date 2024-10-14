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
    private $logger;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        private string $listingsPhotoDirectory,
        private MessageService $messageService,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }


    /**
     * Create a new listing.
     *
     * @param Listing $listing The listing to create
     * @param User $user The user creating the listing
     * @return Listing The created listing
     */
    public function createListing(Listing $listing, User $user): Listing
    {
        $this->logger->info('Début de createListing dans ListingService');

        $listing->setUser($user);
        $listing->setCreatedAt(new \DateTimeImmutable());

        $slug = $this->slugger->slug($listing->getTitle())->lower();
        $listing->setSlug($slug);

        $this->logger->info('Avant handlePhotoUploads');
        $this->handlePhotoUploads($listing, $listing->getPhotoFiles());
        $this->logger->info('Après handlePhotoUploads');

        try {
            $this->entityManager->persist($listing);
            $this->entityManager->flush();
            $this->logger->info('Annonce persistée avec succès', ['listing_id' => $listing->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la persistance de l\'annonce', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $listing;
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
     * Handle the upload of photos for a listing.
     *
     * @param Listing $listing The listing to add photos to
     * @param array|null $photoFiles The array of uploaded photo files
     */
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

    /**
     * Upload a single photo file.
     *
     * @param UploadedFile $photoFile The file to upload
     * @return string The new filename of the uploaded photo
     * @throws RuntimeException If there's an error during upload
     */
    private function uploadPhoto(UploadedFile $photoFile): string
    {
        $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

        try {
            $photoFile->move($this->listingsPhotoDirectory, $newFilename);
        } catch (FileException $e) {
            throw new RuntimeException('Une erreur est survenue lors de l\'upload de l\'image.');
        }

        return $newFilename;
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