<?php
// src/Repository/RatingRepository.php
namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }
    
    // Vous pouvez ajouter des méthodes personnalisées ici
    public function findBySuiviMedical($suiviId)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.ratings = :suiviId')  // Changez 'r.suiviMedical' par 'r.ratings'
            ->setParameter('suiviId', $suiviId)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function getAverageRatingForTraitement($traitementId)
    {
        return $this->createQueryBuilder('r')
            ->select('AVG(r.value) as average')
            ->join('r.ratings', 's')  // Changez 'r.suiviMedical' par 'r.ratings'
            ->andWhere('s.id_historique = :traitementId')  // Assurez-vous que c'est le bon nom de champ
            ->setParameter('traitementId', $traitementId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}