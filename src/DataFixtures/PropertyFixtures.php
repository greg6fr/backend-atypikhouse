<?php
// src/DataFixtures/PropertyFixtures.php
namespace App\DataFixtures;

use App\Entity\Property;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PropertyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $properties = [
            [
                'title' => 'Yourte luxueuse en pleine nature',
                'description' => 'Magnifique yourte tout confort avec vue panoramique sur les montagnes...',
                'basePrice' => '120.00',
                'capacity' => 4,
                'address' => '123 Chemin des Yourtes, 74000 Annecy, France',
                'latitude' => 45.899247,
                'longitude' => 6.129384,
                'type' => 0, // Yourte (index de référence)
                'owner' => 1  // Owner-1 (index de référence)
            ],
            [
                'title' => 'Cabane perchée à 10m de hauteur',
                'description' => 'Vivez une expérience unique dans notre cabane perchée à 10 mètres de hauteur...',
                'basePrice' => '150.00',
                'capacity' => 2,
                'address' => '456 Route Forestière, 67000 Strasbourg, France',
                'latitude' => 48.584614,
                'longitude' => 7.750713,
                'type' => 1, // Cabane dans les arbres (index de référence)
                'owner' => 2  // Owner-2 (index de référence)
            ],
        ];

        foreach ($properties as $index => $propertyData) {
            $property = new Property();
            $property->setTitle($propertyData['title']);
            $property->setDescription($propertyData['description']);
            $property->setBasePrice($propertyData['basePrice']);
            $property->setCapacity($propertyData['capacity']);
            $property->setAddress($propertyData['address']);
            $property->setLatitude($propertyData['latitude']);
            $property->setLongitude($propertyData['longitude']);
            $property->setIsActive(true);
            
            // Utiliser cette syntaxe pour getReference avec le bon nombre d'arguments
            $propertyType = $this->getReference('property-type-' . $propertyData['type'], \App\Entity\PropertyType::class);
            $property->setPropertyType($propertyType);
            
            $owner = $this->getReference('owner-' . $propertyData['owner'], \App\Entity\User::class);
            $property->setOwner($owner);
            
            $manager->persist($property);
            $this->addReference('property-' . $index, $property);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PropertyTypeFixtures::class,
        ];
    }
}