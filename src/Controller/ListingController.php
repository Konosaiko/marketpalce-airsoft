<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\Region;
use App\Entity\User;
use App\Form\ListingFormType;
use App\Form\MessageFormType;
use App\Repository\DepartmentRepository;
use App\Service\ListingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/listing')]
class ListingController extends AbstractController
{
    private ListingService $listingService;

    public function __construct(ListingService $listingService)
    {
        $this->listingService = $listingService;
    }

    /**
     * Display the form to create a new listing and handle form submission.
     */
    #[Route('/new', name: 'app_listing_new')]
    #[IsGranted("ROLE_USER")]
    public function new(Request $request): Response
    {
        $form = $this->createForm(ListingFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer une annonce.');
            }

            $listingData = $form->getData();
            $listingData->setRegion($form->get('region')->getData()->getName());
            $listingData->setDepartment($form->get('department')->getData()->getName());
            $listingData->setPhotoFiles($form->get('photoFiles')->getData());

            $this->listingService->createListing($listingData, $user);

            $this->addFlash('success', 'Annonce créée avec succès.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('listing/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retrieve departments for a given region (AJAX endpoint).
     */
    #[Route('/get-departments/{id}', name: 'app_get_departments', methods: ['GET'])]
    public function getDepartments(Region $region, DepartmentRepository $departmentRepository): JsonResponse
    {
        $departments = $departmentRepository->findBy(['region' => $region]);

        $departmentsArray = array_map(function($department) {
            return ['id' => $department->getId(), 'name' => $department->getName()];
        }, $departments);

        return new JsonResponse($departmentsArray);
    }

    /**
     * Display the form to edit an existing listing and handle form submission.
     */
    #[Route('/edit/{slug}', name: 'app_listing_edit')]
    public function edit(Request $request, Listing $listing): Response
    {
        if ($listing->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette annonce.');
        }

        $form = $this->createForm(ListingFormType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->listingService->updateListing($listing, $form->getData());

            $this->addFlash('success', 'Votre annonce a été mise à jour avec succès.');

            return $this->redirectToRoute('app_listing_show', ['slug' => $listing->getSlug()]);
        }

        return $this->render('listing/edit.html.twig', [
            'form' => $form->createView(),
            'listing' => $listing,
        ]);
    }

    /**
     * Handle the deletion of a listing.
     */
    #[Route('/delete/{slug}', name: 'app_listing_delete')]
    public function delete(Request $request, Listing $listing): Response
    {
        if ($listing->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette annonce.');
        }

        if ($this->isCsrfTokenValid('delete'.$listing->getId(), $request->request->get('_token'))) {
            $this->listingService->deleteListing($listing);
            $this->addFlash('success', 'Votre annonce a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_home');
    }

    /**
     * Display details of a specific listing.
     */
    #[Route('/{slug}', name: 'app_listing_show', methods: ['GET'])]
    public function show(Listing $listing): Response
    {
        return $this->render('listing/show.html.twig', [
            'listing' => $listing,
        ]);
    }

    /**
     * Handle contacting the seller of a listing.
     */
    #[Route('/{slug}/contact', name: 'app_listing_contact')]
    #[IsGranted('ROLE_USER')]
    public function contact(Request $request, Listing $listing): Response
    {
        $sender = $this->getUser();
        if (!$sender instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour envoyer un message.');
        }

        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->get('content')->getData();

            try {
                $this->listingService->contactSeller($sender, $listing, $content);
                $this->addFlash('success', 'Votre message a été envoyé avec succès.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_listing_show', ['slug' => $listing->getSlug()]);
        }

        return $this->render('message/new.html.twig', [
            'listing' => $listing,
            'form' => $form->createView(),
        ]);
    }
}
