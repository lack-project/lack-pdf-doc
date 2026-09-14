<?php

use Lack\PdfDoc\SimpleDocument;

require dirname(__DIR__) . '/vendor/autoload.php';

$document = (new SimpleDocument())
    ->markdown("# Hallo\n\nDieses PDF wurde direkt aus Markdown erzeugt.");

$pdf = $document->toPdf();
file_put_contents(__DIR__ . '/quickstart.pdf', $pdf);
