<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PropertyImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertyImageRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['property_image:read']],
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_OWNER') and object.property.owner == user)"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['property_image:read']],
        ),
        new Post(
            normalizationContext: ['groups' => ['property_image:read']],
            denormalizationContext: ['groups' => ['property_image:write']],
            security: "is_granted('ROLE_OWNER')",
            validationContext: ['groups' => ['Default', 'property_image:write']]
        ),
    ],
    normalizationContext: ['groups' => ['property_image:read']],
    denormalizationContext: ['groups' => ['property_image:write']]
)]
class PropertyImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['property_image:read', 'property:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['property_image:read', 'property:read'])]
    private ?string $path = null;

    #[Assert\NotBlank(groups: ['property_image:write'])]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        groups: ['property_image:write']
    )]
    #[Groups(['property_image:write'])]
    private ?File $file = null;

    #[ORM\Column]
    #[Groups(['property_image:read', 'property_image:write', 'property:read'])]
    private ?int $position = 0;

    #[ORM\Column]
    #[Groups(['property_image:read', 'property_image:write', 'property:read'])]
    private ?bool $isFeatured = false;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['property_image:read', 'property_image:write'])]
    private ?Property $property = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

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

    #[Groups(['property_image:read', 'property:read'])]
    public function getUrl(): string
    {
        return '/media/properties/' . $this->path;
    }
}