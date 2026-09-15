<?php

use Lack\PdfDoc\Template\Document;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$document = Document::fromFile(
    __DIR__ . '/page-fragments/letter.md',
    safe: true,
);

$document->metadata(['showTerms' => true]);

file_put_contents(__DIR__ . '/letter-page-fragments.pdf', $document->toPdf());
