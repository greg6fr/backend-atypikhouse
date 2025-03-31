<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private JWTTokenManagerInterface $jwtManager;
    private UserRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->jwtManager = $jwtManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/register/tenant', name: 'app_register_tenant', methods: ['POST'])]
    public function registerTenant(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setPhone($data['phone'] ?? null);
        $user->setPlainPassword($data['password'] ?? '');
        $user->setRoles(['ROLE_USER']);
        
        // Validate the user entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );
        $user->setPassword($hashedPassword);
        
        // Save user to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Generate token
        $token = $this->jwtManager->create($user);
        
        return $this->json([
            'user' => $user,
            'token' => $token
        ], Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }
    
    #[Route('/register/owner', name: 'app_register_owner', methods: ['POST'])]
    public function registerOwner(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setPhone($data['phone'] ?? null);
        $user->setPlainPassword($data['password'] ?? '');
        $user->setVerificationDocument($data['verificationDocument'] ?? null);
        $user->setRoles(['ROLE_USER', 'ROLE_OWNER']);
        $user->setIsVerified(false); // Owners need to be verified by admin
        
        // Validate the user entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );
        $user->setPassword($hashedPassword);
        
        // Save user to database
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Generate token
        $token = $this->jwtManager->create($user);
        
        return $this->json([
            'user' => $user,
            'token' => $token
        ], Response::HTTP_CREATED, [], ['groups' => 'user:read']);
    }
    
    #[Route('/me', name: 'app_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }
        
        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => ['user:read', 'user:item:read']]
        );
    }
    
    #[Route('/refresh-token', name: 'app_refresh_token', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;
        
        if (!$refreshToken) {
            return $this->json(['error' => 'Refresh token is required'], Response::HTTP_BAD_REQUEST);
        }
        
        // Implement refresh token logic here
        // This would need a refresh token bundle or a custom implementation
        
        return $this->json(['error' => 'Not implemented yet'], Response::HTTP_NOT_IMPLEMENTED);
    }
}