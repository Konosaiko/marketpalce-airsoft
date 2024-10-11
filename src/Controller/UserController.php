<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle user registration.
     *
     * This method displays the registration form and processes the form submission.
     * It creates a new user account with hashed password and default user role.
     *
     * @Route("/register", name="app_user_register")
     *
     * @param Request $request The current request
     * @param UserPasswordHasherInterface $userPasswordHasher Service for hashing passwords
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @param ValidatorInterface $validator
     * @return JsonResponse A response containing the registration form or redirecting after successful registration
     */
    #[Route('/register', name: 'app_user_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $data['plainPassword']
            )
        );
        $user->addRole(UserRoles::ROLE_USER);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User registered successfully'], Response::HTTP_CREATED);
    }

    /**
     * Handle user login.
     *
     * This method displays the login form and handles any login errors.
     *
     * @Route("/login", name="app_login")
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse A response containing the login form
     */
    #[Route('/user/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $this->logger->info('Login attempt', ['username' => $data['username'] ?? 'not provided']);

            $username = $data['username'] ?? null;
            $password = $data['password'] ?? null;

            if (!$username || !$password) {
                return new JsonResponse(['message' => 'Username and password are required'], Response::HTTP_BAD_REQUEST);
            }

            $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse(['message' => 'Login successful'], Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Login error: ' . $e->getMessage());
            return new JsonResponse(['message' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handle user logout.
     *
     * This method is intercepted by the logout key on the firewall and doesn't need to have any logic.
     *
     * @Route("/logout", name="app_logout")
     *
     * @throws LogicException This method can be blank - it will be intercepted by the logout key on your firewall
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck()
    {

    }

    #[Route('/user/info', name: 'api_user_info', methods: ['GET', 'OPTIONS'])]
    public function getUserInfo(): JsonResponse
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('User not found');
        }

        return $this->json([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ]);
    }
}