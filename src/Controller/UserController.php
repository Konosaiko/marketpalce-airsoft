<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserRoles;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/user')]
class UserController extends AbstractController
{
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
     * @return Response A response containing the registration form or redirecting after successful registration
     */
    #[Route('/register', name: 'app_user_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->addRole(UserRoles::ROLE_USER);
            $entityManager->persist($user);
            $entityManager->flush();

            // Ajouter envoi de mail de confirmation

            return $this->redirectToRoute('app_home');
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Handle user login.
     *
     * This method displays the login form and handles any login errors.
     *
     * @Route("/login", name="app_login")
     *
     * @param AuthenticationUtils $authenticationUtils Utilities for authentication
     * @return Response A response containing the login form
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
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
}