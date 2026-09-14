<?php

declare(strict_types=1);

namespace Lack\PdfDoc;

use Phore\Markdown\Markdown;
use RuntimeException;

final class DocumentParser
{
    public function __construct(private readonly string $templateDir = __DIR__ . '/../templates')
    {
    }

    public function parse(Document $document): string
    {
        $dir = rtrim($this->templateDir, '/') . '/' . $document->getTemplate();
        $htmlFile = $dir . '/document.html';
        $cssFile = $dir . '/document.css';

        $html = @file_get_contents($htmlFile);
        $css = @file_get_contents($cssFile);
        if ($html === false || $css === false) {
            throw new RuntimeException('PDF template not found: ' . $document->getTemplate());
        }

        $values = array_merge($document->getVariables(), [
            'css' => $css,
            'content' => Markdown::toHtml($document->getMarkdown()),
        ]);

        return preg_replace_callback('/\{\{([a-zA-Z0-9_-]+)\}\}/', static function (array $match) use ($values): string {
            return (string) ($values[$match[1]] ?? '');
        }, $html) ?? throw new RuntimeException('Unable to render PDF template.');
    }
}
