<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BreedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BreedRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['breed:lire', 'country:lire', 'breedgroup:lire', 'temperament:lire']],
    denormalizationContext: ['groups' => ['breed:ecrire']],
)]
class Breed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['breed:lire'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $catApiId = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['breed:lire', 'breed:ecrire'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['breed:lire', 'breed:ecrire'])]
    private ?string $lifeSpan = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['breed:lire', 'breed:ecrire'])]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['breed:lire', 'breed:ecrire'])]
    #[Assert\Length(max: 2000)]
    private ?string $history = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['breed:lire'])]
    #[Assert\Range(min: 0.5, max: 20)]
    private ?int $weight = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['breed:lire'])]
    #[Assert\Range(min: 10, max: 100)]
    private ?int $height = null;

    #[ORM\ManyToOne(inversedBy: 'breeds')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['breed:lire', 'breed:ecrire'])]
    private ?Country $country_id = null;

    #[ORM\ManyToOne(inversedBy: 'breeds')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['breed:lire', 'breed:ecrire'])]
    private ?Group $group_id = null;

    /**
     * @var Collection<int, Temperament>
     */
    #[ORM\ManyToMany(targetEntity: Temperament::class, inversedBy: 'breeds')]
    #[Groups(['breed:lire', 'breed:ecrire'])]
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

    public function setLifeSpan(?string $lifeSpan): static
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

    public function setWeight(?int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
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
