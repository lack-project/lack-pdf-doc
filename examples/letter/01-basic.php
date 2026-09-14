<?php

use Lack\PdfDoc\Letter\LetterConfig;
use Lack\PdfDoc\Letter\LetterDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = LetterConfig::fromFile(__DIR__ . '/letter.yaml');

$letter = (new LetterDocument($config))
    ->recipientAddress("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->referenceBlock('Kundennummer 12345 · 14. September 2026')
    ->variables([
        'customerNumber' => '12345',
        'date' => '14.09.2026',
    ])
    ->markdown(<<<'MARKDOWN'
# Ihr Schreiben vom 10. September 2026

Sehr geehrte Frau Mustermann,

vielen Dank für Ihre Nachricht. Der eigentliche Briefinhalt wird als Markdown übergeben.

Mit freundlichen Grüßen

**Max Mustermann**
MARKDOWN);

$pdf = $letter->toPdf();
file_put_contents(__DIR__ . '/letter.pdf', $pdf);
