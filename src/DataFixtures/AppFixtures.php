<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Cette fixture ne fait rien directement, elle est juste un point d'entrée
        // pour charger toutes les autres fixtures dans le bon ordre
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PropertyTypeFixtures::class,
            AmenityFixtures::class,
            PropertyFixtures::class,
            PropertyAmenityFixtures::class,
            PropertyImageFixtures::class,
            AvailabilityFixtures::class,
            BookingFixtures::class,
            ReviewFixtures::class,
            MessageFixtures::class,
        ];
    }
}