<?php

namespace App\Controller;

use App\Form\SearchFormType;
use App\Repository\ListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_listing_search')]
    public function search(Request $request, ListingRepository $listingRepository): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $results = [];
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        if ($form->isSubmitted() && $form->isValid()) {
            $query = $form->get('query')->getData();
            $region = $form->get('region')->getData();
            $department = $form->get('department')->getData();
            $results = $listingRepository->search($query, $region, $department, $sortBy, $sortOrder);
        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'results' => $results,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }
}