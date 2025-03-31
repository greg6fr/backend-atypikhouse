<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['review:read']],
        ),
        new Put(
            normalizationContext: ['groups' => ['review:read']],
            denormalizationContext: ['groups' => ['review:update']],
            security: "is_granted('ROLE_ADMIN') or (object.booking.tenant == user and object.createdAt > date('-1 day'))"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['review:read']],
        ),
        new Post(
            normalizationContext: ['groups' => ['review:read']],
            denormalizationContext: ['groups' => ['review:write']],
            security: "is_granted('ROLE_USER')"
        ),
    ],
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'property.id' => 'exact',
    'booking.tenant.id' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'rating'])]
#[ApiFilter(BooleanFilter::class, properties: ['isModerated'])]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['review:read', 'property:item:read', 'booking:item:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['review:read', 'review:write'])]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['review:read', 'booking:item:read'])]
    private ?Property $property = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['review:read', 'review:write', 'review:update', 'property:item:read', 'booking:item:read'])]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 1000)]
    #[Groups(['review:read', 'review:write', 'review:update', 'property:item:read', 'booking:item:read'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['review:read', 'property:item:read', 'booking:item:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['review:read', 'review:update', 'property:item:read'])]
    private ?bool $isModerated = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->booking && !$this->property) {
            $this->property = $this->booking->getProperty();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

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

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

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

    public function isIsModerated(): ?bool
    {
        return $this->isModerated;
    }

    public function setIsModerated(bool $isModerated): static
    {
        $this->isModerated = $isModerated;

        return $this;
    }

    #[Groups(['review:read', 'property:item:read', 'booking:item:read'])]
    public function getAuthor(): ?User
    {
        return $this->booking?->getTenant();
    }
}