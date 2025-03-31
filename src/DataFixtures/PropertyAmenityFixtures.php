<?php
// src/DataFixtures/PropertyAmenityFixtures.php
namespace App\DataFixtures;

use App\Entity\PropertyAmenity;
use App\Entity\Property;
use App\Entity\Amenity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PropertyAmenityFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Tableau associatif pour lier les propriétés à leurs équipements
        $propertyAmenities = [
            0 => [0, 1, 4, 5, 8], // Yourte luxueuse: Wi-Fi, Parking, Chauffage, Cuisine, Vue panoramique
            1 => [0, 8, 11], // Cabane perchée: Wi-Fi, Vue panoramique, Cheminée
            // Ajoutez plus de liaisons selon vos propriétés
        ];

        foreach ($propertyAmenities as $propertyIndex => $amenityIndices) {
            foreach ($amenityIndices as $amenityIndex) {
                $propertyAmenity = new PropertyAmenity();
                $propertyAmenity->setProperty($this->getReference("property-{$propertyIndex}", Property::class));
                $propertyAmenity->setAmenity($this->getReference("amenity-{$amenityIndex}", Amenity::class));
                $propertyAmenity->setIsHighlighted($amenityIndex < 3); // Mettre en avant les 3 premiers équipements
                $manager->persist($propertyAmenity);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PropertyFixtures::class,
            AmenityFixtures::class,
        ];
    }
}