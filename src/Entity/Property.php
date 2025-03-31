<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['property:read', 'property:item:read']],
        ),
        new Put(
            normalizationContext: ['groups' => ['property:read']],
            denormalizationContext: ['groups' => ['property:update']],
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_OWNER') and object.owner == user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_OWNER') and object.owner == user)"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['property:read']],
        ),
        new Post(
            normalizationContext: ['groups' => ['property:read']],
            denormalizationContext: ['groups' => ['property:write']],
            security: "is_granted('ROLE_OWNER')"
        ),
    ],
    normalizationContext: ['groups' => ['property:read']],
    denormalizationContext: ['groups' => ['property:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'description' => 'partial',
    'address' => 'partial',
    'propertyType.name' => 'exact',
    'owner.firstName' => 'partial',
    'owner.lastName' => 'partial'
])]
#[ApiFilter(RangeFilter::class, properties: ['basePrice', 'capacity'])]
#[ApiFilter(OrderFilter::class, properties: ['basePrice', 'createdAt', 'updatedAt'])]
#[ApiFilter(BooleanFilter::class, properties: ['isActive'])]
#[ORM\HasLifecycleCallbacks]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['property:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 255)]
    #[Groups(['property:read', 'property:write', 'property:update', 'booking:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 20)]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['property:read', 'property:write', 'property:update', 'booking:read'])]
    private ?string $basePrice = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private ?int $capacity = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private ?string $address = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private ?float $longitude = null;

    #[ORM\Column]
    #[Groups(['property:read', 'property:update'])]
    private ?bool $isActive = false;

    #[ORM\Column]
    #[Groups(['property:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['property:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['property:read', 'property:write', 'property:item:read'])]
    private ?User $owner = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private ?PropertyType $propertyType = null;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: PropertyImage::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    #[Assert\Valid]
    private Collection $images;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Booking::class, orphanRemoval: true)]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Availability::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    #[Assert\Valid]
    private Collection $availabilities;

    #[ORM\ManyToMany(targetEntity: Amenity::class)]
    #[ORM\JoinTable(name: 'property_amenity')]
    #[Groups(['property:read', 'property:write', 'property:update', 'property:item:read'])]
    private Collection $amenities;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Review::class, orphanRemoval: true)]
    #[Groups(['property:item:read'])]
    private Collection $reviews;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->availabilities = new ArrayCollection();
        $this->amenities = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getPropertyType(): ?PropertyType
    {
        return $this->propertyType;
    }

    public function setPropertyType(?PropertyType $propertyType): static
    {
        $this->propertyType = $propertyType;

        return $this;
    }

    /**
     * @return Collection<int, PropertyImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(PropertyImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProperty($this);
        }

        return $this;
    }

    public function removeImage(PropertyImage $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProperty() === $this) {
                $image->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setProperty($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getProperty() === $this) {
                $booking->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    public function addAvailability(Availability $availability): static
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
            $availability->setProperty($this);
        }

        return $this;
    }

    public function removeAvailability(Availability $availability): static
    {
        if ($this->availabilities->removeElement($availability)) {
            // set the owning side to null (unless already changed)
            if ($availability->getProperty() === $this) {
                $availability->setProperty(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Amenity>
     */
    public function getAmenities(): Collection
    {
        return $this->amenities;
    }

    public function addAmenity(Amenity $amenity): static
    {
        if (!$this->amenities->contains($amenity)) {
            $this->amenities->add($amenity);
        }

        return $this;
    }

    public function removeAmenity(Amenity $amenity): static
    {
        $this->amenities->removeElement($amenity);

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
            $review->setProperty($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getProperty() === $this) {
                $review->setProperty(null);
            }
        }

        return $this;
    }

    #[Groups(['property:read'])]
    public function getAverageRating(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }

        $sum = 0;
        $count = 0;

        foreach ($this->reviews as $review) {
            $sum += $review->getRating();
            $count++;
        }

        return $count > 0 ? $sum / $count : null;
    }

    #[Groups(['property:read'])]
    public function getReviewCount(): int
    {
        return $this->reviews->count();
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[Groups(['property:read'])]
    public function getFeaturedImage(): ?PropertyImage
    {
        foreach ($this->images as $image) {
            if ($image->isIsFeatured()) {
                return $image;
            }
        }

        return $this->images->isEmpty() ? null : $this->images->first();
    }
}