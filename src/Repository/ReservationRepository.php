<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

//    /**
/**
 * @param string|null $keyword Le mot-clé à rechercher dans les noms et emails
 * @return array Tableau des réservations trouvées, formaté pour JSON
 */
public function search(?string $keyword)
{
    // Créer un query builder pour la table réservations
    $qb = $this->createQueryBuilder('r');

    // Si un mot-clé est fourni, filtrer par nom ou email
    if ($keyword && !empty(trim($keyword))) {
        $qb->where('LOWER(r.nomreserv) LIKE LOWER(:keyword)')
           ->orWhere('LOWER(r.mail) LIKE LOWER(:keyword)')
           ->setParameter('keyword', '%' . trim($keyword) . '%')
           ->orderBy('r.id', 'DESC'); // Tri par ID décroissant
    } else {
        // Si pas de mot-clé, limiter aux 10 réservations les plus récentes
        $qb->orderBy('r.id', 'DESC')
           ->setMaxResults(10);
    }

    // Exécuter la requête
    $results = $qb->getQuery()->getResult();

    // Convertir les entités en tableau pour la réponse JSON
    return array_map(function($reservation) {
        return [
            'id' => $reservation->getId(),
            'nomreserv' => $reservation->getNomreserv(),
            'nbrpersonne' => $reservation->getNbrpersonne(),
            // Vous pouvez ajouter d'autres propriétés si nécessaire
        ];
    }, $results);
}
//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
