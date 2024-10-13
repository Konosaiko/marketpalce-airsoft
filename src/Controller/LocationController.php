<?php

namespace App\Controller;

use App\Repository\RegionRepository;
use App\Repository\DepartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class LocationController extends AbstractController
{
    #[Route('/regions', name: 'regions_list', methods: ['GET'])]
    public function getRegions(RegionRepository $regionRepository): JsonResponse
    {
        $regions = $regionRepository->findAll();
        $data = [];
        foreach ($regions as $region) {
            $data[] = [
                'id' => $region->getId(),
                'name' => $region->getName(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/regions/{id}/departments', name: 'departments_by_region', methods: ['GET'])]
    public function getDepartmentsByRegion(int $id, DepartmentRepository $departmentRepository): JsonResponse
    {
        $departments = $departmentRepository->findBy(['region' => $id]);
        $data = [];
        foreach ($departments as $department) {
            $data[] = [
                'id' => $department->getId(),
                'name' => $department->getName(),
            ];
        }
        return $this->json($data);
    }
}