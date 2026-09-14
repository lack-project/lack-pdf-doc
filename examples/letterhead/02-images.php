<?php

use Lack\PdfDoc\Core\ImageSource;
use Lack\PdfDoc\Core\PdfRenderer;
use Lack\PdfDoc\Letterhead\LetterheadDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$letter = (new LetterheadDocument())
    ->image('logo', ImageSource::file('/secure/input/logo.png', 'image/png'))
    ->image('chart', ImageSource::resolver(
        fn(): string => $cloudStorage->read('reports/current-chart.png'),
        'image/png',
    ))
    ->logo('logo')
    ->sender('Example GmbH · Musterstraße 1 · 45130 Essen')
    ->recipient("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->markdown("# Bericht\n\n![Aktuelles Diagramm](image:chart)");

$pdf = (new PdfRenderer())->render($letter);
file_put_contents(__DIR__ . '/letter-with-images.pdf', $pdf);
