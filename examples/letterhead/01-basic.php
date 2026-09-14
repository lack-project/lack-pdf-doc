<?php

use Lack\PdfDoc\Core\FontSource;
use Lack\PdfDoc\Core\ImageSource;
use Lack\PdfDoc\Core\PdfRenderer;
use Lack\PdfDoc\Letterhead\LetterheadDocument;
use Lack\PdfDoc\Letterhead\LetterheadStyle;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// LetterheadStyle enthält ausschließlich Layoutwerte. Die Schrift wird über einen Alias referenziert,
// damit das Template keinen Dateipfad und keine URL kennen muss.
$style = new LetterheadStyle(
    logoWidth: '42mm',
    logoHeight: '20mm',
    pageLeft: '20mm',
    pageRight: '20mm',
    bodyFont: 'font:body',
    bodyFontSize: '11pt',
    bodyLineHeight: '1.45',
    footerFont: 'font:body',
    footerFontSize: '8pt',
);

$letter = new LetterheadDocument($style);

// Eigene Ressourcen werden vor dem Rendern registriert. Hier kommt das Logo bereits als Bildinhalt.
// In der Anwendung können die Bytes z. B. aus einem Storage- oder Cloud-Connector stammen.
$letter
    ->font('body', FontSource::registered('helvetica'))
    ->image('company-logo', ImageSource::dataUrl(
        'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
    ))
    ->logo('company-logo');

// Der Briefkopf besteht aus einem optionalen Zusatzbereich, Absenderzeile, Empfängeradresse
// und bis zu vier Bereichen im Footer der ersten Seite.
$letter
    ->header('Kundennummer 12345 · 14. September 2026')
    ->sender('Example GmbH · Musterstraße 1 · 45130 Essen')
    ->recipient("Erika Mustermann\nBeispielweg 10\n45130 Essen")
    ->footer(
        'Example GmbH<br>Musterstraße 1<br>45130 Essen',
        'Bankverbindung<br>IBAN DE00 0000 0000 0000 0000 00<br>BIC EXAMPLE1',
        'Kontakt<br>kontakt@example.de<br>+49 201 123456',
        'Geschäftsführung<br>Max Mustermann',
    );

// Der eigentliche Briefinhalt bleibt Markdown. phore/markdown wandelt ihn vor dem PDF-Rendering in HTML um.
$letter->markdown(<<<'MARKDOWN'
# Ihr Schreiben vom 10. September 2026

Sehr geehrte Frau Mustermann,

vielen Dank für Ihre Nachricht. Der eigentliche Dokumentinhalt kann vollständig als Markdown erzeugt werden.

## Zusammenfassung

- erster Punkt
- zweiter Punkt
- dritter Punkt

Mit freundlichen Grüßen

**Max Mustermann**
MARKDOWN);

// render() gibt die fertigen PDF-Bytes zurück. Danach entscheidet die Anwendung selbst,
// ob sie diese speichert, per E-Mail verschickt oder als HTTP-Response ausliefert.
$pdf = (new PdfRenderer())->render($letter);
file_put_contents(__DIR__ . '/letter.pdf', $pdf);
