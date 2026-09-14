<?php

use Lack\PdfDoc\Core\FontSource;
use Lack\PdfDoc\Core\ImageSource;
use Lack\PdfDoc\Core\PdfRenderer;
use Lack\PdfDoc\Letterhead\LetterheadDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Diese Variante ergänzt 01-basic.php: Die Anwendung besitzt einen eigenen Storage-Client.
// Nur der Callback kennt den externen Speicher. TCPDF sieht weder URL noch Dateipfad.
$readAsset = static function (string $name) use ($cloudStorage): string {
    return $cloudStorage->read($name);
};

$letter = (new LetterheadDocument())
    ->font('body', FontSource::registered('helvetica'))
    ->image('logo', ImageSource::resolver(
        fn(): string => $readAsset('branding/logo.png'),
        'image/png',
    ))
    ->image('chart', ImageSource::resolver(
        fn(): string => $readAsset('reports/current-chart.png'),
        'image/png',
    ))
    ->logo('logo')
    ->sender('Example GmbH · Musterstraße 1 · 45130 Essen')
    ->recipient("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->markdown(<<<'MARKDOWN'
# Monatsbericht

Das Diagramm wird im Markdown ausschließlich über seinen Alias angesprochen:

![Aktuelles Diagramm](image:chart)

Der Resolver liefert die Binärdaten erst beim Rendern. Daraus erzeugt die Library intern eine Data-URL.
MARKDOWN);

$pdf = (new PdfRenderer())->render($letter);
file_put_contents(__DIR__ . '/letter-with-images.pdf', $pdf);
