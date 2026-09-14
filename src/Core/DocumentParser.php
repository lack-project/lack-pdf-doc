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

        if (preg_match('#url\((?!["\']?data:)#i', $css)) {
            throw new RuntimeException('External or filesystem CSS resources are not allowed.');
        }

        $content = $document->getHtml() ?? Markdown::toHtml($document->getMarkdown());
        $values = array_merge($document->variables(), ['css' => $css, 'content' => $content]);
        $html = preg_replace_callback('/\{\{([a-zA-Z0-9_.-]+)\}\}/', static fn(array $m): string => (string) ($values[$m[1]] ?? ''), $html)
            ?? throw new RuntimeException('Unable to render PDF template.');

        $images = $document->getImages();
        $html = preg_replace_callback('#(<img\b[^>]*\bsrc=["\'])image:([a-zA-Z0-9_.-]+)(["\'][^>]*>)#i', static function (array $m) use ($images): string {
            $source = $images[$m[2]] ?? null;
            if (!$source instanceof ImageSource) {
                throw new RuntimeException('Unknown image alias: ' . $m[2]);
            }
            return $m[1] . $source->resolve() . $m[3];
        }, $html) ?? throw new RuntimeException('Unable to resolve image aliases.');

        if (preg_match('#<img\b[^>]*\bsrc=["\'](?!data:image/)#i', $html)) {
            throw new RuntimeException('Images must use registered aliases; direct URLs and filesystem paths are forbidden.');
        }

        return $html;
    }
}
