<?php

namespace App\Repository;

use App\Entity\Reminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reminder::class);
    }

    public function save(Reminder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reminder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.dateTime >= :startDate')
            ->andWhere('r.dateTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findTodayReminders(): array
   {
    $qb = $this->createQueryBuilder('r')
        ->where('DATE(r.dateTime) = CURRENT_DATE()')
        ->orderBy('r.dateTime', 'ASC');
    
    return $qb->getQuery()->getResult();
   }
}