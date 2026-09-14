<?php

use Lack\PdfDoc\Core\FontSource;
use Lack\PdfDoc\Core\ImageSource;
use Lack\PdfDoc\Core\PdfRenderer;
use Lack\PdfDoc\Letterhead\LetterheadDocument;

require dirname(__DIR__) . '/vendor/autoload.php';

// Die Library arbeitet mit Dokumentobjekten. Der Renderer liefert am Ende den PDF-Inhalt als String.
// Ressourcen werden vorher unter Aliasnamen registriert; TCPDF erhält weder URL- noch Dateipfade.
$document = (new LetterheadDocument())
    ->font('body', FontSource::registered('helvetica'))
    ->image('logo', ImageSource::dataUrl(
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    ))
    ->logo('logo')
    ->sender('Example GmbH · Musterstraße 1 · 45130 Essen')
    ->recipient("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->markdown("# Hallo Frau Mustermann\n\nDieses PDF wurde direkt aus Markdown erzeugt.");

$pdf = (new PdfRenderer())->render($document);

// $pdf enthält die fertigen PDF-Bytes und kann gespeichert, versendet oder als HTTP-Response ausgegeben werden.
file_put_contents(__DIR__ . '/quickstart.pdf', $pdf);
