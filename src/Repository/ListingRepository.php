<?php

namespace App\Repository;

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

    public function search(?string $query, ?Region $region)
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

        return $qb->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
