<?php
// src/DataFixtures/BookingFixtures.php
namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\Property;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Quelques réservations passées et futures
        $bookings = [
            // Réservation passée terminée
            [
                'property' => 0,
                'tenant' => 1,
                'checkInDate' => '-2 months',
                'checkOutDate' => '-1 month -25 days',
                'totalPrice' => '600.00',
                'status' => Booking::STATUS_COMPLETED
            ],
            // Réservation passée annulée
            [
                'property' => 1,
                'tenant' => 2,
                'checkInDate' => '-1 month',
                'checkOutDate' => '-1 month +5 days',
                'totalPrice' => '750.00',
                'status' => Booking::STATUS_CANCELLED
            ],
            // Réservation future confirmée
            [
                'property' => 0,
                'tenant' => 3,
                'checkInDate' => '+2 weeks',
                'checkOutDate' => '+2 weeks +4 days',
                'totalPrice' => '480.00',
                'status' => Booking::STATUS_CONFIRMED
            ],
            // Réservation future en attente
            [
                'property' => 1,
                'tenant' => 4,
                'checkInDate' => '+1 month',
                'checkOutDate' => '+1 month +3 days',
                'totalPrice' => '450.00',
                'status' => Booking::STATUS_PENDING
            ],
        ];

        foreach ($bookings as $index => $bookingData) {
            $booking = new Booking();
            $booking->setProperty($this->getReference("property-{$bookingData['property']}", Property::class));
            $booking->setTenant($this->getReference("tenant-{$bookingData['tenant']}", User::class));
            $booking->setCheckInDate(new \DateTime($bookingData['checkInDate']));
            $booking->setCheckOutDate(new \DateTime($bookingData['checkOutDate']));
            $booking->setTotalPrice($bookingData['totalPrice']);
            $booking->setStatus($bookingData['status']);
            
            // Ajouter un transactionId pour les réservations confirmées ou terminées
            if (in_array($bookingData['status'], [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED])) {
                $booking->setTransactionId('tr_' . bin2hex(random_bytes(10)));
            }
            
            $manager->persist($booking);
            $this->addReference("booking-{$index}", $booking);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PropertyFixtures::class,
            UserFixtures::class,
        ];
    }
}