<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository 
{
    /*public function loadUserByIdentifier(string $identifier): ?Utilisateur
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }*/

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function searchUsers(string $query, string $sortField = 'id', string $sortDirection = 'asc'): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.nom LIKE :query')
            ->orWhere('u.prenom LIKE :query')
            ->orWhere('u.email LIKE :query')
            ->orWhere('u.telephone LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.' . $sortField, $sortDirection);
        
        return $qb->getQuery()->getResult();
    }

    public function findAllSorted(string $sortField = 'id', string $sortDirection = 'asc'): array
    {
        // Validate sort field to prevent SQL injection
        $allowedFields = ['id', 'nom', 'prenom', 'email', 'telephone', 'role'];
        if (!in_array($sortField, $allowedFields)) {
            $sortField = 'id';
        }
        
        // Validate sort direction
        $direction = strtolower($sortDirection) === 'desc' ? 'DESC' : 'ASC';
        
        return $this->createQueryBuilder('u')
            ->orderBy('u.' . $sortField, $direction)
            ->getQuery()
            ->getResult();
    }
/**
 * Compte le nombre d'utilisateurs par rôle (sans Admin)
 */
public function countByRole(): array
{
    return $this->createQueryBuilder('u')
        ->select('u.role as role, COUNT(u.id) as count')
        ->where('u.role != :adminRole')
        ->setParameter('adminRole', \App\Enum\UserRole::ADMIN)
        ->groupBy('u.role')
        ->getQuery()
        ->getResult();
}
    /**
     * Récupère des statistiques détaillées par rôle
     */
    /**
 * Récupère des statistiques détaillées par rôle
 */
/**
 * Récupère des statistiques détaillées par rôle (sans Admin)
 */
public function getStatsByRole(): array
{
    $stats = [];
    
    // Nombre total d'utilisateurs (sans Admin)
    $stats['total'] = $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role != :adminRole')
        ->setParameter('adminRole', \App\Enum\UserRole::ADMIN)
        ->getQuery()
        ->getSingleScalarResult();
    
    // Statistiques par rôle
    $roleStats = $this->countByRole();
    $formattedStats = [];
    
    foreach ($roleStats as $roleStat) {
        // Convertir l'enum en chaîne
        $roleName = $roleStat['role']->value;
        $formattedStats[$roleName] = $roleStat['count'];
    }
    
    $stats['by_role'] = $formattedStats;
    
    // Pourcentage par rôle
    $stats['percentage'] = [];
    foreach ($formattedStats as $role => $count) {
        $stats['percentage'][$role] = round(($count / $stats['total']) * 100, 2);
    }
    
    // Spécialités des médecins
    $specialties = $this->createQueryBuilder('u')
        ->select('u.specialite as specialty, COUNT(u.id) as count')
        ->where('u.role = :role')
        ->setParameter('role', \App\Enum\UserRole::MEDECIN)
        ->andWhere('u.specialite IS NOT NULL')
        ->groupBy('u.specialite')
        ->getQuery()
        ->getResult();
    
    $stats['specialties'] = $specialties;
    
    // Compter les médecins vérifiés vs non vérifiés
    $stats['verified_doctors'] = $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role = :role')
        ->andWhere('u.isVerified = :verified')
        ->setParameter('role', \App\Enum\UserRole::MEDECIN)
        ->setParameter('verified', true)
        ->getQuery()
        ->getSingleScalarResult();
    
    $stats['unverified_doctors'] = $this->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.role = :role')
        ->andWhere('u.isVerified = :verified')
        ->setParameter('role', \App\Enum\UserRole::MEDECIN)
        ->setParameter('verified', false)
        ->getQuery()
        ->getSingleScalarResult();
    
    return $stats;
}

//    /**
//     * @return Utilisateur[] Returns an array of Utilisateur objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Utilisateur
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
