<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    // In src/Repository/CommandeRepository.php

// src/Repository/CommandeRepository.php

public function getCheckoutStats(): array
{
    $qb = $this->createQueryBuilder('c');
    
    $result = $qb
        ->select('COUNT(c.id) as totalCheckouts')
        ->addSelect('SUM(c.totalAmount) as totalAmount')
        ->where('c.paymentStatus = :status')
        ->setParameter('status', 'completed') // Ensure this matches the status in your database
        ->getQuery()
        ->getOneOrNullResult();
        
    return [
        'totalCheckouts' => (int)($result['totalCheckouts'] ?? 0),
        'totalAmount' => (float)($result['totalAmount'] ?? 0)
    ];
}
//    /**
//     * @return Commande[] Returns an array of Commande objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Commande
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
