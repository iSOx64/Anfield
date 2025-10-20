<?php

declare(strict_types=1);

namespace App\Services;

class InvoicePdfService
{
    /**
     * @param array<string, mixed> $reservation
     * @param array{terrain: array<string, array<string, mixed>>, services: array<string, array<string, mixed>>} $pricing
     */
    public function stream(array $reservation, array $pricing): void
    {
        $eventLabels = [
            'match_amical' => 'Match amical',
            'entrainement' => 'Entrainement dirige',
            'tournoi_corporate' => 'Tournoi corporate',
            'anniversaire' => 'Anniversaire sportif',
            'stage' => 'Stage intensif',
        ];

        $levelLabels = [
            'loisir' => 'Loisir',
            'intermediaire' => 'Intermediaire',
            'competitif' => 'Competitif',
        ];

        $serviceLabels = [
            'ballon' => 'Ballon',
            'arbitre' => 'Arbitre',
            'maillot' => 'Kit maillots',
            'douche' => 'Acces douche',
            'coach' => 'Coach dedie',
            'photographe' => 'Photographe',
            'traiteur' => 'Service traiteur',
        ];

        $stream = [];
        $stream[] = 'BT';

        $y = 800;
        $this->addLine($stream, $y, 'Foot Fields - Facture', 18);
        $y -= 26;
        $this->addLine($stream, $y, 'Facture #' . (string) ($reservation['id'] ?? ''));
        $y -= 18;
        $this->addLine($stream, $y, 'Date : ' . ($reservation['date_reservation'] ?? ''));
        $y -= 14;
        $this->addLine($stream, $y, 'Creneau : ' . substr((string) ($reservation['creneau_horaire'] ?? ''), 0, 5));
        $y -= 14;
        $this->addLine($stream, $y, 'Terrain : ' . ($reservation['terrain_nom'] ?? ''));
        $y -= 14;
        $eventType = (string) ($reservation['type_evenement'] ?? '');
        $this->addLine($stream, $y, 'Evenement : ' . ($eventLabels[$eventType] ?? ucfirst($eventType)));
        $y -= 14;
        $skillLevel = (string) ($reservation['niveau'] ?? '');
        $this->addLine($stream, $y, 'Niveau : ' . ($levelLabels[$skillLevel] ?? ucfirst($skillLevel)));
        $participants = (int) ($reservation['participants'] ?? 0);
        if ($participants > 0) {
            $y -= 14;
            $this->addLine($stream, $y, 'Participants : ' . $participants);
        }

        $y -= 24;
        $this->addLine($stream, $y, 'Detail des montants', 14);
        $y -= 18;
        $terrainSize = (string) ($reservation['terrain_taille'] ?? '');
        $terrainAmount = (float) ($reservation['montant_terrain'] ?? 0.0);
        $this->addLine($stream, $y, 'Terrain (' . strtoupper($terrainSize) . ') : ' . $this->formatAmount($terrainAmount));

        $y -= 16;
        $this->addLine($stream, $y, 'Services : ' . $this->formatAmount((float) ($reservation['montant_service'] ?? 0.0)));
        $y -= 14;

        foreach ($serviceLabels as $key => $label) {
            if ((int) ($reservation[$key] ?? 0) === 1) {
                $price = $pricing['services'][$key]['prix'] ?? 0.0;
                $y -= 12;
                $this->addLine($stream, $y, '  - ' . $label . ' (' . $this->formatAmount((float) $price) . ')', 10);
            }
        }

        $y -= 20;
        $total = (float) ($reservation['facture_total'] ?? 0.0);
        $this->addLine($stream, $y, 'Total : ' . $this->formatAmount($total), 14);

        $y -= 28;
        if (!empty($reservation['demande'])) {
            $this->addLine($stream, $y, 'Demande client :', 12);
            $y -= 14;
            $this->addLine($stream, $y, (string) $reservation['demande']);
            $y -= 20;
        }

        $this->addLine($stream, $y, 'Contact Foot Fields : +212 7 66 36 16 03', 10);
        $y -= 12;
        $this->addLine($stream, $y, 'Email : contact@footfields.com', 10);

        $stream[] = 'ET';

        $pdf = $this->buildPdf($stream);
        $filename = 'facture-reservation-' . (string) ($reservation['id'] ?? 'document') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    /**
     * @param array<int, string> $stream
     */
    private function buildPdf(array $stream): string
    {
        $content = implode("\n", $stream);
        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj';
        $objects[] = '4 0 obj << /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj";
        $objects[] = '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';

        $pdf = '%PDF-1.4\n';
        $offsets = [];
        $offset = strlen($pdf);
        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = $offset;
            $pdf .= $object . "\n";
            $offset += strlen($object) + 1;
        }

        $xrefPos = $offset;
        $pdf .= 'xref\n0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf('%010d 00000 n %s', $off, "\n");
        }

        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xrefPos . "\n";
        $pdf .= '%%EOF';

        return $pdf;
    }

    /**
     * @param array<int, string> $stream
     */
    private function addLine(array &$stream, int $y, string $text, int $fontSize = 12): void
    {
        $stream[] = sprintf('/F1 %d Tf', $fontSize);
        $stream[] = sprintf('1 0 0 1 50 %d Tm', $y);
        $stream[] = '(' . $this->escapeText($text) . ') Tj';
    }

    private function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' DH';
    }
}
