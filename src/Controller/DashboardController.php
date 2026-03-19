<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Repository\AlertRepository;
use App\Repository\FireDetectionRepository;
use App\Repository\UserRepository;
use App\Service\PdfExportService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly FireDetectionRepository $detectionRepo,
        private readonly AlertRepository         $alertRepo,
        private readonly UserRepository          $userRepo,
        private readonly PdfExportService        $pdfService,
        private readonly Connection              $conn,
    ) {}

    #[Route('', name: 'dashboard_index')]
    public function index(): Response
    {
        // ── Stats FIRMS (DBAL — requêtes directes) ───────────
        $stats = $this->detectionRepo->getDashboardStats($this->conn);

        // ── Stats Symfony ────────────────────────────────────
        $totalUsers  = count($this->userRepo->findAll());
        $alertCounts = $this->alertRepo->countByStatus();

        $sentAlerts = 0;
        foreach ($alertCounts as $row) {
            if ($row['status'] === 'sent') {
                $sentAlerts = (int) $row['total'];
            }
        }

        // ── Hotspots récents (48h, FRP > 50 MW) ─────────────
        $hotspots = $this->detectionRepo->findRecentHotspots(48, 10);

        // ── Chart.js — séries JSON ────────────────────────────
        $chartLabels  = array_map(fn($r) => $r['day'],  $stats['daily']);
        $chartCounts  = array_map(fn($r) => (int) $r['cnt'], $stats['daily']);

        $sourceLabels = array_map(fn($r) => $r['source'], $stats['by_source']);
        $sourceCounts = array_map(fn($r) => (int) $r['cnt'],   $stats['by_source']);

        return $this->render('dashboard/index.html.twig', [
            'today_count'   => $stats['today_count'],
            'max_frp'       => $stats['max_frp'],
            'total_users'   => $totalUsers,
            'sent_alerts'   => $sentAlerts,
            'hotspots'      => $hotspots,
            'chart_labels'  => json_encode($chartLabels),
            'chart_counts'  => json_encode($chartCounts),
            'source_labels' => json_encode($sourceLabels),
            'source_counts' => json_encode($sourceCounts),
        ]);
    }

    /**
     * Export PDF — rapport des 7 derniers jours.
     */
    #[Route('/export/pdf', name: 'dashboard_export_pdf')]
    public function exportPdf(): Response
    {
        $stats    = $this->detectionRepo->getDashboardStats($this->conn);
        $hotspots = $this->detectionRepo->findRecentHotspots(168, 50); // 7 jours
        $alerts   = $this->alertRepo->findRecentWithRelations(30);

        $pdf = $this->pdfService->generate('pdf/report.html.twig', [
            'generated_at' => new \DateTimeImmutable(),
            'stats'        => $stats,
            'hotspots'     => $hotspots,
            'alerts'       => $alerts,
        ]);

        $filename = sprintf('jeryMotro-rapport-%s.pdf', date('Y-m-d'));
        return $this->pdfService->streamResponse($pdf, $filename);
    }
}
