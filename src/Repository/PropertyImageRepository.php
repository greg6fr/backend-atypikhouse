<?php

namespace App\Repository;

use App\Entity\PropertyImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PropertyImage>
 *
 * @method PropertyImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PropertyImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PropertyImage[]    findAll()
 * @method PropertyImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyImage::class);
    }

    public function save(PropertyImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PropertyImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find images for a property
     */
    public function findByProperty(int $propertyId): array
    {
        return $this->createQueryBuilder('pi')
            ->andWhere('pi.property = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('pi.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find featured image for a property
     */
    public function findFeaturedByProperty(int $propertyId): ?PropertyImage
    {
        return $this->createQueryBuilder('pi')
            ->andWhere('pi.property = :propertyId')
            ->andWhere('pi.isFeatured = :isFeatured')
            ->setParameter('propertyId', $propertyId)
            ->setParameter('isFeatured', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}