<?php

namespace App\Repository;

use App\Entity\HistoriqueTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueTraitement>
 */
class HistoriqueTraitementRepository extends ServiceEntityRepository
{ public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueTraitement::class);
    }

    public function searchByMaladie(string $maladie): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.maladie LIKE :maladie')
            ->setParameter('maladie', '%' . $maladie . '%')
            ->getQuery()
            ->getResult();
    }
    public function countByMaladie(): array
{
    return $this->createQueryBuilder('h')
        ->select('h.maladie, COUNT(h.id) as count')
        ->groupBy('h.maladie')
        ->getQuery()
        ->getResult();
}
    //    /**
    //     * @return HistoriqueTraitement[] Returns an array of HistoriqueTraitement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?HistoriqueTraitement
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
