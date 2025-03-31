<?php

namespace App\DataPersister;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\PropertyImage;
use App\Service\File\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PropertyImageDataPersister implements ProcessorInterface
{
    private EntityManagerInterface $entityManager;
    private FileUploader $fileUploader;

    public function __construct(EntityManagerInterface $entityManager, FileUploader $fileUploader)
    {
        $this->entityManager = $entityManager;
        $this->fileUploader = $fileUploader;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PropertyImage
    {
        // Si ce n'est pas une PropertyImage, on ne fait rien
        if (!$data instanceof PropertyImage) {
            return $data;
        }
        
        // Handle file upload
        if ($data->getFile() instanceof UploadedFile) {
            $propertyId = $data->getProperty()->getId();
            $fileName = $this->fileUploader->uploadPropertyImage($data->getFile(), $propertyId);
            $data->setPath($fileName);
        }
        
        // Make sure only one image is set as featured
        if ($data->isIsFeatured()) {
            $property = $data->getProperty();
            foreach ($property->getImages() as $image) {
                if ($image !== $data && $image->isIsFeatured()) {
                    $image->setIsFeatured(false);
                }
            }
        }
        
        // Ensure a position is set
        if ($data->getPosition() === 0) {
            $property = $data->getProperty();
            $maxPosition = 0;
            
            foreach ($property->getImages() as $image) {
                if ($image !== $data && $image->getPosition() > $maxPosition) {
                    $maxPosition = $image->getPosition();
                }
            }
            
            $data->setPosition($maxPosition + 1);
        }
        
        // If this is the first image, set it as featured
        if ($data->getProperty()->getImages()->count() === 0) {
            $data->setIsFeatured(true);
        }
        
        $this->entityManager->persist($data);
        $this->entityManager->flush();
        
        return $data;
    }
}