<?php

namespace App\Repository;

use App\Entity\PropertyType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyType>
 *
 * @method PropertyType|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyType|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyType[]    findAll()
 * @method PropertyType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyType::class);
    }

    public function save(PropertyType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PropertyType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active property types (that have at least one active property)
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('pt')
            ->select('pt')
            ->join('pt.properties', 'p')
            ->where('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->groupBy('pt.id')
            ->orderBy('pt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find property types with their property count
     */
    public function findWithPropertyCount(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                pt.id,
                pt.name,
                pt.description,
                pt.icon,
                COUNT(p.id) as property_count
            FROM property_type pt
            LEFT JOIN property p ON p.property_type_id = pt.id
            GROUP BY pt.id
            ORDER BY property_count DESC, pt.name ASC
        ';
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        
        return $resultSet->fetchAllAssociative();
    }

    /**
     * Find popular property types (with most bookings)
     */
    public function findPopular(int $limit = 5): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                pt.id,
                pt.name,
                pt.description,
                pt.icon,
                COUNT(b.id) as booking_count
            FROM property_type pt
            JOIN property p ON p.property_type_id = pt.id
            JOIN booking b ON b.property_id = p.id
            WHERE b.status IN ("confirmed", "completed")
            GROUP BY pt.id
            ORDER BY booking_count DESC
            LIMIT :limit
        ';
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['limit' => $limit]);
        
        return $resultSet->fetchAllAssociative();
    }
}