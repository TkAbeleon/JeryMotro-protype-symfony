<?php
// src/Service/N8nAgentService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Appelle le webhook n8n "jeryMotro-agent" pour obtenir
 * des réponses de l'agent IA.
 */
class N8nAgentService
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface     $logger,
        private readonly string              $n8nAgentWebhookUrl,
    ) {}

    /**
     * Envoie une question à l'agent IA et retourne la réponse brute.
     *
     * @return array{success: bool, data: mixed, error: string|null}
     */
    public function ask(string $question, string $userName = 'Utilisateur', string $sessionId = ''): array
    {
        if (!$sessionId) {
            $sessionId = 'sess_' . bin2hex(random_bytes(8));
        }

        $payload = [
            'question'   => $question,
            'user_name'  => $userName,
            'session_id' => $sessionId,
        ];

        try {
            $response = $this->client->request('POST', $this->n8nAgentWebhookUrl, [
                'json'    => $payload,
                'timeout' => 30,
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body       = $response->getContent(false);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('Agent IA a répondu (HTTP {code}).', [
                    'code' => $statusCode,
                ]);

                // Essaye de décoder du JSON, sinon retourne le texte brut
                $decoded = json_decode($body, true);

                return [
                    'success'    => true,
                    'data'       => $decoded ?? $body,
                    'session_id' => $sessionId,
                    'error'      => null,
                ];
            }

            $this->logger->error('Agent IA a retourné HTTP {code}.', [
                'code' => $statusCode,
                'body' => $body,
            ]);

            return [
                'success'    => false,
                'data'       => null,
                'session_id' => $sessionId,
                'error'      => "L'agent a retourné HTTP $statusCode.",
            ];

        } catch (\Throwable $e) {
            $this->logger->error('Erreur appel agent IA : {msg}', [
                'msg' => $e->getMessage(),
            ]);

            return [
                'success'    => false,
                'data'       => null,
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ];
        }
    }
}
