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
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['booking:read', 'booking:item:read']],
            security: "is_granted('ROLE_ADMIN') or object.getTenant() == user or object.getProperty().getOwner() == user"
        ),
        new Put(
            normalizationContext: ['groups' => ['booking:read']],
            denormalizationContext: ['groups' => ['booking:update']],
            security: "is_granted('ROLE_ADMIN') or object.getTenant() == user or object.getProperty().getOwner() == user"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['booking:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            normalizationContext: ['groups' => ['booking:read']],
            denormalizationContext: ['groups' => ['booking:write']],
            security: "is_granted('ROLE_USER')"
        ),
    ],
    normalizationContext: ['groups' => ['booking:read']],
    denormalizationContext: ['groups' => ['booking:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'property.id' => 'exact',
    'tenant.id' => 'exact',
    'status' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: ['checkInDate', 'checkOutDate'])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'checkInDate', 'totalPrice'])]
#[ORM\HasLifecycleCallbacks]
class Booking
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['booking:read', 'booking:write', 'booking:item:read'])]
    private ?Property $property = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['booking:read', 'booking:write', 'booking:item:read'])]
    private ?User $tenant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThanOrEqual("today")]
    #[Groups(['booking:read', 'booking:write', 'booking:update', 'booking:item:read'])]
    private ?\DateTimeInterface $checkInDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: "checkInDate")]
    #[Groups(['booking:read', 'booking:write', 'booking:update', 'booking:item:read'])]
    private ?\DateTimeInterface $checkOutDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['booking:read', 'booking:write', 'booking:item:read'])]
    private ?string $totalPrice = null;

    #[ORM\Column(length: 20)]
    #[Groups(['booking:read', 'booking:update', 'booking:item:read'])]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['booking:read', 'booking:item:read'])]
    private ?string $transactionId = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'booking', targetEntity: Review::class)]
    #[Groups(['booking:item:read'])]
    private Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getTenant(): ?User
    {
        return $this->tenant;
    }

    public function setTenant(?User $tenant): static
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getCheckInDate(): ?\DateTimeInterface
    {
        return $this->checkInDate;
    }

    public function setCheckInDate(\DateTimeInterface $checkInDate): static
    {
        $this->checkInDate = $checkInDate;

        return $this;
    }

    public function getCheckOutDate(): ?\DateTimeInterface
    {
        return $this->checkOutDate;
    }

    public function setCheckOutDate(\DateTimeInterface $checkOutDate): static
    {
        $this->checkOutDate = $checkOutDate;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;

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

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setBooking($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getBooking() === $this) {
                $review->setBooking(null);
            }
        }

        return $this;
    }

    #[Groups(['booking:read'])]
    public function getNights(): int
    {
        if (!$this->checkInDate || !$this->checkOutDate) {
            return 0;
        }

        $interval = $this->checkInDate->diff($this->checkOutDate);
        return (int) $interval->format('%a');
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED])
            && $this->checkInDate > new \DateTime();
    }

    #[Groups(['booking:read'])]
    public function getIsCancellable(): bool
    {
        return $this->canCancel();
    }

    public function canReview(): bool
    {
        return $this->status === self::STATUS_COMPLETED 
            && $this->checkOutDate < new \DateTime()
            && $this->reviews->isEmpty();
    }

    #[Groups(['booking:read'])]
    public function getIsReviewable(): bool
    {
        return $this->canReview();
    }
}