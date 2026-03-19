<?php
// src/Repository/FireDetectionRepository.php

namespace App\Repository;

use App\Entity\FireDetection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FireDetectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FireDetection::class);
    }

    /**
     * Hotspots récents (FRP > 50 MW) pour le dashboard et le formulaire d'alerte.
     */
    public function findRecentHotspots(int $hours = 48, int $limit = 20): array
    {
        $since = new \DateTimeImmutable("-{$hours} hours");

        return $this->createQueryBuilder('f')
            ->where('f.frp > 50')
            ->andWhere('f.acqDatetime >= :since')
            ->setParameter('since', $since)
            ->orderBy('f.frp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques agrégées via DBAL pour le dashboard.
     */
    public function getDashboardStats(\Doctrine\DBAL\Connection $conn): array
    {
        // Détections aujourd'hui
        $todayCount = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM firms_fire_detections WHERE acq_datetime::date = CURRENT_DATE"
        );

        // FRP max (24h)
        $maxFrp = (float) ($conn->fetchOne(
            "SELECT COALESCE(MAX(frp), 0) FROM firms_fire_detections
             WHERE acq_datetime >= NOW() - INTERVAL '24 hours'"
        ) ?? 0);

        // Détections par jour — 7 derniers jours
        $dailyRows = $conn->fetchAllAssociative(
            "SELECT acq_datetime::date AS day, COUNT(*) AS cnt
             FROM firms_fire_detections
             WHERE acq_datetime >= NOW() - INTERVAL '7 days'
             GROUP BY day ORDER BY day"
        );

        // MODIS vs VIIRS — 24h
        $sourceRows = $conn->fetchAllAssociative(
            "SELECT source, COUNT(*) AS cnt
             FROM firms_fire_detections
             WHERE acq_datetime >= NOW() - INTERVAL '24 hours'
             GROUP BY source"
        );

        return [
            'today_count' => $todayCount,
            'max_frp'     => round($maxFrp, 1),
            'daily'       => $dailyRows,
            'by_source'   => $sourceRows,
        ];
    }
}
