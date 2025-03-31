<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessListener
{
    /**
     * Add additional data to the response on authentication success
     * 
     * This listener is triggered when a user successfully authenticates via JWT
     * It enriches the response with additional user information
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        // Only process if we have a valid user
        if (!$user instanceof UserInterface) {
            return;
        }

        // Add basic user data to the token response
        $data['user'] = [
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ];

        // Add profile information when available
        if ($user instanceof User) {
            $data['user']['firstName'] = $user->getFirstName();
            $data['user']['lastName'] = $user->getLastName();
            $data['user']['fullName'] = $user->getFullName();
            $data['user']['isVerified'] = $user->isVerified();
            
            // Add owner-specific information
            if ($user->isOwner()) {
                $data['user']['isOwner'] = true;
            }
            
            // Add admin-specific information
            if ($user->isAdmin()) {
                $data['user']['isAdmin'] = true;
            }
        } else {
            // Use generic method checks for non-App\Entity\User instances
            if (method_exists($user, 'getFirstName')) {
                $data['user']['firstName'] = $user->getFirstName();
            }

            if (method_exists($user, 'getLastName')) {
                $data['user']['lastName'] = $user->getLastName();
            }

            if (method_exists($user, 'isVerified')) {
                $data['user']['isVerified'] = $user->isVerified();
            }
        }

        // Update the response data
        $event->setData($data);
    }
}