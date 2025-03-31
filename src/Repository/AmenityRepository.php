<?php

namespace App\Repository;

use App\Entity\Amenity;
use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Amenity>
 *
 * @method Amenity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Amenity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Amenity[]    findAll()
 * @method Amenity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmenityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Amenity::class);
    }

    public function save(Amenity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Amenity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find popular amenities (most used in properties)
     */
    public function findPopular(int $limit = 10): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        
        return $queryBuilder
            ->select('a', 'COUNT(p.id) as propertyCount')
            ->from('App\Entity\Amenity', 'a')
            ->join('a.properties', 'p')
            ->groupBy('a.id')
            ->orderBy('propertyCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find amenities for a property type
     */
    public function findByPropertyType(int $propertyTypeId): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        
        return $queryBuilder
            ->select('a', 'COUNT(p.id) as propertyCount')
            ->from('App\Entity\Amenity', 'a')
            ->join('a.properties', 'p')
            ->where('p.propertyType = :propertyTypeId')
            ->setParameter('propertyTypeId', $propertyTypeId)
            ->groupBy('a.id')
            ->orderBy('propertyCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find amenities not associated with the given property
     */
    public function findNotInProperty(Property $property): array
    {
        $queryBuilder = $this->createQueryBuilder('a');
        
        // Get IDs of amenities already associated with the property
        $propertyAmenityIds = $property->getAmenities()->map(function ($amenity) {
            return $amenity->getId();
        })->toArray();
        
        // If no amenities are associated, return all amenities
        if (empty($propertyAmenityIds)) {
            return $queryBuilder
                ->orderBy('a.name', 'ASC')
                ->getQuery()
                ->getResult();
        }
        
        // Otherwise, return amenities not in the list
        return $queryBuilder
            ->where($queryBuilder->expr()->notIn('a.id', ':amenityIds'))
            ->setParameter('amenityIds', $propertyAmenityIds)
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search amenities by name
     */
    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}