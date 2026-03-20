<?php
// src/Controller/AgentController.php

namespace App\Controller;

use App\Service\N8nAgentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agent')]
class AgentController extends AbstractController
{
    public function __construct(
        private readonly N8nAgentService $agentService,
    ) {}

    /**
     * Page chat de l'agent IA.
     */
    #[Route('', name: 'agent_index')]
    public function index(): Response
    {
        return $this->render('agent/index.html.twig');
    }

    /**
     * Endpoint AJAX — envoie la question à n8n et retourne la réponse.
     */
    #[Route('/ask', name: 'agent_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $question  = trim($body['question'] ?? '');
        $userName  = trim($body['user_name'] ?? 'Utilisateur');
        $sessionId = trim($body['session_id'] ?? '');

        if ($question === '') {
            return $this->json([
                'success' => false,
                'error'   => 'La question est vide.',
            ], 400);
        }

        $result = $this->agentService->ask($question, $userName, $sessionId);

        return $this->json($result);
    }
}
