<?php

namespace App\Controller\Review;

use App\Entity\Review;
use App\Repository\BookingRepository;
use App\Repository\PropertyRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ReviewController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private ReviewRepository $reviewRepository;
    private BookingRepository $bookingRepository;
    private PropertyRepository $propertyRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        ReviewRepository $reviewRepository,
        BookingRepository $bookingRepository,
        PropertyRepository $propertyRepository
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->reviewRepository = $reviewRepository;
        $this->bookingRepository = $bookingRepository;
        $this->propertyRepository = $propertyRepository;
    }

    #[Route('/bookings/{id}/review', name: 'app_create_review', methods: ['POST'])]
    public function createReview(int $id, Request $request): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($booking->getTenant() !== $user) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Check if the booking can be reviewed
        if (!$booking->canReview()) {
            return $this->json(['error' => 'This booking cannot be reviewed'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        $rating = $data['rating'] ?? null;
        $comment = $data['comment'] ?? null;

        if ($rating === null || $comment === null) {
            return $this->json(['error' => 'Rating and comment are required'], Response::HTTP_BAD_REQUEST);
        }

        $review = new Review();
        $review->setBooking($booking);
        $review->setProperty($booking->getProperty());
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setIsModerated(false);

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $this->json(
            $review,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['review:read']]
        );
    }

    #[Route('/reviews/user', name: 'app_user_reviews', methods: ['GET'])]
    public function getUserReviews(): JsonResponse
    {
        $user = $this->getUser();
        $reviews = $this->reviewRepository->findByAuthor($user);

        return $this->json(
            $reviews,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }

    #[Route('/reviews/{id}', name: 'app_update_review', methods: ['PUT'])]
    public function updateReview(int $id, Request $request): JsonResponse
    {
        $review = $this->reviewRepository->find($id);

        if (!$review) {
            return $this->json(['error' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        $booking = $review->getBooking();

        // Only the author can update their own review, and only within 24 hours
        if ($booking->getTenant() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        // Check if the review is less than 24 hours old
        $createdAt = $review->getCreatedAt();
        $now = new \DateTimeImmutable();
        $interval = $now->diff($createdAt);
        $hoursElapsed = $interval->h + ($interval->days * 24);

        if ($hoursElapsed > 24 && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Reviews can only be updated within 24 hours'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Update only allowed fields
        if (isset($data['rating'])) {
            $review->setRating($data['rating']);
        }

        if (isset($data['comment'])) {
            $review->setComment($data['comment']);
        }

        // Admin can modify moderation status
        if ($this->isGranted('ROLE_ADMIN') && isset($data['is_moderated'])) {
            $review->setIsModerated($data['is_moderated']);
        }

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json(
            $review,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }

    #[Route('/reviews/{id}/moderate', name: 'app_moderate_review', methods: ['PUT'])]
    public function moderateReview(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $review = $this->reviewRepository->find($id);

        if (!$review) {
            return $this->json(['error' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $isModerated = $data['is_moderated'] ?? true;

        $review->setIsModerated($isModerated);
        $this->entityManager->flush();

        return $this->json(
            $review,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }

    #[Route('/reviews/for-moderation', name: 'app_reviews_for_moderation', methods: ['GET'])]
    public function getReviewsForModeration(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $reviews = $this->reviewRepository->findForModeration();

        return $this->json(
            $reviews,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }
}