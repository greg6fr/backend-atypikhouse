<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count users with owner role
     */
    public function countOwners(): int
    {
        try {
            return $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
                ->setParameter('role', json_encode('ROLE_OWNER'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Count users with tenant role (only ROLE_USER without ROLE_OWNER)
     */
    public function countTenants(): int
    {
        try {
            return $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->andWhere('JSON_CONTAINS(u.roles, :roleUser) = 1')
                ->andWhere('JSON_CONTAINS(u.roles, :roleOwner) = 0')
                ->setParameter('roleUser', json_encode('ROLE_USER'))
                ->setParameter('roleOwner', json_encode('ROLE_OWNER'))
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Find unverified owners
     */
    public function findUnverifiedOwners(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->andWhere('u.isVerified = :isVerified')
            ->setParameter('role', json_encode('ROLE_OWNER'))
            ->setParameter('isVerified', false)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently active users
     */
    public function findRecentlyActive(int $days = 30): array
    {
        $date = new \DateTime('now');
        $date->modify('-' . $days . ' days');

        return $this->createQueryBuilder('u')
            ->andWhere('u.updatedAt > :date')
            ->setParameter('date', $date)
            ->orderBy('u.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}