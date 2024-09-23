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


    /**
     * Create a new listing.
     *
     * @param Listing $listing The listing to create
     * @param User $user The user creating the listing
     * @return Listing The created listing
     */
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