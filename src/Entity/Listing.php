<?php

namespace App\Entity;

use App\Repository\ListingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ListingRepository::class)]
class Listing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["listing:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["listing:read"])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["listing:read"])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(["listing:read"])]
    private ?float $price = null;

    #[ORM\Column(name: "created_at", type: "datetime_immutable")]
    #[Groups(["listing:read"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'sells')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["listing:read"])]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Groups(["listing:read"])]
    private ?string $state = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'listings')]
    #[Groups(["listing:read"])]
    private Collection $categories;

    #[ORM\Column(length: 255)]
    #[Groups(["listing:read"])]
    private ?string $slug = null;

    #[ORM\OneToMany(targetEntity: ListingPhoto::class, mappedBy: 'listing', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(["listing:read"])]
    private Collection $listingPhotos;

    private ?array $photoFiles = null;

    #[ORM\Column(length: 255)]
    #[Groups(["listing:read"])]
    private ?string $region = null;

    #[ORM\Column(length: 255)]
    #[Groups(["listing:read"])]
    private ?string $department = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->listingPhotos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addListing($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeListing($this);
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function computeSlug(SluggerInterface $slugger): void
    {
        if (!$this->slug || '-' === $this->slug) {
            $this->slug = (string) $slugger->slug((string) $this->getTitle())->lower();
        }
    }

    /**
     * @return Collection<int, ListingPhoto>
     */
    public function getListingPhotos(): Collection
    {
        return $this->listingPhotos;
    }

    public function addListingPhoto(ListingPhoto $listingPhoto): static
    {
        if (!$this->listingPhotos->contains($listingPhoto)) {
            $this->listingPhotos->add($listingPhoto);
            $listingPhoto->setListing($this);
        }

        return $this;
    }

    public function removeListingPhoto(ListingPhoto $listingPhoto): static
    {
        if ($this->listingPhotos->removeElement($listingPhoto)) {
            // set the owning side to null (unless already changed)
            if ($listingPhoto->getListing() === $this) {
                $listingPhoto->setListing(null);
            }
        }

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getPhotoFiles(): ?array
    {
        return $this->photoFiles;
    }

    public function setPhotoFiles(?array $photoFiles): self
    {
        $this->photoFiles = $photoFiles;
        return $this;
    }
}
