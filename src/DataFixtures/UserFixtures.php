<?php
// src/DataFixtures/UserFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un admin
        $admin = new User();
        $admin->setEmail('admin@atypikhouse.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $manager->persist($admin);
        $this->addReference('admin-user', $admin);

        // Créer quelques propriétaires
        for ($i = 1; $i <= 5; $i++) {
            $owner = new User();
            $owner->setEmail("owner{$i}@atypikhouse.com");
            $owner->setFirstName("Owner{$i}");
            $owner->setLastName("User");
            $owner->setRoles(['ROLE_OWNER', 'ROLE_USER']);
            $owner->setPassword($this->passwordHasher->hashPassword($owner, 'owner123'));
            $owner->setIsVerified(true);
            $manager->persist($owner);
            $this->addReference("owner-{$i}", $owner);
        }

        // Créer quelques locataires
        for ($i = 1; $i <= 10; $i++) {
            $tenant = new User();
            $tenant->setEmail("tenant{$i}@atypikhouse.com");
            $tenant->setFirstName("Tenant{$i}");
            $tenant->setLastName("User");
            $tenant->setRoles(['ROLE_USER']);
            $tenant->setPassword($this->passwordHasher->hashPassword($tenant, 'tenant123'));
            $tenant->setIsVerified(true);
            $manager->persist($tenant);
            $this->addReference("tenant-{$i}", $tenant);
        }

        $manager->flush();
    }
}