<?php
// src/DataFixtures/ReviewFixtures.php
namespace App\DataFixtures;

use App\Entity\Review;
use App\Entity\Booking;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $reviews = [
            [
                'booking' => 0, // La réservation complétée
                'rating' => 5,
                'comment' => 'Séjour magnifique dans cette yourte ! Tout était parfait, de l\'accueil à la propreté. La vue sur les montagnes est à couper le souffle.',
                'isModerated' => true
            ],
            // Vous pouvez ajouter d'autres avis si vous avez d'autres réservations complétées
        ];

        foreach ($reviews as $reviewData) {
            $booking = $this->getReference("booking-{$reviewData['booking']}", Booking::class);
            
            $review = new Review();
            $review->setBooking($booking);
            $review->setProperty($booking->getProperty());
            $review->setRating($reviewData['rating']);
            $review->setComment($reviewData['comment']);
            $review->setIsModerated($reviewData['isModerated']);
            
            $manager->persist($review);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BookingFixtures::class,
        ];
    }
}