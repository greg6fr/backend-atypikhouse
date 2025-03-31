<?php

namespace App\Controller\Message;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\MessageRepository;
use App\Repository\PropertyRepository;
use App\Repository\UserRepository;
use App\Service\Email\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class MessageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private MessageRepository $messageRepository;
    private UserRepository $userRepository;
    private PropertyRepository $propertyRepository;
    private BookingRepository $bookingRepository;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        PropertyRepository $propertyRepository,
        BookingRepository $bookingRepository,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->propertyRepository = $propertyRepository;
        $this->bookingRepository = $bookingRepository;
        $this->emailService = $emailService;
    }

    #[Route('/messages', name: 'app_messages_list', methods: ['GET'])]
    public function getMessages(): JsonResponse
    {
        $user = $this->getUser();
        $messages = $this->messageRepository->findForUser($user);

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => ['message:read']]
        );
    }

    #[Route('/messages/unread', name: 'app_messages_unread', methods: ['GET'])]
    public function getUnreadMessages(): JsonResponse
    {
        $user = $this->getUser();
        $messages = $this->messageRepository->findUnreadForUser($user);

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => ['message:read']]
        );
    }

    #[Route('/messages/conversations', name: 'app_messages_conversations', methods: ['GET'])]
    public function getConversations(): JsonResponse
    {
        $user = $this->getUser();
        $conversations = $this->messageRepository->findConversationsForUser($user);

        return $this->json(
            $conversations,
            Response::HTTP_OK
        );
    }

    #[Route('/messages/conversation/{userId}', name: 'app_messages_conversation', methods: ['GET'])]
    public function getConversation(int $userId): JsonResponse
    {
        $user = $this->getUser();
        $otherUser = $this->userRepository->find($userId);

        if (!$otherUser) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $messages = $this->messageRepository->findConversation($user, $otherUser);

        // Mark all messages as read
        foreach ($messages as $message) {
            if ($message->getReceiver() === $user && !$message->isIsRead()) {
                $message->setIsRead(true);
            }
        }
        $this->entityManager->flush();

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => ['message:read']]
        );
    }

    #[Route('/messages', name: 'app_messages_send', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $receiverId = $data['receiver_id'] ?? null;
        $content = $data['content'] ?? null;
        $subject = $data['subject'] ?? null;
        $propertyId = $data['property_id'] ?? null;
        $bookingId = $data['booking_id'] ?? null;

        if (!$receiverId || !$content) {
            return $this->json(['error' => 'Receiver and content are required'], Response::HTTP_BAD_REQUEST);
        }

        $receiver = $this->userRepository->find($receiverId);
        if (!$receiver) {
            return $this->json(['error' => 'Receiver not found'], Response::HTTP_NOT_FOUND);
        }

        $sender = $this->getUser();
        
        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setSubject($subject);
        $message->setPropertyId($propertyId);
        $message->setBookingId($bookingId);
        $message->setIsRead(false);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Send email notification
        $this->emailService->sendNewMessageNotification($message);

        return $this->json(
            $message,
            Response::HTTP_CREATED,
            [],
            ['groups' => ['message:read']]
        );
    }

    #[Route('/messages/{id}/read', name: 'app_messages_mark_read', methods: ['PUT'])]
    public function markAsRead(int $id): JsonResponse
    {
        $message = $this->messageRepository->find($id);

        if (!$message) {
            return $this->json(['error' => 'Message not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($message->getReceiver() !== $user) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $message->setIsRead(true);
        $this->entityManager->flush();

        return $this->json(
            $message,
            Response::HTTP_OK,
            [],
            ['groups' => ['message:read']]
        );
    }

    #[Route('/messages/property/{propertyId}', name: 'app_messages_property', methods: ['GET'])]
    public function getPropertyMessages(int $propertyId): JsonResponse
    {
        $property = $this->propertyRepository->find($propertyId);

        if (!$property) {
            return $this->json(['error' => 'Property not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($property->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $messages = $this->messageRepository->findByPropertyId($propertyId);

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => ['message:read']]
        );
    }

    #[Route('/messages/booking/{bookingId}', name: 'app_messages_booking', methods: ['GET'])]
    public function getBookingMessages(int $bookingId): JsonResponse
    {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();
        if ($booking->getTenant() !== $user && $booking->getProperty()->getOwner() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $messages = $this->messageRepository->findByBookingId($bookingId);

        return $this->json(
            $messages,
            Response::HTTP_OK,
            [],
            ['groups' => ['message:read']]
        );
    }
}