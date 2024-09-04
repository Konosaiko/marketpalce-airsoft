<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\ListingPhoto;
use App\Form\ListingFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/listing')]
class ListingController extends AbstractController
{
    #[Route('/new', name: 'app_listing_new')]
    #[IsGranted("ROLE_USER")]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $listing = new Listing();
        $form = $this->createForm(ListingFormType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $listing->setUser($this->getUser());
            $listing->setCreatedAt(new \DateTimeImmutable());

            $slug = $slugger->slug($listing->getTitle())->lower();
            $listing->setSlug($slug);

            $photoFiles = $form->get('photoFiles')->getData();
            foreach ($photoFiles as $photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('listings_photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $photo = new ListingPhoto();
                $photo->setFilename($newFilename);
                $listing->addListingPhoto($photo);
            }

            $entityManager->persist($listing);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce créée avec succès.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('listing/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{slug}', name: 'app_listing_edit')]
    public function edit(Request $request, Listing $listing, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($listing->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette annonce.');
        }

        $originalTitle = $listing->getTitle();
        $form = $this->createForm(ListingFormType::class, $listing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($originalTitle !== $listing->getTitle()) {
                $slug = $slugger->slug($listing->getTitle())->lower();
                $listing->setSlug($slug);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre annonce a été mise à jour avec succès.');

            return $this->redirectToRoute('app_listing_show', ['id' => $listing->getId()]);
        }

        return $this->render('listing/edit.html.twig', [
            'form' => $form->createView(),
            'listing' => $listing,
        ]);
    }

    #[Route('/delete/{slug}', name: 'app_listing_delete')]
    public function delete(Request $request, Listing $listing, EntityManagerInterface $entityManager): Response
    {
        if ($listing->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette anonce.');
        }

        if ($this->isCsrfTokenValid('delete'.$listing->getId(), $request->request->get('_token'))) {
            $entityManager->remove($listing);
            $entityManager->flush();

            $this->addFlash('success', 'Votre annonce a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_home');

    }

    #[Route('/{slug}', name: 'app_listing_show', methods: ['GET'])]
    public function show(Listing $listing): Response
    {
        return $this->render('listing/show.html.twig', [
            'listing' => $listing,
        ]);
    }
}
