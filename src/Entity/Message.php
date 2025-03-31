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
use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['message:read']],
            security: "is_granted('ROLE_ADMIN') or object.sender == user or object.receiver == user"
        ),
        new Put(
            normalizationContext: ['groups' => ['message:read']],
            denormalizationContext: ['groups' => ['message:update']],
            security: "is_granted('ROLE_ADMIN') or object.receiver == user"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['message:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            normalizationContext: ['groups' => ['message:read']],
            denormalizationContext: ['groups' => ['message:write']],
            security: "is_granted('ROLE_USER')"
        ),
    ],
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'sender.id' => 'exact',
    'receiver.id' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['sentAt'])]
#[ApiFilter(BooleanFilter::class, properties: ['isRead'])]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    private ?User $sender = null;

    #[ORM\ManyToOne(inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['message:read', 'message:write'])]
    private ?User $receiver = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 2000)]
    #[Groups(['message:read', 'message:write'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['message:read'])]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column]
    #[Groups(['message:read', 'message:update'])]
    private ?bool $isRead = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?string $subject = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?int $propertyId = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['message:read', 'message:write'])]
    private ?int $bookingId = null;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getPropertyId(): ?int
    {
        return $this->propertyId;
    }

    public function setPropertyId(?int $propertyId): static
    {
        $this->propertyId = $propertyId;

        return $this;
    }

    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    public function setBookingId(?int $bookingId): static
    {
        $this->bookingId = $bookingId;

        return $this;
    }
}