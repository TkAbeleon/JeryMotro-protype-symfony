<?php
// src/Repository/AlertRepository.php

namespace App\Repository;

use App\Entity\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    public function findRecentWithRelations(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->leftJoin('a.detection', 'd')->addSelect('d')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.status, COUNT(a.id) AS total')
            ->groupBy('a.status')
            ->getQuery()
            ->getResult();
    }
}
