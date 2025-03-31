<?php
// src/DataFixtures/PropertyImageFixtures.php
namespace App\DataFixtures;

use App\Entity\PropertyImage;
use App\Entity\Property;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PropertyImageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Pour les images, nous allons simuler des chemins de fichiers
        $propertyImages = [
            0 => [
                ['path' => 'yourte_1.jpg', 'position' => 1, 'isFeatured' => true],
                ['path' => 'yourte_2.jpg', 'position' => 2, 'isFeatured' => false],
                ['path' => 'yourte_3.jpg', 'position' => 3, 'isFeatured' => false],
            ],
            1 => [
                ['path' => 'cabane_1.jpg', 'position' => 1, 'isFeatured' => true],
                ['path' => 'cabane_2.jpg', 'position' => 2, 'isFeatured' => false],
            ],
        ];

        foreach ($propertyImages as $propertyIndex => $images) {
            foreach ($images as $imageData) {
                $image = new PropertyImage();
                $image->setProperty($this->getReference("property-{$propertyIndex}", Property::class));
                $image->setPath($imageData['path']);
                $image->setPosition($imageData['position']);
                $image->setIsFeatured($imageData['isFeatured']);
                $manager->persist($image);
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