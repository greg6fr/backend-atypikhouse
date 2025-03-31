<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find messages for a user (sent or received)
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.sender = :user OR m.receiver = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find messages between two users (conversation)
     */
    public function findConversation(User $user1, User $user2): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find unread messages for a user
     */
    public function findUnreadForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.receiver = :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread messages for a user
     */
    public function countUnreadForUser(User $user): int
    {
        try {
            return $this->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->andWhere('m.receiver = :user')
                ->andWhere('m.isRead = :isRead')
                ->setParameter('user', $user)
                ->setParameter('isRead', false)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * Find recent messages related to a property
     */
    public function findByPropertyId(int $propertyId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.propertyId = :propertyId')
            ->setParameter('propertyId', $propertyId)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent messages related to a booking
     */
    public function findByBookingId(int $bookingId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.bookingId = :bookingId')
            ->setParameter('bookingId', $bookingId)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all conversations for a user
     * Returns an array of the most recent message from each conversation
     */
    public function findConversationsForUser(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            WITH conversations AS (
                SELECT 
                    CASE 
                        WHEN m.sender_id = :userId THEN m.receiver_id
                        ELSE m.sender_id
                    END as other_user_id,
                    m.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY
                            CASE 
                                WHEN m.sender_id = :userId THEN CONCAT(m.sender_id, "-", m.receiver_id)
                                ELSE CONCAT(m.receiver_id, "-", m.sender_id)
                            END
                        ORDER BY m.sent_at DESC
                    ) as rn
                FROM message m
                WHERE m.sender_id = :userId OR m.receiver_id = :userId
            )
            SELECT c.*, u.email, u.first_name, u.last_name, u.profile_picture
            FROM conversations c
            JOIN user u ON u.id = c.other_user_id
            WHERE c.rn = 1
            ORDER BY c.sent_at DESC
        ';
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['userId' => $user->getId()]);
        
        return $resultSet->fetchAllAssociative();
    }
}