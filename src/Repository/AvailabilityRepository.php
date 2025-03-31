<?php

namespace App\Repository;

use App\Entity\Availability;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Availability>
 *
 * @method Availability|null find($id, $lockMode = null, $lockVersion = null)
 * @method Availability|null findOneBy(array $criteria, array $orderBy = null)
 * @method Availability[]    findAll()
 * @method Availability[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    public function save(Availability $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Availability $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find availabilities for a property
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->setParameter('property', $property)
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find future availabilities for a property
     */
    public function findFutureByProperty(Property $property): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->andWhere('a.endDate >= :today')
            ->setParameter('property', $property)
            ->setParameter('today', new \DateTime())
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find availabilities for a property in a date range
     */
    public function findByPropertyAndDateRange(Property $property, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->andWhere(
                '(a.startDate <= :endDate AND a.endDate >= :startDate)'
            )
            ->setParameter('property', $property)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find availabilities that cover a specific period completely
     */
    public function findAvailabilitiesCoveringPeriod(Property $property, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->andWhere('a.startDate <= :startDate')
            ->andWhere('a.endDate >= :endDate')
            ->setParameter('property', $property)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if there are any conflicting availabilities for a new availability
     */
    public function findConflictingAvailabilities(Property $property, \DateTimeInterface $startDate, \DateTimeInterface $endDate, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.property = :property')
            ->andWhere(
                '(a.startDate < :endDate AND a.endDate > :startDate)'
            )
            ->setParameter('property', $property)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($excludeId) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find availabilities that need attention (ending soon)
     */
    public function findEndingSoon(int $daysThreshold = 7): array
    {
        $today = new \DateTime();
        $threshold = new \DateTime('+' . $daysThreshold . ' days');

        return $this->createQueryBuilder('a')
            ->andWhere('a.endDate >= :today')
            ->andWhere('a.endDate <= :threshold')
            ->setParameter('today', $today)
            ->setParameter('threshold', $threshold)
            ->orderBy('a.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}