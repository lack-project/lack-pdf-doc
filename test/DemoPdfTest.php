<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Test;

use Lack\PdfDoc\SimpleDocument;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DemoPdfTest extends TestCase
{
    private const OUTPUT_DIR = __DIR__ . '/../demo/generated';

    public static function setUpBeforeClass(): void
    {
        if (!is_dir(self::OUTPUT_DIR) && !mkdir(self::OUTPUT_DIR, 0777, true) && !is_dir(self::OUTPUT_DIR)) {
            throw new RuntimeException('Unable to create demo PDF output directory.');
        }
    }

    public function testGeneratesSimpleDocumentDemo(): void
    {
        $pdf = (new SimpleDocument())
            ->markdown(<<<'MARKDOWN'
# Demo-Dokument

Dieses PDF wird bei jedem Testlauf mit festen Beispieldaten erzeugt.

- Markdown-Überschrift
- Fließtext
- Aufzählung

**Stand der Beispieldaten:** 14.09.2026
MARKDOWN)
            ->toPdf();

        $this->writePdf('simple-document.pdf', $pdf);
    }

    public function testGeneratesFillAndSignDemo(): void
    {
        $document = (new SimpleDocument())
            ->markdown(<<<'MARKDOWN'
# Einverständniserklärung

Bitte füllen Sie die Felder aus und speichern Sie das PDF anschließend wieder ab.
MARKDOWN);

        $document->form()
            ->text('full_name', x: 25, y: 80, width: 90, label: 'Vor- und Nachname')
            ->checkbox('confirmed', x: 25, y: 110, label: 'Angaben geprüft')
            ->signature('signature', x: 25, y: 140, width: 80, height: 25, label: 'Digital unterschreiben');

        $this->writePdf('fill-and-sign.pdf', $document->toPdf());
    }

    private function writePdf(string $filename, string $pdf): void
    {
        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertGreaterThan(1000, strlen($pdf));

        $path = self::OUTPUT_DIR . '/' . $filename;
        if (file_put_contents($path, $pdf) === false) {
            throw new RuntimeException('Unable to write demo PDF: ' . $path);
        }

        self::assertFileExists($path);
        self::assertGreaterThan(1000, filesize($path));
    }
}
