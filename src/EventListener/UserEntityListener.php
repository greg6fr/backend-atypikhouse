<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class UserEntityListener
{
    /**
     * Called before a user is first persisted
     */
    public function prePersist(User $user, PrePersistEventArgs $event): void
    {
        // Set creation and update timestamps if they don't exist
        if ($user->getCreatedAt() === null) {
            $user->setCreatedAt(new \DateTimeImmutable());
        }
        
        if ($user->getUpdatedAt() === null) {
            $user->setUpdatedAt(new \DateTimeImmutable());
        }
        
        // Ensure new users have at least ROLE_USER
        $roles = $user->getRoles();
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
            $user->setRoles($roles);
        }
    }

    /**
     * Called before a user is updated
     */
    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        // Always update the updatedAt timestamp
        $user->setUpdatedAt(new \DateTimeImmutable());
    }
}