<?php
// src/Controller/AlertController.php

namespace App\Controller;

use App\Entity\Alert;
use App\Form\AlertType;
use App\Repository\AlertRepository;
use App\Service\N8nMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alerts')]
class AlertController extends AbstractController
{
    public function __construct(
        private readonly AlertRepository        $alertRepo,
        private readonly EntityManagerInterface $em,
        private readonly N8nMailerService       $mailer,
    ) {}

    #[Route('', name: 'alert_index')]
    public function index(): Response
    {
        return $this->render('alert/index.html.twig', [
            'alerts' => $this->alertRepo->findRecentWithRelations(50),
        ]);
    }

    #[Route('/new', name: 'alert_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $alert = new Alert();
        $form  = $this->createForm(AlertType::class, $alert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1. Persister l'alerte en BDD (status = 'pending')
            $this->em->persist($alert);
            $this->em->flush();

            // 2. Appel n8n → envoi email
            $success = $this->mailer->sendAlert($alert);

            // 3. Mettre à jour le statut (sent | failed)
            $this->em->flush();

            if ($success) {
                $this->addFlash('success', sprintf(
                    '✅ Alerte envoyée à %s via n8n.',
                    $alert->getUser()?->getEmail()
                ));
            } else {
                $this->addFlash('danger', '❌ L\'alerte a été enregistrée mais l\'envoi email a échoué. Vérifiez n8n.');
            }

            return $this->redirectToRoute('alert_index');
        }

        return $this->render('alert/create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Renvoyer une alerte échouée.
     */
    #[Route('/{id}/retry', name: 'alert_retry', methods: ['POST'])]
    public function retry(Alert $alert, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('retry_alert_' . $alert->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $alert->setStatus(Alert::STATUS_PENDING);
        $success = $this->mailer->sendAlert($alert);
        $this->em->flush();

        $this->addFlash(
            $success ? 'success' : 'danger',
            $success ? '✅ Alerte renvoyée avec succès.' : '❌ Nouvel échec d\'envoi.'
        );

        return $this->redirectToRoute('alert_index');
    }
}
