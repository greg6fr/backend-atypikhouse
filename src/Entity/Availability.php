<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AvailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['availability:read']],
        ),
        new Put(
            normalizationContext: ['groups' => ['availability:read']],
            denormalizationContext: ['groups' => ['availability:update']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_OWNER') and object.property.owner == user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_OWNER') and object.property.owner == user)"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['availability:read']],
        ),
        new Post(
            normalizationContext: ['groups' => ['availability:read']],
            denormalizationContext: ['groups' => ['availability:write']],
            security: "is_granted('ROLE_OWNER') and object.property.owner == user"
        ),
    ],
    normalizationContext: ['groups' => ['availability:read']],
    denormalizationContext: ['groups' => ['availability:write']]
)]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['availability:read', 'property:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThanOrEqual("today")]
    #[Groups(['availability:read', 'availability:write', 'availability:update', 'property:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: "startDate")]
    #[Groups(['availability:read', 'availability:write', 'availability:update', 'property:read'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['availability:read', 'availability:write', 'availability:update', 'property:read'])]
    private ?string $specialPrice = null;

    #[ORM\ManyToOne(inversedBy: 'availabilities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['availability:read', 'availability:write'])]
    private ?Property $property = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getSpecialPrice(): ?string
    {
        return $this->specialPrice;
    }

    public function setSpecialPrice(?string $specialPrice): static
    {
        $this->specialPrice = $specialPrice;

        return $this;
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

    #[Groups(['availability:read', 'property:read'])]
    public function getPricePerNight(): ?string
    {
        return $this->specialPrice ?? $this->property?->getBasePrice();
    }
}