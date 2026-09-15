<?php

use Lack\PdfDoc\Template\TemplateDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$document = TemplateDocument::fromDocumentFile(
    __DIR__ . '/page-elements/letter.md',
    safe: true,
);

// Programmatisch kann der Aufrufer optionale Dokumentteile ein- oder ausblenden.
$document->metadata(['showTerms' => true]);

file_put_contents(__DIR__ . '/letter-page-elements.pdf', $document->toPdf());
