<?php

use Lack\PdfDoc\Letter\LetterConfig;
use Lack\PdfDoc\Resource\ImageSource;
use Lack\PdfDoc\Template\LetterTemplate;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$template = LetterTemplate::fromArray([
    'sections' => [
        [
            'type' => 'table',
            'title' => 'Bewerberprofil',
            'image' => 'candidate.photo',
            'imageWidth' => '34mm',
            'rows' => [
                ['label' => 'Nachname', 'value' => 'candidate.lastName'],
                ['label' => 'Vorname', 'value' => 'candidate.firstName'],
                ['label' => 'Wohnort', 'value' => 'candidate.city'],
                ['label' => 'Geburtsdatum', 'value' => 'candidate.birthDate'],
                ['label' => 'Berufsbezeichnung', 'value' => 'candidate.jobTitle'],
            ],
        ],
        [
            'type' => 'markdown',
            'title' => 'Profil und Erfahrung',
            'slot' => 'profile',
        ],
        [
            'type' => 'table',
            'title' => 'Rahmendaten',
            'rows' => [
                ['label' => 'Verfügbarkeit', 'value' => 'application.availability'],
                ['label' => 'Gehaltsvorstellung', 'value' => 'application.salaryExpectation'],
                ['label' => 'Referenz', 'value' => 'application.reference'],
            ],
        ],
        [
            'type' => 'fixed',
            'title' => 'Interner Hinweis',
            'text' => 'Dossier {{application.reference}} · erstellt für {{application.clientName}}',
        ],
    ],
]);

$config = LetterConfig::fromArray([
    'returnAddress' => 'Example Recruiting GmbH · Musterstraße 1 · 45130 Essen',
]);

$dossier = $template->document($config)
    ->data([
        'candidate' => [
            'photo' => 'candidate-photo',
            'lastName' => 'Mustermann',
            'firstName' => 'Erika',
            'city' => 'Essen',
            'birthDate' => '14.05.1990',
            'jobTitle' => 'Senior Software Engineer',
        ],
        'application' => [
            'availability' => 'ab 1. November 2026',
            'salaryExpectation' => '85.000 EUR p.a.',
            'reference' => 'BEW-2026-1042',
            'clientName' => 'Beispiel AG',
        ],
    ])
    ->text('profile', <<<'MARKDOWN'
Erika Mustermann verfügt über mehrjährige Erfahrung in der Entwicklung verteilter PHP-Systeme.

- Schwerpunkt: Backend-Architektur und APIs
- Erfahrung mit Cloud-Infrastrukturen
- Führung kleiner Entwicklungsteams
MARKDOWN)
    ->image('candidate-photo', ImageSource::dataUrl(
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    ));

$pdf = $dossier->toPdf();
file_put_contents(__DIR__ . '/candidate-dossier.pdf', $pdf);
