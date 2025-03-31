<?php
// src/DataFixtures/PropertyTypeFixtures.php
namespace App\DataFixtures;

use App\Entity\PropertyType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PropertyTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = [
            ['name' => 'Yourte', 'description' => 'Habitat traditionnel mongol', 'icon' => 'fa-home'],
            ['name' => 'Cabane dans les arbres', 'description' => 'Logement perché dans les arbres', 'icon' => 'fa-tree'],
            ['name' => 'Maison flottante', 'description' => 'Logement sur l\'eau', 'icon' => 'fa-water'],
            ['name' => 'Igloo', 'description' => 'Habitat traditionnel inuit', 'icon' => 'fa-snowflake'],
            ['name' => 'Tiny House', 'description' => 'Petite maison écologique', 'icon' => 'fa-house'],
            ['name' => 'Dôme géodésique', 'description' => 'Structure sphérique innovante', 'icon' => 'fa-circle'],
        ];

        foreach ($types as $index => $typeData) {
            $type = new PropertyType();
            $type->setName($typeData['name']);
            $type->setDescription($typeData['description']);
            $type->setIcon($typeData['icon']);
            $manager->persist($type);
            $this->addReference("property-type-{$index}", $type);
        }

        $manager->flush();
    }
}