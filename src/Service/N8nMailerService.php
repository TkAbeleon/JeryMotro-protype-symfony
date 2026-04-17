<?php
// src/Service/N8nMailerService.php

namespace App\Service;

use App\Entity\Alert;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Envoie les alertes email via le webhook n8n.
 * Symfony POST → n8n → Email SMTP.
 */
class N8nMailerService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface     $logger,
        private readonly string              $n8nWebhookUrl,
        private readonly string              $hfToken,
    ) {}

    /**
     * Envoie une alerte à n8n et retourne true si succès.
     * Met à jour le statut de l'alerte in-place (pending → sent | failed).
     */
    public function sendAlert(Alert $alert): bool
    {
        $user      = $alert->getUser();
        $detection = $alert->getDetection();

        $payload = [
            'alert_id' => $alert->getId(),
            'email'    => $user?->getEmail(),
            'name'     => $user?->getName(),
            'message'  => $alert->getMessage(),
            'detection' => $detection ? [
                'id'           => $detection->getId(),
                'latitude'     => $detection->getLatitude(),
                'longitude'    => $detection->getLongitude(),
                'frp'          => $detection->getFrp(),
                'source'       => $detection->getSource(),
                'satellite'    => $detection->getSatellite(),
                'acq_datetime' => $detection->getAcqDatetime()->format('c'),
                'risk_label'   => $detection->getRiskLabel(),
            ] : null,
        ];

        try {
            $response = $this->client->request('POST', $this->n8nWebhookUrl, [
                'json'    => $payload,
                'timeout' => 15,
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $this->hfToken,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $alert->setStatus(Alert::STATUS_SENT);
                $alert->setSentAt(new \DateTimeImmutable());
                $this->logger->info('Alerte #{id} envoyée via n8n.', ['id' => $alert->getId()]);
                return true;
            }

            $this->logger->error('n8n a retourné HTTP {code}.', [
                'id'   => $alert->getId(),
                'code' => $statusCode,
                'body' => $response->getContent(false),
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur appel n8n : {msg}', [
                'id'  => $alert->getId(),
                'msg' => $e->getMessage(),
            ]);
        }

        $alert->setStatus(Alert::STATUS_FAILED);
        return false;
    }
}
