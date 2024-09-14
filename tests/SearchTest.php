<?php

namespace App\Tests\Repository;

use App\Entity\Listing;
use App\Entity\Region;
use App\Entity\Department;
use App\Entity\User;
use App\Repository\ListingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class SearchTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ListingRepository $listingRepository;
    private Region $region;
    private Department $department;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->listingRepository = $this->entityManager->getRepository(Listing::class);

        $this->createTestData();
    }

    private function createTestData(): void
    {
        $username = 'testuser_' . uniqid();
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword('password');
        $user->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($user);

        $this->region = new Region();
        $this->region->setName('Île-de-France');
        $this->entityManager->persist($this->region);

        $this->department = new Department();
        $this->department->setName('Paris');
        $this->department->setCode('75');
        $this->department->setRegion($this->region);
        $this->entityManager->persist($this->department);

        $listings = [
            ['Réplique AK47 1', 200.00, new \DateTimeImmutable('2023-01-01')],
            ['Réplique AK47 2', 150.00, new \DateTimeImmutable('2023-02-01')],
            ['Réplique M4 1', 300.00, new \DateTimeImmutable('2023-03-01')],
        ];

        foreach ($listings as $listingData) {
            $listing = new Listing();
            $listing->setTitle($listingData[0]);
            $listing->setDescription('Très bon état, peu utilisée');
            $listing->setPrice($listingData[1]);
            $listing->setState('Bon état');
            $listing->setRegion($this->region->getName());
            $listing->setDepartment($this->department->getName());
            $listing->setCreatedAt($listingData[2]);
            $listing->setSlug(strtolower(str_replace(' ', '-', $listingData[0]) . '-' . uniqid()));
            $listing->setUser($user);
            $this->entityManager->persist($listing);
        }

        $this->entityManager->flush();
    }

    public function testSearchByQuery(): void
    {
        $results = $this->listingRepository->search('AK47', null, null);
        $this->assertCount(2, $results);
        $titles = array_map(function($listing) { return $listing->getTitle(); }, $results);
        $this->assertContains('Réplique AK47 1', $titles);
        $this->assertContains('Réplique AK47 2', $titles);
    }

    public function testSearchByRegion(): void
    {
        $results = $this->listingRepository->search(null, $this->region, null);
        $this->assertCount(3, $results);
    }

    public function testSearchByDepartment(): void
    {
        $results = $this->listingRepository->search(null, null, $this->department);
        $this->assertCount(3, $results);
    }

    public function testSearchByQueryAndRegion(): void
    {
        $results = $this->listingRepository->search('AK47', $this->region, null);
        $this->assertCount(2, $results);
    }

    public function testSearchNoResults(): void
    {
        $results = $this->listingRepository->search('Inexistant', null, null);
        $this->assertCount(0, $results);
    }

    public function testSearchSortByPriceAsc(): void
    {
        $results = $this->listingRepository->search(null, null, null, 'price', 'ASC');
        $this->assertCount(3, $results);
        $this->assertEquals(150.00, $results[0]->getPrice());
        $this->assertEquals(300.00, $results[2]->getPrice());
    }

    public function testSearchSortByPriceDesc(): void
    {
        $results = $this->listingRepository->search(null, null, null, 'price', 'DESC');
        $this->assertCount(3, $results);
        $this->assertEquals(300.00, $results[0]->getPrice());
        $this->assertEquals(150.00, $results[2]->getPrice());
    }

    public function testSearchSortByCreatedAtAsc(): void
    {
        $results = $this->listingRepository->search(null, null, null, 'createdAt', 'ASC');
        $this->assertCount(3, $results);
        $this->assertEquals('2023-01-01', $results[0]->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('2023-03-01', $results[2]->getCreatedAt()->format('Y-m-d'));
    }

    public function testSearchSortByCreatedAtDesc(): void
    {
        $results = $this->listingRepository->search(null, null, null, 'createdAt', 'DESC');
        $this->assertCount(3, $results);
        $this->assertEquals('2023-03-01', $results[0]->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('2023-01-01', $results[2]->getCreatedAt()->format('Y-m-d'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->createQuery('DELETE FROM App\Entity\Listing')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Department')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Region')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        $this->entityManager->close();
    }
}