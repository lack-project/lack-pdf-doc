<?php

use Lack\PdfDoc\Template\TemplateDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$invoice = TemplateDocument::fromDocumentFile(
    __DIR__ . '/page-fragments/invoice.md',
    safe: true,
);

file_put_contents(__DIR__ . '/invoice-page-fragments.pdf', $invoice->toPdf());
