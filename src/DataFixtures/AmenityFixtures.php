<?php
// src/DataFixtures/AmenityFixtures.php
namespace App\DataFixtures;

use App\Entity\Amenity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AmenityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $amenities = [
            ['name' => 'Wi-Fi', 'icon' => 'fa-wifi', 'description' => 'Connexion internet sans fil'],
            ['name' => 'Parking', 'icon' => 'fa-car', 'description' => 'Espace de stationnement privé'],
            ['name' => 'Piscine', 'icon' => 'fa-swimming-pool', 'description' => 'Piscine privée ou partagée'],
            ['name' => 'Climatisation', 'icon' => 'fa-snowflake', 'description' => 'Système de climatisation'],
            ['name' => 'Chauffage', 'icon' => 'fa-fire', 'description' => 'Système de chauffage'],
            ['name' => 'Cuisine équipée', 'icon' => 'fa-utensils', 'description' => 'Cuisine avec équipements'],
            ['name' => 'Terrasse', 'icon' => 'fa-umbrella-beach', 'description' => 'Espace extérieur privé'],
            ['name' => 'Jacuzzi', 'icon' => 'fa-hot-tub', 'description' => 'Bain à remous'],
            ['name' => 'Vue panoramique', 'icon' => 'fa-mountain', 'description' => 'Vue exceptionnelle'],
            ['name' => 'Animaux acceptés', 'icon' => 'fa-paw', 'description' => 'Les animaux de compagnie sont autorisés'],
            ['name' => 'Petit déjeuner inclus', 'icon' => 'fa-coffee', 'description' => 'Petit déjeuner fourni'],
            ['name' => 'Cheminée', 'icon' => 'fa-fire-alt', 'description' => 'Cheminée fonctionnelle'],
        ];

        foreach ($amenities as $index => $amenityData) {
            $amenity = new Amenity();
            $amenity->setName($amenityData['name']);
            $amenity->setIcon($amenityData['icon']);
            $amenity->setDescription($amenityData['description']);
            $manager->persist($amenity);
            $this->addReference("amenity-{$index}", $amenity);
        }

        $manager->flush();
    }
}