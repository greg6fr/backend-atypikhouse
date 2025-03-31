<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Add custom data to the JWT token payload
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Add user data to the token payload
        $payload['id'] = $user->getId();
        $payload['firstName'] = $user->getFirstName();
        $payload['lastName'] = $user->getLastName();
        $payload['isVerified'] = $user->isVerified();
        $payload['isOwner'] = $user->isOwner();
        $payload['isAdmin'] = $user->isAdmin();

        $event->setData($payload);

        // You can also customize the token TTL based on user roles
        $header = $event->getHeader();
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        // If user is accessing from a "Remember Me" feature, extend the token TTL
        $rememberMe = $request->headers->get('X-Remember-Me') === 'true';
        if ($rememberMe) {
            $expiration = new \DateTime('+1 week');
            $expiration = $expiration->getTimestamp();
            $payload['exp'] = $expiration;
        }

        $event->setData($payload);
    }
}