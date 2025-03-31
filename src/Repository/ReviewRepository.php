<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 *
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function save(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find reviews by property
     */
    public function findByProperty(Property $property): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.property = :property')
            ->setParameter('property', $property)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find moderated reviews by property
     */
    public function findModeratedByProperty(Property $property): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.property = :property')
            ->andWhere('r.isModerated = :isModerated')
            ->setParameter('property', $property)
            ->setParameter('isModerated', true)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews by author
     */
    public function findByAuthor(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.booking', 'b')
            ->andWhere('b.tenant = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reviews that need moderation
     */
    public function findForModeration(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isModerated = :isModerated')
            ->setParameter('isModerated', false)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calculate average rating for a property
     */
    public function calculateAverageRating(Property $property): ?float
    {
        try {
            return $this->createQueryBuilder('r')
                ->select('AVG(r.rating)')
                ->andWhere('r.property = :property')
                ->setParameter('property', $property)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Count reviews by property
     */
    public function countByProperty(Property $property): int
    {
        try {
            return $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->andWhere('r.property = :property')
                ->setParameter('property', $property)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Get the distribution of ratings for a property
     * Returns an array with keys 1-5 and count values
     */
    public function getRatingDistribution(Property $property): array
    {
        $distribution = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        ];

        $results = $this->createQueryBuilder('r')
            ->select('r.rating, COUNT(r.id) as count')
            ->andWhere('r.property = :property')
            ->setParameter('property', $property)
            ->groupBy('r.rating')
            ->getQuery()
            ->getResult();

        foreach ($results as $result) {
            $distribution[$result['rating']] = $result['count'];
        }

        return $distribution;
    }
}