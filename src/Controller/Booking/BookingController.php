<?php

namespace App\Controller\Booking;

use App\Entity\Booking;
use App\Entity\Property;
use App\Repository\BookingRepository;
use App\Repository\PropertyRepository;
use App\Service\Booking\BookingService;
use App\Service\Payment\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class BookingController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private BookingService $bookingService;
    private BookingRepository $bookingRepository;
    private PropertyRepository $propertyRepository;
    private PaymentService $paymentService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        BookingService $bookingService,
        BookingRepository $bookingRepository,
        PropertyRepository $propertyRepository,
        PaymentService $paymentService
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->bookingService = $bookingService;
        $this->bookingRepository = $bookingRepository;
        $this->propertyRepository = $propertyRepository;
        $this->paymentService = $paymentService;
    }

    #[Route('/properties/{id}/check-availability', name: 'app_check_availability', methods: ['GET'])]
    public function checkAvailability(int $id, Request $request): JsonResponse
    {
        $property = $this->propertyRepository->find($id);
        
        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }
        
        $checkInDate = new \DateTime($request->query->get('check_in'));
        $checkOutDate = new \DateTime($request->query->get('check_out'));
        
        $available = $this->bookingService->isPropertyAvailableForDates($property, $checkInDate, $checkOutDate);
        $totalPrice = null;
        
        if ($available) {
            $totalPrice = $this->bookingService->calculateTotalPrice($property, $checkInDate, $checkOutDate);
        }
        
        return $this->json([
            'available' => $available,
            'total_price' => $totalPrice,
            'nights' => $checkInDate->diff($checkOutDate)->days
        ]);
    }
    
    #[Route('/bookings', name: 'app_create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $propertyId = $data['property_id'] ?? null;
        $checkInDate = $data['check_in_date'] ?? null;
        $checkOutDate = $data['check_out_date'] ?? null;
        
        if (!$propertyId || !$checkInDate || !$checkOutDate) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }
        
        $property = $this->propertyRepository->find($propertyId);
        
        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }
        
        $user = $this->getUser();
        
        try {
            $booking = $this->bookingService->createBooking(
                $property,
                $user,
                new \DateTime($checkInDate),
                new \DateTime($checkOutDate)
            );
            
            // Generate payment intent/session with Stripe or PayPal
            $paymentIntent = $this->paymentService->createPaymentIntent($booking);
            
            return $this->json([
                'booking' => $booking,
                'payment_intent' => $paymentIntent
            ], Response::HTTP_CREATED, [], ['groups' => ['booking:read', 'booking:item:read']]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/bookings/{id}/confirm-payment', name: 'app_confirm_payment', methods: ['POST'])]
    public function confirmPayment(int $id, Request $request): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        
        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }
        
        if ($booking->getTenant() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true);
        $paymentIntentId = $data['payment_intent_id'] ?? null;
        
        if (!$paymentIntentId) {
            return $this->json(['error' => 'Payment intent ID is required'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            // Verify payment with payment service
            $paymentVerified = $this->paymentService->verifyPayment($paymentIntentId);
            
            if (!$paymentVerified) {
                return $this->json(['error' => 'Payment verification failed'], Response::HTTP_BAD_REQUEST);
            }
            
            // Confirm booking
            $booking = $this->bookingService->confirmBooking($booking, $paymentIntentId);
            
            return $this->json(
                $booking,
                Response::HTTP_OK,
                [],
                ['groups' => ['booking:read', 'booking:item:read']]
            );
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/bookings/{id}/cancel', name: 'app_cancel_booking', methods: ['PUT'])]
    public function cancelBooking(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        
        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if user is allowed to cancel this booking
        $user = $this->getUser();
        if ($booking->getTenant() !== $user && $booking->getProperty()->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        try {
            $booking = $this->bookingService->cancelBooking($booking);
            
            // If there was a payment, initiate refund
            if ($booking->getTransactionId()) {
                $this->paymentService->refundPayment($booking->getTransactionId());
            }
            
            return $this->json(
                $booking,
                Response::HTTP_OK,
                [],
                ['groups' => ['booking:read', 'booking:item:read']]
            );
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/bookings/{id}/complete', name: 'app_complete_booking', methods: ['PUT'])]
    public function completeBooking(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        
        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Only property owner or admin can mark booking as completed
        if ($booking->getProperty()->getOwner() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        try {
            $booking = $this->bookingService->completeBooking($booking);
            
            return $this->json(
                $booking,
                Response::HTTP_OK,
                [],
                ['groups' => ['booking:read', 'booking:item:read']]
            );
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/user/bookings', name: 'app_user_bookings', methods: ['GET'])]
    public function getUserBookings(): JsonResponse
    {
        $user = $this->getUser();
        $bookings = $this->bookingRepository->findByTenant($user);
        
        return $this->json(
            $bookings,
            Response::HTTP_OK,
            [],
            ['groups' => ['booking:read']]
        );
    }
    
    #[Route('/owner/bookings', name: 'app_owner_bookings', methods: ['GET'])]
    public function getOwnerBookings(): JsonResponse
    {
        $user = $this->getUser();
        $bookings = $this->bookingRepository->findByPropertyOwner($user);
        
        return $this->json(
            $bookings,
            Response::HTTP_OK,
            [],
            ['groups' => ['booking:read']]
        );
    }
}