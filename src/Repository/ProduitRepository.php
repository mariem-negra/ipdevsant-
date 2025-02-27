<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function searchByName(?string $searchTerm)
    {
        $qb = $this->createQueryBuilder('p');
        
        if ($searchTerm) {
            $qb->where('p.nom LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }
        
        return $qb->orderBy('p.id', 'DESC')
                 ->getQuery()
                 ->getResult();
    }
    
    public function findAllSorted(string $sortBy = 'nom', string $direction = 'ASC')
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.' . $sortBy, $direction)
            ->getQuery()
            ->getResult();
    }
    
    // Find products created by a specific admin
    public function findByCreator(Utilisateur $admin)
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdBy = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }




// In ProduitRepository.php
// src/Repository/ProduitRepository.php
public function findByPriceRange(float $minPrice, float $maxPrice): array
{
    return $this->createQueryBuilder('p')
        ->andWhere('p.prix >= :minPrice')
        ->andWhere('p.prix <= :maxPrice')
        ->setParameter('minPrice', $minPrice)
        ->setParameter('maxPrice', $maxPrice)
        ->orderBy('p.prix', 'ASC')
        ->getQuery()
        ->getResult();
}

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
