<?php

namespace App\Tests\Repository;

use App\Entity\Listing;
use App\Entity\Region;
use App\Entity\Department;
use App\Entity\User;
use App\Repository\ListingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ListingRepositoryTest extends KernelTestCase
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

        // Créez des données de test ici
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Créer un utilisateur de test avec un nom unique
        $username = 'testuser_' . uniqid();
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);

        $this->region = new Region();
        $this->region->setName('Île-de-France');
        $this->entityManager->persist($this->region);

        $this->department = new Department();
        $this->department->setName('Paris');
        $this->department->setCode('75');
        $this->department->setRegion($this->region);
        $this->entityManager->persist($this->department);

        // Créer deux annonces pour la même région et le même département
        for ($i = 1; $i <= 2; $i++) {
            $listing = new Listing();
            $listing->setTitle('Réplique AK47 ' . $i);
            $listing->setDescription('Très bon état, peu utilisée');
            $listing->setPrice(200.00);
            $listing->setState('Bon état');
            $listing->setRegion($this->region->getName());
            $listing->setDepartment($this->department->getName());
            $listing->setCreatedAt(new \DateTimeImmutable());
            $listing->setSlug('replique-ak47-' . uniqid());
            $listing->setUser($user);
            $this->entityManager->persist($listing);
        }

        $this->entityManager->flush();
    }

    public function testSearchByQuery(): void
    {
        $results = $this->listingRepository->search('AK47', null, null);
        $this->assertCount(2, $results);
        $this->assertEquals('Réplique AK47 1', $results[0]->getTitle());
    }

    public function testSearchByRegion(): void
    {
        $results = $this->listingRepository->search(null, $this->region, null);
        $this->assertCount(2, $results);
    }

    public function testSearchByDepartment(): void
    {
        $results = $this->listingRepository->search(null, null, $this->department);
        $this->assertCount(2, $results);
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

    protected function tearDown(): void
    {
        parent::tearDown();

        // Supprimez les données de test
        $this->entityManager->createQuery('DELETE FROM App\Entity\Listing')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Department')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Region')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();

        $this->entityManager->close();

    }
}