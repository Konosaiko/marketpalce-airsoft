<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, Listing>
     */
    #[ORM\ManyToMany(targetEntity: Listing::class, inversedBy: 'categories')]
    private Collection $sell;

    public function __construct()
    {
        $this->sell = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * @return Collection<int, Listing>
     */
    public function getSell(): Collection
    {
        return $this->sell;
    }

    public function addSell(Listing $sell): static
    {
        if (!$this->sell->contains($sell)) {
            $this->sell->add($sell);
        }

        return $this;
    }

    public function removeSell(Listing $sell): static
    {
        $this->sell->removeElement($sell);

        return $this;
    }
}
