<?php

namespace App\Repository;

use App\Entity\Contrat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contrat>
 */
class ContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contrat::class);
    }

    public function findPaginated(int $page = 1, int $limit = 10): array
{
    $offset = ($page - 1) * $limit;

    return $this->createQueryBuilder('c')
        ->orderBy('c.id', 'DESC')
        ->setFirstResult($offset)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}

public function countAll(?string $search = null): int
{
    $qb = $this->createQueryBuilder('c')
        ->select('COUNT(c.id)');

    if ($search) {
        $qb->where('c.nom LIKE :search OR c.prenom LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
}

public function findPaginatedWithSearch(int $page = 1, int $limit = 10, ?string $search = null): array
{
    $offset = ($page - 1) * $limit;

    $qb = $this->createQueryBuilder('c')
        ->orderBy('c.id', 'DESC')
        ->setFirstResult($offset)
        ->setMaxResults($limit);

    if ($search) {
        $qb->where('c.nom LIKE :search OR c.prenom LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    return $qb->getQuery()->getResult();
}
}
