<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    private $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display the profile of the current user.
     */
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found or invalid user type.');
        }

        $listings = $this->profileService->getUserListings($user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'listings' => $listings,
        ]);
    }

    /**
     * Display the form to edit the current user's profile and handle form submission.
     */
    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User not found or invalid user type.');
        }

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $this->profileService->updateProfile($user, $form->getData(), $newPassword);
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
