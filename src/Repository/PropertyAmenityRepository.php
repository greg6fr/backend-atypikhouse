<?php

namespace App\Repository;

use App\Entity\Amenity;
use App\Entity\Property;
use App\Entity\PropertyAmenity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyAmenity>
 *
 * @method PropertyAmenity|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyAmenity|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyAmenity[]    findAll()
 * @method PropertyAmenity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyAmenityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyAmenity::class);
    }

    public function save(PropertyAmenity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PropertyAmenity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find amenities for a specific property
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.property = :property')
            ->setParameter('property', $property)
            ->orderBy('pa.isHighlighted', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find highlighted amenities for a property
     */
    public function findHighlightedByProperty(Property $property): array
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.property = :property')
            ->andWhere('pa.isHighlighted = :highlighted')
            ->setParameter('property', $property)
            ->setParameter('highlighted', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties that have a specific amenity
     */
    public function findPropertiesByAmenity(Amenity $amenity): array
    {
        return $this->createQueryBuilder('pa')
            ->join('pa.property', 'p')
            ->andWhere('pa.amenity = :amenity')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('amenity', $amenity)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find PropertyAmenity by property and amenity
     */
    public function findOneByPropertyAndAmenity(Property $property, Amenity $amenity): ?PropertyAmenity
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.property = :property')
            ->andWhere('pa.amenity = :amenity')
            ->setParameter('property', $property)
            ->setParameter('amenity', $amenity)
            ->getQuery()
            ->getOneOrNullResult();
    }
}