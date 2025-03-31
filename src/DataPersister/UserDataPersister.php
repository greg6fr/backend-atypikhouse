<?php

namespace App\DataPersister;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements ProcessorInterface
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Si ce n'est pas un User, on ne fait rien
        if (!$data instanceof User) {
            return $data;
        }

        // Hash the plain password if it exists
        if ($data->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->getPlainPassword()
            );
            $data->setPassword($hashedPassword);
            $data->eraseCredentials();
        }

        // Handle new users
        if (!$data->getId()) {
            // Make sure new users have at least ROLE_USER
            $roles = $data->getRoles();
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
                $data->setRoles($roles);
            }

            // Set creation date for new users
            if (!$data->getCreatedAt()) {
                $data->setCreatedAt(new \DateTimeImmutable());
            }
        }

        // Always update the updatedAt timestamp
        $data->setUpdatedAt(new \DateTimeImmutable());

        // Persist the entity
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}