<?php

declare(strict_types=1);

use Lack\PdfDoc\Template\TemplateDocument;
use PHPUnit\Framework\TestCase;

final class TemplateDocumentTest extends TestCase
{
    public function testLoadsFrontMatterInheritanceAndMarkdown(): void
    {
        $dir = sys_get_temp_dir() . '/lack-pdf-doc-template-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        try {
            file_put_contents($dir . '/base.template.html', "---\ndefaults:\n  company:\n    name: Example GmbH\nconfig:\n  fonts:\n    body: builtin://helvetica\n  layout:\n    pageLeft: 20mm\n    pageRight: 18mm\n    pageTop: 12mm\n    pageBottom: 22mm\n---\n<main>{{ meta.company.name }} {{ template }}</main>");
            file_put_contents($dir . '/child.template.html', "---\nextends: file://base.template.html\ndefaults:\n  title: Default title\n---\n<h1>{{ meta.title }}</h1>{{ content }}");
            file_put_contents($dir . '/document.md', "---\ntitle: Custom title\n---\nHello **World**");

            $document = TemplateDocument::fromTemplateFile($dir . '/child.template.html', safe: true)
                ->fromMarkdownFile($dir . '/document.md');
            $document->templateVariables();
            $html = $document->getHtml();

            self::assertNotNull($html);
            self::assertStringContainsString('Example GmbH', $html);
            self::assertStringContainsString('<h1>Custom title</h1>', $html);
            self::assertStringContainsString('<strong>World</strong>', $html);
            self::assertSame('20mm', $document->styleVariables()['pageLeft']);
            self::assertSame('18mm', $document->styleVariables()['pageRight']);
            self::assertSame('12mm', $document->styleVariables()['pageTop']);
            self::assertSame('22mm', $document->styleVariables()['pageBottom']);
        } finally {
            @unlink($dir . '/document.md');
            @unlink($dir . '/child.template.html');
            @unlink($dir . '/base.template.html');
            @rmdir($dir);
        }
    }

    public function testProgrammaticMetadataOverridesDocumentMetadata(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'lack-pdf-doc-template-');
        self::assertIsString($file);

        try {
            file_put_contents($file, "---\ndefaults:\n  title: Default\n---\n{{ meta.title }}");
            $document = TemplateDocument::fromTemplateFile($file, metadataOverrides: ['title' => 'Override'])
                ->metadata(['title' => 'Document']);
            $document->templateVariables();

            self::assertSame('Override', $document->getHtml());
        } finally {
            @unlink($file);
        }
    }

    public function testRendererReceivesTemplateContext(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'lack-pdf-doc-template-');
        self::assertIsString($file);

        try {
            file_put_contents($file, "---\ndefaults:\n  candidate:\n    name: Erika\n---\n{{ render:candidate }}");
            $document = TemplateDocument::fromTemplateFile($file)
                ->renderer('candidate', static fn($context): string => '<b>' . $context->meta('candidate.name') . '</b>');
            $document->templateVariables();

            self::assertSame('<b>Erika</b>', $document->getHtml());
        } finally {
            @unlink($file);
        }
    }

    public function testMissingTemplateFileIncludesFilenameInException(): void
    {
        $file = sys_get_temp_dir() . '/lack-pdf-doc-missing-' . bin2hex(random_bytes(6)) . '.html';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($file);
        TemplateDocument::fromTemplateFile($file);
    }

    public function testExtendsRequiresSafeMode(): void
    {
        $dir = sys_get_temp_dir() . '/lack-pdf-doc-template-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        try {
            file_put_contents($dir . '/base.template.html', '{{ template }}');
            file_put_contents($dir . '/child.template.html', "---\nextends: file://base.template.html\n---\nChild");

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($dir . '/child.template.html');
            TemplateDocument::fromTemplateFile($dir . '/child.template.html');
        } finally {
            @unlink($dir . '/child.template.html');
            @unlink($dir . '/base.template.html');
            @rmdir($dir);
        }
    }
}
