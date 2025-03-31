<?php

namespace App\Controller\Property;

use App\Entity\Availability;
use App\Entity\Property;
use App\Entity\PropertyImage;
use App\Repository\AmenityRepository;
use App\Repository\AvailabilityRepository;
use App\Repository\PropertyRepository;
use App\Repository\PropertyTypeRepository;
use App\Repository\ReviewRepository;
use App\Service\Email\EmailService;
use App\Service\File\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class PropertyController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private PropertyRepository $propertyRepository;
    private PropertyTypeRepository $propertyTypeRepository;
    private AmenityRepository $amenityRepository;
    private AvailabilityRepository $availabilityRepository;
    private ReviewRepository $reviewRepository;
    private FileUploader $fileUploader;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        PropertyRepository $propertyRepository,
        PropertyTypeRepository $propertyTypeRepository,
        AmenityRepository $amenityRepository,
        AvailabilityRepository $availabilityRepository,
        ReviewRepository $reviewRepository,
        FileUploader $fileUploader,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->propertyRepository = $propertyRepository;
        $this->propertyTypeRepository = $propertyTypeRepository;
        $this->amenityRepository = $amenityRepository;
        $this->availabilityRepository = $availabilityRepository;
        $this->reviewRepository = $reviewRepository;
        $this->fileUploader = $fileUploader;
        $this->emailService = $emailService;
    }

    #[Route('/properties/search', name: 'app_properties_search', methods: ['GET'])]
    public function searchProperties(Request $request): JsonResponse
    {
        $criteria = [
            'propertyType' => $request->query->get('property_type'),
            'capacity' => $request->query->get('capacity'),
            'minPrice' => $request->query->get('min_price'),
            'maxPrice' => $request->query->get('max_price'),
            'amenities' => $request->query->all('amenities'),
            'query' => $request->query->get('query'),
            'location' => $request->query->get('location'),
            'sortBy' => $request->query->get('sort_by')
        ];

        $properties = $this->propertyRepository->searchByCriteria($criteria);

        return $this->json(
            $properties,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }

    #[Route('/properties/featured', name: 'app_properties_featured', methods: ['GET'])]
    public function getFeaturedProperties(): JsonResponse
    {
        $properties = $this->propertyRepository->findFeatured();

        return $this->json(
            $properties,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }

    #[Route('/owner/properties', name: 'app_owner_properties', methods: ['GET'])]
    public function getOwnerProperties(): JsonResponse
    {
        $user = $this->getUser();
        $properties = $this->propertyRepository->findByOwner($user);

        return $this->json(
            $properties,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }

    #[Route('/properties/{id}/availability', name: 'app_property_availability', methods: ['GET'])]
    public function getPropertyAvailability(int $id): JsonResponse
    {
        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $availabilities = $this->availabilityRepository->findByProperty($property);

        return $this->json(
            $availabilities,
            Response::HTTP_OK,
            [],
            ['groups' => ['availability:read']]
        );
    }

    #[Route('/properties/{id}/reviews', name: 'app_property_reviews', methods: ['GET'])]
    public function getPropertyReviews(int $id): JsonResponse
    {
        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $reviews = $this->reviewRepository->findByProperty($property);

        return $this->json(
            $reviews,
            Response::HTTP_OK,
            [],
            ['groups' => ['review:read']]
        );
    }

    #[Route('/properties/{id}/upload-image', name: 'app_property_upload_image', methods: ['POST'])]
    public function uploadPropertyImage(int $id, Request $request): JsonResponse
    {
        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($property->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $isFeatured = $request->request->getBoolean('is_featured', false);
        $position = $request->request->getInt('position', 0);

        // If this image is featured, unfeature all others
        if ($isFeatured) {
            foreach ($property->getImages() as $image) {
                $image->setIsFeatured(false);
            }
        }

        // If no position specified and no other images, make it position 1
        if ($position === 0 && $property->getImages()->isEmpty()) {
            $position = 1;
        }

        try {
            $fileName = $this->fileUploader->uploadPropertyImage($file, $property->getId());

            $image = new PropertyImage();
            $image->setProperty($property);
            $image->setPath($fileName);
            $image->setIsFeatured($isFeatured);
            $image->setPosition($position);

            $this->entityManager->persist($image);
            $this->entityManager->flush();

            return $this->json(
                $image,
                Response::HTTP_CREATED,
                [],
                ['groups' => ['property_image:read']]
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/properties/{id}/add-availability', name: 'app_property_add_availability', methods: ['POST'])]
    public function addPropertyAvailability(int $id, Request $request): JsonResponse
    {
        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($property->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $startDate = new \DateTime($data['start_date'] ?? 'now');
        $endDate = new \DateTime($data['end_date'] ?? 'now');
        $specialPrice = $data['special_price'] ?? null;

        // Check if dates are valid
        if ($startDate >= $endDate) {
            return $this->json(['error' => 'Start date must be before end date'], Response::HTTP_BAD_REQUEST);
        }

        // Check for conflicts with existing availabilities
        $conflicts = $this->availabilityRepository->findConflictingAvailabilities($property, $startDate, $endDate);
        if (!empty($conflicts)) {
            return $this->json(['error' => 'Conflicting availability exists'], Response::HTTP_CONFLICT);
        }

        $availability = new Availability();
        $availability->setProperty($property);
        $availability->setStartDate($startDate);
        $availability->setEndDate($endDate);
        if ($specialPrice !== null) {
            $availability->setSpecialPrice((string) $specialPrice);
        }

        $this->entityManager->persist($availability);
        $this->entityManager->flush();

        return $this->json(
            $availability,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['availability:read']]
        );
    }

    #[Route('/properties/{id}/approve', name: 'app_property_approve', methods: ['PUT'])]
    public function approveProperty(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $property->setIsActive(true);
        $this->entityManager->flush();

        // Notify owner
        $this->emailService->sendPropertyApprovalNotification($property, true);

        return $this->json(
            $property,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }

    #[Route('/properties/{id}/reject', name: 'app_property_reject', methods: ['PUT'])]
    public function rejectProperty(int $id): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $property->setIsActive(false);
        $this->entityManager->flush();

        // Notify owner
        $this->emailService->sendPropertyApprovalNotification($property, false);

        return $this->json(
            $property,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }

    #[Route('/properties/for-moderation', name: 'app_properties_for_moderation', methods: ['GET'])]
    public function getPropertiesForModeration(): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $properties = $this->propertyRepository->findForModeration();

        return $this->json(
            $properties,
            Response::HTTP_OK,
            [],
            ['groups' => ['property:read']]
        );
    }

    #[Route('/properties/{id}/reviews/stats', name: 'app_property_review_stats', methods: ['GET'])]
    public function getPropertyReviewStats(int $id): JsonResponse
    {
        $property = $this->propertyRepository->find($id);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $averageRating = $this->reviewRepository->calculateAverageRating($property);
        $reviewCount = $this->reviewRepository->countByProperty($property);
        $ratingDistribution = $this->reviewRepository->getRatingDistribution($property);

        return $this->json([
            'average_rating' => $averageRating,
            'review_count' => $reviewCount,
            'rating_distribution' => $ratingDistribution
        ]);
    }
}