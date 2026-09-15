<?php

use Lack\PdfDoc\Template\TemplateDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$template = TemplateDocument::fromFile(
    __DIR__ . '/page-fragments/invoice.template.html',
    safe: true,
);

$invoice = $template->createDocument()
    ->metadata([
        'customer' => [
            'name' => 'Musterkunde GmbH',
            'street' => 'Kundenstraße 8',
            'postalCode' => '45128',
            'city' => 'Essen',
        ],
        'invoice' => [
            'number' => 'RE-2026-0042',
            'date' => '15.09.2026',
            'servicePeriod' => 'September 2026',
            'items' => "| Leistung | Menge | Einzelpreis | Gesamt |\n|---|---:|---:|---:|\n| Beratung | 4 h | 120,00 € | 480,00 € |\n| Dokumentation | 1 | 180,00 € | 180,00 € |",
            'net' => '660,00 €',
            'taxRate' => '19 %',
            'tax' => '125,40 €',
            'total' => '785,40 €',
            'dueDate' => '29.09.2026',
        ],
        'showTerms' => false,
    ]);

file_put_contents(__DIR__ . '/invoice-page-fragments.pdf', $invoice->toPdf());
