<?php

use Lack\PdfDoc\Letter\LetterConfig;
use Lack\PdfDoc\Letter\LetterDocument;
use Lack\PdfDoc\Resource\ImageSource;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = LetterConfig::fromFile(__DIR__ . '/letter.yaml');

$letter = (new LetterDocument($config))
    ->image('chart', ImageSource::fromCallback(
        fn(): string => $cloudStorage->read('reports/current-chart.png'),
        'image/png',
    ))
    ->recipientAddress("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->markdown("# Bericht\n\n![Aktuelles Diagramm](image:chart)");

$pdf = $letter->toPdf();
file_put_contents(__DIR__ . '/letter-with-images.pdf', $pdf);
