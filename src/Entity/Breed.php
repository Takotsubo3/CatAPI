<?php

namespace App\Entity;

use App\Repository\BreedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: BreedRepository::class)]
#[ApiResource]

class Breed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $catApiId = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    private ?string $lifeSpan = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $history = null;

    #[ORM\Column]
    private ?int $weight = null;

    #[ORM\Column]
    private ?int $height = null;

    #[ORM\ManyToOne(inversedBy: 'breeds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Country $country_id = null;

    #[ORM\ManyToOne(inversedBy: 'breeds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group_id = null;

    /**
     * @var Collection<int, Temperament>
     */
    #[ORM\ManyToMany(targetEntity: Temperament::class, inversedBy: 'breeds')]
    private Collection $temperaments;

    public function __construct()
    {
        $this->temperaments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCatApiId(): ?string
    {
        return $this->catApiId;
    }

    public function setCatApiId(string $catApiId): static
    {
        $this->catApiId = $catApiId;

        return $this;
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

    public function getLifeSpan(): ?string
    {
        return $this->lifeSpan;
    }

    public function setLifeSpan(string $lifeSpan): static
    {
        $this->lifeSpan = $lifeSpan;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getHistory(): ?string
    {
        return $this->history;
    }

    public function setHistory(?string $history): static
    {
        $this->history = $history;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getCountryId(): ?Country
    {
        return $this->country_id;
    }

    public function setCountryId(?Country $country_id): static
    {
        $this->country_id = $country_id;

        return $this;
    }

    public function getGroupId(): ?Group
    {
        return $this->group_id;
    }

    public function setGroupId(?Group $group_id): static
    {
        $this->group_id = $group_id;

        return $this;
    }

    /**
     * @return Collection<int, Temperament>
     */
    public function getTemperaments(): Collection
    {
        return $this->temperaments;
    }

    public function addTemperament(Temperament $temperament): static
    {
        if (!$this->temperaments->contains($temperament)) {
            $this->temperaments->add($temperament);
        }

        return $this;
    }

    public function removeTemperament(Temperament $temperament): static
    {
        $this->temperaments->removeElement($temperament);

        return $this;
    }
}
