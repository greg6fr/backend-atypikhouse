<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Property;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 *
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find bookings by tenant
     */
    public function findByTenant(User $tenant): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find bookings for properties owned by a user
     */
    public function findByPropertyOwner(User $owner): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.property', 'p')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find bookings for a specific property
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.property = :property')
            ->setParameter('property', $property)
            ->orderBy('b.checkInDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find bookings by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->setParameter('status', $status)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count bookings by status
     */
    public function countByStatus(string $status): int
    {
        try {
            return $this->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->andWhere('b.status = :status')
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Find conflicting bookings for a property during a specific date range
     */
    public function findConflictingBookings(Property $property, \DateTimeInterface $checkInDate, \DateTimeInterface $checkOutDate): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.property = :property')
            ->andWhere('b.status IN (:statuses)')
            ->andWhere(
                '(b.checkInDate < :checkOutDate AND b.checkOutDate > :checkInDate)'
            )
            ->setParameter('property', $property)
            ->setParameter('statuses', [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED])
            ->setParameter('checkInDate', $checkInDate)
            ->setParameter('checkOutDate', $checkOutDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate total revenue (from completed bookings)
     */
    public function calculateTotalRevenue(): float
    {
        try {
            return (float) $this->createQueryBuilder('b')
                ->select('SUM(b.totalPrice)')
                ->andWhere('b.status = :status')
                ->setParameter('status', Booking::STATUS_COMPLETED)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0.0;
        }
    }

    /**
     * Calculate monthly revenue
     */
    public function calculateMonthlyRevenue(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                DATE_FORMAT(b.created_at, "%Y-%m") as month,
                SUM(b.total_price) as revenue
            FROM booking b
            WHERE b.status = :status
            GROUP BY DATE_FORMAT(b.created_at, "%Y-%m")
            ORDER BY month ASC
        ';
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['status' => Booking::STATUS_COMPLETED]);
        
        return $resultSet->fetchAllAssociative();
    }
}