<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Phore\Markdown\Markdown;
use RuntimeException;

final class DocumentParser
{
    public function __construct(private readonly string $templateDir = __DIR__ . '/../../templates') {}

    public function parse(Document $document): string
    {
        $dir = rtrim($this->templateDir, '/') . '/' . $document->template();
        $html = @file_get_contents($dir . '/document.html');
        $css = @file_get_contents($dir . '/document.css');
        if ($html === false || $css === false) {
            throw new RuntimeException('PDF template not found: ' . $document->template());
        }

        foreach ($document->styleVariables() as $name => $value) {
            $css = str_replace('{{style.' . $name . '}}', (string) $value, $css);
        }

        $values = array_merge($document->variables(), [
            'css' => $css,
            'content' => $document->getHtml() ?? Markdown::toHtml($document->getMarkdown()),
        ]);

        return preg_replace_callback('/\{\{([a-zA-Z0-9_.-]+)\}\}/', static fn(array $m): string => (string) ($values[$m[1]] ?? ''), $html)
            ?? throw new RuntimeException('Unable to render PDF template.');
    }
}
