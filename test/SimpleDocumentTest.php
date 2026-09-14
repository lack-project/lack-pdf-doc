<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Test;

use Lack\PdfDoc\SimpleDocument;
use PHPUnit\Framework\TestCase;

final class SimpleDocumentTest extends TestCase
{
    public function testDefaultTemplateAndStyleVariables(): void
    {
        $document = new SimpleDocument();

        self::assertSame('simple', $document->template());
        self::assertSame([
            'bodyFont' => 'font:body',
            'bodyFontSize' => '11pt',
            'bodyLineHeight' => '1.45',
        ], $document->styleVariables());
    }
}
