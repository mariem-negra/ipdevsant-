<?php

namespace App\Repository;

use App\Entity\Demande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demande>
 */
class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }
    public function findDemandeByPatientId(int $patientId): ?Demande
    {
    return $this->createQueryBuilder('d')
        ->andWhere('d.Patient = :patientId')
        ->setParameter('patientId', $patientId)
        ->orderBy('d.Date', 'DESC') // Order by the most recent date
        ->setMaxResults(1) // Get only the latest one
        ->getQuery()
        ->getOneOrNullResult();
    }
    public function findPendingDemandes(): array
{
    return $this->createQueryBuilder('d')
        // Left join the Recommandation entity where r.demande equals the Demande object (d)
        ->leftJoin('App\Entity\Recommandation', 'r', 'WITH', 'r.demande = d')
        ->andWhere('r.id IS NULL')
        ->getQuery()
        ->getResult();
}

    //    /**
    //     * @return Demande[] Returns an array of Demande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Demande
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
