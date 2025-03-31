<?php

namespace App\Service\Booking;

use App\Entity\Booking;
use App\Entity\Property;
use App\Entity\User;
use App\Repository\AvailabilityRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class BookingService
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $bookingRepository;
    private AvailabilityRepository $availabilityRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        BookingRepository $bookingRepository,
        AvailabilityRepository $availabilityRepository
    ) {
        $this->entityManager = $entityManager;
        $this->bookingRepository = $bookingRepository;
        $this->availabilityRepository = $availabilityRepository;
    }

    /**
     * Create a new booking
     */
    public function createBooking(Property $property, User $tenant, \DateTimeInterface $checkInDate, \DateTimeInterface $checkOutDate): Booking
    {
        // Check if property is active
        if (!$property->isIsActive()) {
            throw new BadRequestHttpException('Property is not available for booking');
        }
        
        // Check if dates are valid
        if ($checkInDate < new \DateTime('today')) {
            throw new BadRequestHttpException('Check-in date cannot be in the past');
        }
        
        if ($checkInDate >= $checkOutDate) {
            throw new BadRequestHttpException('Check-in date must be before check-out date');
        }
        
        // Check if property is available for the requested dates
        if (!$this->isPropertyAvailableForDates($property, $checkInDate, $checkOutDate)) {
            throw new ConflictHttpException('Property is not available for the requested dates');
        }
        
        // Calculate total price
        $totalPrice = $this->calculateTotalPrice($property, $checkInDate, $checkOutDate);
        
        // Create booking
        $booking = new Booking();
        $booking->setProperty($property);
        $booking->setTenant($tenant);
        $booking->setCheckInDate($checkInDate);
        $booking->setCheckOutDate($checkOutDate);
        $booking->setTotalPrice((string) $totalPrice);
        $booking->setStatus(Booking::STATUS_PENDING);
        
        $this->entityManager->persist($booking);
        $this->entityManager->flush();
        
        return $booking;
    }
    
    /**
     * Confirm a booking
     */
    public function confirmBooking(Booking $booking, string $transactionId = null): Booking
    {
        if ($booking->getStatus() !== Booking::STATUS_PENDING) {
            throw new BadRequestHttpException('Booking cannot be confirmed because it is not in pending status');
        }
        
        $booking->setStatus(Booking::STATUS_CONFIRMED);
        
        if ($transactionId) {
            $booking->setTransactionId($transactionId);
        }
        
        $this->entityManager->flush();
        
        return $booking;
    }
    
    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking): Booking
    {
        if (!$booking->canCancel()) {
            throw new BadRequestHttpException('Booking cannot be cancelled');
        }
        
        $booking->setStatus(Booking::STATUS_CANCELLED);
        $this->entityManager->flush();
        
        return $booking;
    }
    
    /**
     * Complete a booking (after checkout)
     */
    public function completeBooking(Booking $booking): Booking
    {
        if ($booking->getStatus() !== Booking::STATUS_CONFIRMED) {
            throw new BadRequestHttpException('Booking cannot be completed because it is not in confirmed status');
        }
        
        // Check if checkout date has passed
        if ($booking->getCheckOutDate() > new \DateTime()) {
            throw new BadRequestHttpException('Booking cannot be completed before checkout date');
        }
        
        $booking->setStatus(Booking::STATUS_COMPLETED);
        $this->entityManager->flush();
        
        return $booking;
    }
    
    /**
     * Check if a property is available for the requested dates
     */
    public function isPropertyAvailableForDates(Property $property, \DateTimeInterface $checkInDate, \DateTimeInterface $checkOutDate): bool
    {
        // Check if there are any availabilities that cover the requested period
        $availabilities = $this->availabilityRepository->findAvailabilitiesCoveringPeriod(
            $property,
            $checkInDate,
            $checkOutDate
        );
        
        if (empty($availabilities)) {
            return false;
        }
        
        // Check if there are any conflicting bookings
        $conflictingBookings = $this->bookingRepository->findConflictingBookings(
            $property,
            $checkInDate,
            $checkOutDate
        );
        
        return empty($conflictingBookings);
    }
    
    /**
     * Calculate the total price for a booking
     */
    public function calculateTotalPrice(Property $property, \DateTimeInterface $checkInDate, \DateTimeInterface $checkOutDate): float
    {
        $nights = $checkInDate->diff($checkOutDate)->days;
        $availabilities = $this->availabilityRepository->findAvailabilitiesCoveringPeriod(
            $property,
            $checkInDate,
            $checkOutDate
        );
        
        $totalPrice = 0;
        $basePrice = (float) $property->getBasePrice();
        
        // If there are special prices for some dates, calculate accordingly
        if (!empty($availabilities)) {
            foreach ($availabilities as $availability) {
                $pricePerNight = $availability->getSpecialPrice() 
                    ? (float) $availability->getSpecialPrice() 
                    : $basePrice;
                
                // Calculate overlap between booking period and availability period
                $overlapStart = max($checkInDate, $availability->getStartDate());
                $overlapEnd = min($checkOutDate, $availability->getEndDate());
                $overlapNights = $overlapStart->diff($overlapEnd)->days;
                
                $totalPrice += $pricePerNight * $overlapNights;
            }
        } else {
            // If no specific availabilities, use base price
            $totalPrice = $basePrice * $nights;
        }
        
        return $totalPrice;
    }
}