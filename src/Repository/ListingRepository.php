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

    public function search(?string $query, ?Region $region, ?Department $department)
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

        return $qb->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
