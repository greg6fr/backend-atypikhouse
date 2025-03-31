<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PropertyAmenityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyAmenityRepository::class)]
#[ORM\Table(name: 'prop_amenity_junction')]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['property_amenity:read']],
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['property_amenity:read']],
        ),
    ],
    normalizationContext: ['groups' => ['property_amenity:read']]
)]
class PropertyAmenity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['property_amenity:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'propertyAmenities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['property_amenity:read'])]
    private ?Property $property = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['property_amenity:read'])]
    private ?Amenity $amenity = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['property_amenity:read'])]
    private ?bool $isHighlighted = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['property_amenity:read'])]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;

        return $this;
    }

    public function getAmenity(): ?Amenity
    {
        return $this->amenity;
    }

    public function setAmenity(?Amenity $amenity): static
    {
        $this->amenity = $amenity;

        return $this;
    }

    public function isIsHighlighted(): ?bool
    {
        return $this->isHighlighted;
    }

    public function setIsHighlighted(?bool $isHighlighted): static
    {
        $this->isHighlighted = $isHighlighted;

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
}