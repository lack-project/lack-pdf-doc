<?php

use Lack\PdfDoc\SimpleDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$document = (new SimpleDocument())
    ->markdown(<<<'MARKDOWN'
# Einverständniserklärung

Bitte füllen Sie die folgenden Felder aus und speichern Sie das PDF anschließend wieder ab.

Das Signaturfeld ist ein echtes digitales PDF-Signaturfeld. Ein PDF-Viewer kann dort eine zertifikatsbasierte Signatur setzen. Freihändiges Unterschreiben mit Maus oder Finger ist eine zusätzliche Viewer-Funktion und nicht in jedem Viewer identisch verfügbar.
MARKDOWN);

$document->form()
    ->text('full_name', x: 25, y: 80, width: 90, label: 'Vor- und Nachname')
    ->text('email', x: 25, y: 100, width: 90, label: 'E-Mail-Adresse')
    ->select(
        'decision',
        x: 25,
        y: 120,
        width: 60,
        options: ['yes' => 'Ja', 'no' => 'Nein'],
        label: 'Einverstanden?',
    )
    ->checkbox('confirmed', x: 25, y: 140, label: 'Angaben geprüft')
    ->signature(
        'signature',
        x: 25,
        y: 165,
        width: 80,
        height: 25,
        label: 'Hier digital unterschreiben',
    );

$pdf = $document->toPdf();
file_put_contents(__DIR__ . '/fill-and-sign.pdf', $pdf);

// Der Empfänger öffnet fill-and-sign.pdf in einem PDF-Viewer, füllt die AcroForm-Felder aus,
// signiert optional das Signaturfeld, speichert das PDF und kann genau diese Datei zurücksenden.
