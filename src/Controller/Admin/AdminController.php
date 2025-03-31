<?php

namespace App\Controller\Admin;

use App\Entity\Property;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\PropertyRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private UserRepository $userRepository;
    private PropertyRepository $propertyRepository;
    private ReviewRepository $reviewRepository;
    private BookingRepository $bookingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        PropertyRepository $propertyRepository,
        ReviewRepository $reviewRepository,
        BookingRepository $bookingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->userRepository = $userRepository;
        $this->propertyRepository = $propertyRepository;
        $this->reviewRepository = $reviewRepository;
        $this->bookingRepository = $bookingRepository;
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        return $this->json(
            $users,
            Response::HTTP_OK,
            [],
            ['groups' => ['user:read']]
        );
    }
    
    #[Route('/users/{id}/verify', name: 'app_admin_verify_user', methods: ['PUT'])]
    public function verifyUser(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $user->setIsVerified(true);
        $this->entityManager->flush();
        
        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => ['user:read']]
        );
    }
    
    #[Route('/users/{id}/role', name: 'app_admin_change_role', methods: ['PUT'])]
    public function changeUserRole(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        $roles = $data['roles'] ?? [];
        
        // Ensure at least ROLE_USER is present
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        
        $user->setRoles($roles);
        $this->entityManager->flush();
        
        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => ['user:read']]
        );
    }
    
    #[Route('/properties', name: 'app_admin_properties', methods: ['GET'])]
    public function getProperties(): JsonResponse
    {
        $properties = $this->propertyRepository->findAll();
        
        return $this->json(
            $properties,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }
    
    #[Route('/properties/{id}/approve', name: 'app_admin_approve_property', methods: ['PUT'])]
    public function approveProperty(int $id): JsonResponse
    {
        $property = $this->propertyRepository->find($id);
        
        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }
        
        $property->setIsActive(true);
        $this->entityManager->flush();
        
        return $this->json(
            $property,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }
    
    #[Route('/properties/{id}/reject', name: 'app_admin_reject_property', methods: ['PUT'])]
    public function rejectProperty(int $id): JsonResponse
    {
        $property = $this->propertyRepository->find($id);
        
        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }
        
        $property->setIsActive(false);
        $this->entityManager->flush();
        
        return $this->json(
            $property,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }
    
    #[Route('/reviews', name: 'app_admin_reviews', methods: ['GET'])]
    public function getReviews(): JsonResponse
    {
        $reviews = $this->reviewRepository->findAll();
        
        return $this->json(
            $reviews,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }
    
    #[Route('/reviews/{id}/moderate', name: 'app_admin_moderate_review', methods: ['PUT'])]
    public function moderateReview(int $id, Request $request): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        
        if (!$review) {
            return $this->json(['error' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        $isModerated = $data['is_moderated'] ?? false;
        
        $review->setIsModerated($isModerated);
        $this->entityManager->flush();
        
        return $this->json(
            $review,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }
    
    #[Route('/stats', name: 'app_admin_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        // Count total users
        $totalUsers = $this->userRepository->count([]);
        $totalOwners = $this->userRepository->countOwners();
        $totalTenants = $this->userRepository->countTenants();
        
        // Count properties
        $totalProperties = $this->propertyRepository->count([]);
        $activeProperties = $this->propertyRepository->count(['isActive' => true]);
        
        // Count bookings
        $totalBookings = $this->bookingRepository->count([]);
        $pendingBookings = $this->bookingRepository->countByStatus('pending');
        $confirmedBookings = $this->bookingRepository->countByStatus('confirmed');
        $completedBookings = $this->bookingRepository->countByStatus('completed');
        $cancelledBookings = $this->bookingRepository->countByStatus('cancelled');
        
        // Calculate revenue
        $totalRevenue = $this->bookingRepository->calculateTotalRevenue();
        $monthlyRevenue = $this->bookingRepository->calculateMonthlyRevenue();
        
        return $this->json([
            'users' => [
                'total' => $totalUsers,
                'owners' => $totalOwners,
                'tenants' => $totalTenants
            ],
            'properties' => [
                'total' => $totalProperties,
                'active' => $activeProperties,
                'inactive' => $totalProperties - $activeProperties
            ],
            'bookings' => [
                'total' => $totalBookings,
                'pending' => $pendingBookings,
                'confirmed' => $confirmedBookings,
                'completed' => $completedBookings,
                'cancelled' => $cancelledBookings
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'monthly' => $monthlyRevenue
            ]
        ]);
    }
    
    #[Route('/notify-owners', name: 'app_admin_notify_owners', methods: ['POST'])]
    public function notifyOwners(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? null;
        
        if (!$message) {
            return $this->json(['error' => 'Message is required'], Response::HTTP_BAD_REQUEST);
        }
        
        // Get all owners
        $owners = $this->userRepository->findByRole('ROLE_OWNER');
        
        // In a real application, you would send emails to all owners
        // Here we'll just return the count
        
        return $this->json([
            'success' => true,
            'message' => 'Notification sent to ' . count($owners) . ' owners'
        ]);
    }
}