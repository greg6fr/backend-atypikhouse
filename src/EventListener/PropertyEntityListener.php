<?php

namespace App\EventListener;

use App\Entity\Property;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class PropertyEntityListener
{
    /**
     * Called before a property is first persisted
     */
    public function prePersist(Property $property, PrePersistEventArgs $event): void
    {
        // Set creation and update timestamps if they don't exist
        if ($property->getCreatedAt() === null) {
            $property->setCreatedAt(new \DateTimeImmutable());
        }
        
        if ($property->getUpdatedAt() === null) {
            $property->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    /**
     * Called before a property is updated
     */
    public function preUpdate(Property $property, PreUpdateEventArgs $event): void
    {
        // Always update the updatedAt timestamp
        $property->setUpdatedAt(new \DateTimeImmutable());
    }
}