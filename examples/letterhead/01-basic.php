<?php

use Lack\PdfDoc\Core\ImageSource;
use Lack\PdfDoc\Core\PdfRenderer;
use Lack\PdfDoc\Letterhead\LetterheadDocument;
use Lack\PdfDoc\Letterhead\LetterheadStyle;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$style = new LetterheadStyle(logoWidth: '42mm');

$letter = (new LetterheadDocument($style))
    ->image('logo', ImageSource::file(__DIR__ . '/logo.png', 'image/png'))
    ->logo('logo')
    ->header('Kundennummer 12345')
    ->sender('Example GmbH · Musterstraße 1 · 45130 Essen')
    ->recipient("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->footer(
        'Example GmbH<br>Musterstraße 1<br>45130 Essen',
        'Bank<br>IBAN DE00 0000 0000 0000 0000 00',
        'kontakt@example.de<br>+49 201 123456',
        'Geschäftsführung<br>Max Mustermann',
    )
    ->markdown("# Ihr Schreiben\n\nSehr geehrte Frau Mustermann,\n\ndieser Inhalt wird mit `phore/markdown` gerendert.");

$pdf = (new PdfRenderer())->render($letter);
file_put_contents(__DIR__ . '/letter.pdf', $pdf);
