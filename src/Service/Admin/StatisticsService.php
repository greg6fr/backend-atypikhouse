<?php

namespace App\Service\Admin;

use App\Entity\Booking;
use App\Entity\Property;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\PropertyRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private PropertyRepository $propertyRepository;
    private BookingRepository $bookingRepository;
    private ReviewRepository $reviewRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        PropertyRepository $propertyRepository,
        BookingRepository $bookingRepository,
        ReviewRepository $reviewRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->propertyRepository = $propertyRepository;
        $this->bookingRepository = $bookingRepository;
        $this->reviewRepository = $reviewRepository;
    }

    /**
     * Get overall platform statistics
     */
    public function getOverallStatistics(): array
    {
        // Count users
        $totalUsers = $this->userRepository->count([]);
        $totalOwners = $this->userRepository->countOwners();
        $totalTenants = $this->userRepository->countTenants();

        // Count properties
        $totalProperties = $this->propertyRepository->count([]);
        $activeProperties = $this->propertyRepository->count(['isActive' => true]);

        // Count bookings
        $totalBookings = $this->bookingRepository->count([]);
        $pendingBookings = $this->bookingRepository->countByStatus(Booking::STATUS_PENDING);
        $confirmedBookings = $this->bookingRepository->countByStatus(Booking::STATUS_CONFIRMED);
        $completedBookings = $this->bookingRepository->countByStatus(Booking::STATUS_COMPLETED);
        $cancelledBookings = $this->bookingRepository->countByStatus(Booking::STATUS_CANCELLED);

        // Calculate revenue
        $totalRevenue = $this->bookingRepository->calculateTotalRevenue();
        $monthlyRevenue = $this->bookingRepository->calculateMonthlyRevenue();

        return [
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
        ];
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        $conn = $this->entityManager->getConnection();

        // User registrations by month
        $sql = '
            SELECT 
                DATE_FORMAT(u.created_at, "%Y-%m") as month,
                COUNT(u.id) as count
            FROM user u
            GROUP BY DATE_FORMAT(u.created_at, "%Y-%m")
            ORDER BY month ASC
        ';
        $stmt = $conn->prepare($sql);
        $registrationsByMonth = $stmt->executeQuery()->fetchAllAssociative();

        // Active users by role
        $activeUsers = $this->userRepository->findRecentlyActive(30);
        $activeOwners = array_filter($activeUsers, function (User $user) {
            return in_array('ROLE_OWNER', $user->getRoles());
        });
        $activeTenants = array_filter($activeUsers, function (User $user) {
            return !in_array('ROLE_OWNER', $user->getRoles()) && in_array('ROLE_USER', $user->getRoles());
        });

        return [
            'registrations_by_month' => $registrationsByMonth,
            'active_users' => [
                'total' => count($activeUsers),
                'owners' => count($activeOwners),
                'tenants' => count($activeTenants)
            ],
            'unverified_owners' => count($this->userRepository->findUnverifiedOwners())
        ];
    }

    /**
     * Get property statistics
     */
    public function getPropertyStatistics(): array
    {
        $conn = $this->entityManager->getConnection();

        // Properties by type
        $sql = '
            SELECT 
                pt.name as type,
                COUNT(p.id) as count
            FROM property p
            JOIN property_type pt ON p.property_type_id = pt.id
            GROUP BY pt.id
            ORDER BY count DESC
        ';
        $stmt = $conn->prepare($sql);
        $propertiesByType = $stmt->executeQuery()->fetchAllAssociative();

        // Properties by location (grouped by city)
        $sql = '
            SELECT 
                SUBSTRING_INDEX(p.address, ",", -2) as location,
                COUNT(p.id) as count
            FROM property p
            GROUP BY location
            ORDER BY count DESC
            LIMIT 10
        ';
        $stmt = $conn->prepare($sql);
        $propertiesByLocation = $stmt->executeQuery()->fetchAllAssociative();

        // Properties added by month
        $sql = '
            SELECT 
                DATE_FORMAT(p.created_at, "%Y-%m") as month,
                COUNT(p.id) as count
            FROM property p
            GROUP BY DATE_FORMAT(p.created_at, "%Y-%m")
            ORDER BY month ASC
        ';
        $stmt = $conn->prepare($sql);
        $propertiesByMonth = $stmt->executeQuery()->fetchAllAssociative();

        return [
            'by_type' => $propertiesByType,
            'by_location' => $propertiesByLocation,
            'by_month' => $propertiesByMonth,
            'for_moderation' => count($this->propertyRepository->findForModeration())
        ];
    }

    /**
     * Get booking statistics
     */
    public function getBookingStatistics(): array
    {
        $conn = $this->entityManager->getConnection();

        // Bookings by month
        $sql = '
            SELECT 
                DATE_FORMAT(b.created_at, "%Y-%m") as month,
                COUNT(b.id) as count
            FROM booking b
            GROUP BY DATE_FORMAT(b.created_at, "%Y-%m")
            ORDER BY month ASC
        ';
        $stmt = $conn->prepare($sql);
        $bookingsByMonth = $stmt->executeQuery()->fetchAllAssociative();

        // Bookings by status
        $bookingsByStatus = [
            'pending' => $this->bookingRepository->countByStatus(Booking::STATUS_PENDING),
            'confirmed' => $this->bookingRepository->countByStatus(Booking::STATUS_CONFIRMED),
            'completed' => $this->bookingRepository->countByStatus(Booking::STATUS_COMPLETED),
            'cancelled' => $this->bookingRepository->countByStatus(Booking::STATUS_CANCELLED)
        ];

        // Top booked property types
        $sql = '
            SELECT 
                pt.name as type,
                COUNT(b.id) as booking_count
            FROM booking b
            JOIN property p ON b.property_id = p.id
            JOIN property_type pt ON p.property_type_id = pt.id
            WHERE b.status IN ("confirmed", "completed")
            GROUP BY pt.id
            ORDER BY booking_count DESC
            LIMIT 5
        ';
        $stmt = $conn->prepare($sql);
        $topPropertyTypes = $stmt->executeQuery()->fetchAllAssociative();

        return [
            'by_month' => $bookingsByMonth,
            'by_status' => $bookingsByStatus,
            'top_property_types' => $topPropertyTypes
        ];
    }

    /**
     * Get revenue statistics
     */
    public function getRevenueStatistics(): array
    {
        // Monthly revenue
        $monthlyRevenue = $this->bookingRepository->calculateMonthlyRevenue();

        // Calculate year-over-year growth
        $currentYearRevenue = 0;
        $previousYearRevenue = 0;
        $currentYear = date('Y');
        $previousYear = (int)$currentYear - 1;

        foreach ($monthlyRevenue as $item) {
            $year = explode('-', $item['month'])[0];
            if ($year == $currentYear) {
                $currentYearRevenue += (float)$item['revenue'];
            } elseif ($year == $previousYear) {
                $previousYearRevenue += (float)$item['revenue'];
            }
        }

        $yearOverYearGrowth = $previousYearRevenue > 0
            ? (($currentYearRevenue - $previousYearRevenue) / $previousYearRevenue) * 100
            : 0;

        // Revenue by property type
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT 
                pt.name as type,
                SUM(b.total_price) as revenue
            FROM booking b
            JOIN property p ON b.property_id = p.id
            JOIN property_type pt ON p.property_type_id = pt.id
            WHERE b.status = "completed"
            GROUP BY pt.id
            ORDER BY revenue DESC
        ';
        $stmt = $conn->prepare($sql);
        $revenueByPropertyType = $stmt->executeQuery()->fetchAllAssociative();

        return [
            'monthly' => $monthlyRevenue,
            'year_over_year_growth' => $yearOverYearGrowth,
            'by_property_type' => $revenueByPropertyType
        ];
    }

    /**
     * Get review statistics
     */
    public function getReviewStatistics(): array
    {
        $conn = $this->entityManager->getConnection();

        // Average rating by property type
        $sql = '
            SELECT 
                pt.name as type,
                AVG(r.rating) as average_rating,
                COUNT(r.id) as review_count
            FROM review r
            JOIN property p ON r.property_id = p.id
            JOIN property_type pt ON p.property_type_id = pt.id
            GROUP BY pt.id
            ORDER BY average_rating DESC
        ';
        $stmt = $conn->prepare($sql);
        $ratingsByPropertyType = $stmt->executeQuery()->fetchAllAssociative();

        // Reviews needing moderation
        $reviewsForModeration = count($this->reviewRepository->findForModeration());

        // Rating distribution
        $sql = '
            SELECT 
                r.rating,
                COUNT(r.id) as count
            FROM review r
            GROUP BY r.rating
            ORDER BY r.rating ASC
        ';
        $stmt = $conn->prepare($sql);
        $ratingDistribution = $stmt->executeQuery()->fetchAllAssociative();

        return [
            'by_property_type' => $ratingsByPropertyType,
            'for_moderation' => $reviewsForModeration,
            'rating_distribution' => $ratingDistribution
        ];
    }
}