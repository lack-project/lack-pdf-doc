<?php

use Lack\PdfDoc\Letter\LetterConfig;
use Lack\PdfDoc\Letter\LetterDocument;
use Lack\PdfDoc\Resource\ImageSource;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = LetterConfig::fromArray([
    'logo' => 'company-logo',
    'returnAddress' => 'Example GmbH · Musterstraße 1 · 45130 Essen',
    'layout' => [
        'logoWidth' => '42mm',
        'pageLeft' => '20mm',
        'pageRight' => '20mm',
    ],
    'footer' => [
        'company' => [
            'Example GmbH',
            'Musterstraße 1',
            '45130 Essen',
        ],
        'bank' => [
            'IBAN' => 'DE00 0000 0000 0000 0000 00',
            'BIC' => 'EXAMPLE1',
        ],
        'contact' => [
            'E-Mail' => 'kontakt@example.de',
            'Telefon' => '+49 201 123456',
        ],
        'legal' => [
            'Geschäftsführung' => 'Max Mustermann',
        ],
    ],
    'variables' => [
        'companyName' => 'Example GmbH',
    ],
]);

$letter = (new LetterDocument($config))
    ->image('company-logo', ImageSource::dataUrl(
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    ))
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
