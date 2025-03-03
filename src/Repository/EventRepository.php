<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }
 /**
     * Recherche des événements selon le titre
     *
    *@param string|null $keyword Le mot-clé à rechercher dans les titres
    * @return array Tableau des événements trouvés, formaté pour JSON
    */
   public function search(?string $keyword)
   {
       // Créer un query builder pour la table événements
       $qb = $this->createQueryBuilder('e');
       
       // Si un mot-clé est fourni, filtrer par titre
       if ($keyword && !empty(trim($keyword))) {
           $qb->where('LOWER(e.titre) LIKE LOWER(:keyword)')
              ->setParameter('keyword', '%' . trim($keyword) . '%')
              ->orderBy('e.dateevent', 'DESC'); // Tri par date décroissante
       } else {
           // Si pas de mot-clé, limiter aux 10 événements les plus récents
           $qb->orderBy('e.dateevent', 'DESC')
              ->setMaxResults(10);
       }
       
       // Exécuter la requête
       $results = $qb->getQuery()->getResult();
       
       // Convertir les entités en tableau pour la réponse JSON
       return array_map(function($event) {
           return [
               'id' => $event->getId(),
               'titre' => $event->getTitre(),
               'dateevent' => $event->getDateevent() ? $event->getDateevent()->format('Y-m-d') : null,
               'lieu' => $event->getLieu(),
               'discription' => $event->getDiscription(),
               'nbplace' => $event->getNbplace(),
               // Vous pouvez ajouter d'autres propriétés si nécessaire
           ];
       }, $results);
   }
   public function searchaudio(?string $keyword)
{
    // Créer un query builder pour la table événements
    $qb = $this->createQueryBuilder('e');

    // Si un mot-clé est fourni, filtrer par titre ou description
    if ($keyword && !empty(trim($keyword))) {
        $qb->where('LOWER(e.titre) LIKE LOWER(:keyword)')
           ->orWhere('LOWER(e.discription) LIKE LOWER(:keyword)')
           ->setParameter('keyword', '%' . trim($keyword) . '%')
           ->orderBy('e.dateevent', 'DESC'); // Tri par date décroissante
    } else {
        // Si pas de mot-clé, limiter aux 10 événements les plus récents
        $qb->orderBy('e.dateevent', 'DESC')
           ->setMaxResults(10);
    }

    // Exécuter la requête
    $results = $qb->getQuery()->getResult();

    // Convertir les entités en tableau pour la réponse JSON
    return array_map(function($event) {
        return [
            'id' => $event->getId(),
            'titre' => $event->getTitre(),
            'dateevent' => $event->getDateevent() ? $event->getDateevent()->format('Y-m-d') : null,
            'lieu' => $event->getLieu(),
            'discription' => $event->getDiscription(),
            'nbplace' => $event->getNbplace(),
            // Ajout d'autres propriétés si nécessaire
        ];
    }, $results);
}

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
