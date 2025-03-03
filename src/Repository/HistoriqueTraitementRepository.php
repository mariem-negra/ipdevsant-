<?php

namespace App\Repository;

use App\Entity\HistoriqueTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriqueTraitement>
 */
class HistoriqueTraitementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueTraitement::class);
    }

    public function searchMultiCriteria(array $criteria, ?string $sortField = null, ?string $sortDirection = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('h');
        
        // Critères de recherche
        if (!empty($criteria['nom'])) {
            $qb->andWhere('h.nom LIKE :nom')
               ->setParameter('nom', '%' . $criteria['nom'] . '%');
        }
        
        if (!empty($criteria['prenom'])) {
            $qb->andWhere('h.prenom LIKE :prenom')
               ->setParameter('prenom', '%' . $criteria['prenom'] . '%');
        }
        
        if (!empty($criteria['maladie'])) {
            $qb->andWhere('h.maladie LIKE :maladie')
               ->setParameter('maladie', '%' . $criteria['maladie'] . '%');
        }
        
        if (!empty($criteria['typeTraitement'])) {
            $qb->andWhere('h.typeTraitement LIKE :typeTraitement')
               ->setParameter('typeTraitement', '%' . $criteria['typeTraitement'] . '%');
        }
        
        // Tri
        if ($sortField && property_exists(HistoriqueTraitement::class, $sortField)) {
            $qb->orderBy('h.' . $sortField, $sortDirection === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('h.id', 'DESC'); // Tri par défaut
        }
        
        return $qb->getQuery()->getResult();
    }

    public function countByMaladie(): array
    {
        return $this->createQueryBuilder('h')
            ->select('h.maladie, COUNT(h.id) as count')
            ->groupBy('h.maladie')
            ->getQuery()
            ->getResult();
    }
}