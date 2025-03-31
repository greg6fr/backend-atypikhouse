<?php
// src/DataFixtures/AvailabilityFixtures.php
namespace App\DataFixtures;

use App\Entity\Availability;
use App\Entity\Property;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AvailabilityFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Générer des disponibilités pour les 3 mois à venir
        $startDate = new \DateTime();
        $endDate = (new \DateTime())->modify('+3 months');

        // Pour chaque propriété
        for ($propertyIndex = 0; $propertyIndex < 2; $propertyIndex++) {
            $property = $this->getReference("property-{$propertyIndex}", Property::class);
            
            // Créer des périodes de disponibilité de 2 semaines
            $currentDate = clone $startDate;
            while ($currentDate < $endDate) {
                $periodEnd = (clone $currentDate)->modify('+14 days');
                
                $availability = new Availability();
                $availability->setProperty($property);
                $availability->setStartDate($currentDate);
                $availability->setEndDate($periodEnd);
                
                // Ajouter parfois un prix spécial
                if (rand(0, 1) === 1) {
                    $basePrice = (float) $property->getBasePrice();
                    $specialPrice = $basePrice * (rand(90, 120) / 100); // ±20% du prix de base
                    $availability->setSpecialPrice((string) $specialPrice);
                }
                
                $manager->persist($availability);
                
                // Passer à la période suivante (avec quelques jours d'intervalle)
                $currentDate = (clone $periodEnd)->modify('+3 days');
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PropertyFixtures::class,
        ];
    }
}