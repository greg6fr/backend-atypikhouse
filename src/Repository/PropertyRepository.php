<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 *
 * @method Property|null find($id, $lockMode = null, $lockVersion = null)
 * @method Property|null findOneBy(array $criteria, array $orderBy = null)
 * @method Property[]    findAll()
 * @method Property[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function save(Property $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Property $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active properties
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find properties by owner
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find featured properties (active properties with best ratings)
     */
    public function findFeatured(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true)
            ->leftJoin('p.reviews', 'r')
            ->groupBy('p.id')
            ->orderBy('AVG(COALESCE(r.rating, 0))', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Search properties by criteria
     */
    public function searchByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', true);

        // Filter by property type if provided
        if (isset($criteria['propertyType']) && $criteria['propertyType']) {
            $qb->andWhere('p.propertyType = :propertyType')
                ->setParameter('propertyType', $criteria['propertyType']);
        }

        // Filter by capacity if provided
        if (isset($criteria['capacity']) && $criteria['capacity']) {
            $qb->andWhere('p.capacity >= :capacity')
                ->setParameter('capacity', $criteria['capacity']);
        }

        // Filter by price range if provided
        if (isset($criteria['minPrice']) && $criteria['minPrice']) {
            $qb->andWhere('p.basePrice >= :minPrice')
                ->setParameter('minPrice', $criteria['minPrice']);
        }

        if (isset($criteria['maxPrice']) && $criteria['maxPrice']) {
            $qb->andWhere('p.basePrice <= :maxPrice')
                ->setParameter('maxPrice', $criteria['maxPrice']);
        }

        // Filter by amenities if provided
        if (isset($criteria['amenities']) && is_array($criteria['amenities']) && count($criteria['amenities']) > 0) {
            $qb->leftJoin('p.amenities', 'a')
                ->andWhere('a.id IN (:amenities)')
                ->setParameter('amenities', $criteria['amenities']);
        }

        // Filter by search query (in title or description)
        if (isset($criteria['query']) && $criteria['query']) {
            $qb->andWhere('p.title LIKE :query OR p.description LIKE :query')
                ->setParameter('query', '%' . $criteria['query'] . '%');
        }

        // Filter by location if provided (simple matching for now)
        if (isset($criteria['location']) && $criteria['location']) {
            $qb->andWhere('p.address LIKE :location')
                ->setParameter('location', '%' . $criteria['location'] . '%');
        }

        // Sort by price or date
        if (isset($criteria['sortBy']) && $criteria['sortBy']) {
            switch ($criteria['sortBy']) {
                case 'price_asc':
                    $qb->orderBy('p.basePrice', 'ASC');
                    break;
                case 'price_desc':
                    $qb->orderBy('p.basePrice', 'DESC');
                    break;
                case 'date_desc':
                    $qb->orderBy('p.createdAt', 'DESC');
                    break;
                case 'rating_desc':
                    $qb->leftJoin('p.reviews', 'r')
                        ->groupBy('p.id')
                        ->orderBy('AVG(COALESCE(r.rating, 0))', 'DESC');
                    break;
                default:
                    $qb->orderBy('p.createdAt', 'DESC');
            }
        } else {
            $qb->orderBy('p.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find properties that need moderation (newly created or updated)
     */
    public function findForModeration(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', false)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}