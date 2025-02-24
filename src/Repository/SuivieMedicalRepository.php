<?php

namespace App\Repository;

use App\Entity\SuivieMedical;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuivieMedical>
 */
class SuivieMedicalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuivieMedical::class);
    }
    public function findDueReminders(): array
    {
        $today = new \DateTime();
        
        return $this->createQueryBuilder('s')
            ->where('DATE(s.date) = :today')
            ->andWhere('s.rappelEnvoye = :rappelEnvoye')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('rappelEnvoye', false)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return SuivieMedical[] Returns an array of SuivieMedical objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SuivieMedical
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
