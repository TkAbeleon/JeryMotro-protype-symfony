<?php
// src/Service/PdfExportService.php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfExportService
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    /**
     * Génère un PDF à partir d'un template Twig et retourne le contenu binaire.
     *
     * @param string $template  Chemin du template Twig (ex: 'pdf/report.html.twig')
     * @param array  $data      Variables passées au template
     * @param string $filename  Nom du fichier suggéré (non utilisé côté serveur)
     */
    public function generate(string $template, array $data = [], string $filename = 'rapport.pdf'): string
    {
        $html = $this->twig->render($template, $data);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Envoie la réponse HTTP PDF directement au navigateur.
     */
    public function streamResponse(string $pdfContent, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\Response(
            $pdfContent,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
                'Content-Length'      => strlen($pdfContent),
            ]
        );
    }
}
