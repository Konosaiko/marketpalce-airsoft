<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Listing;
use App\Entity\Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Listing>
 */
class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    /**
     * Search for listings based on various criteria.
     *
     * @param string|null $query The search query to match against title and description
     * @param Region|null $region The region to filter by
     * @param Department|null $department The department to filter by
     * @param string|null $sortBy The field to sort by (default: 'createdAt')
     * @param string $sortOrder The sort order ('ASC' or 'DESC', default: 'DESC')
     * @return array An array of Listing entities matching the search criteria
     */
    public function search(?string $query, ?Region $region, ?Department $department, ?string $sortBy = 'createdAt', string $sortOrder = 'DESC')
    {
        $qb = $this->createQueryBuilder('l');

        if ($query) {
            $qb->andWhere('l.title LIKE :query OR l.description LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($region) {
            $qb->andWhere('l.region = :region')
                ->setParameter('region', $region->getName());
        }

        if ($department) {
            $qb->andWhere('l.department = :department')
                ->setParameter('department', $department->getName());
        }

        switch ($sortBy) {
            case 'price':
                $qb->orderBy('l.price', $sortOrder);
                break;
            case 'createdAt':
            default:
                $qb->orderBy('l.createdAt', $sortOrder);
                break;
        }

        return $qb->getQuery()->getResult();
    }
}