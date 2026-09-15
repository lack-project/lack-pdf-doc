<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Lack\PdfDoc\Resource\ImageSource;
use Phore\Markdown\Markdown;
use RuntimeException;

final class DocumentParser
{
    public function __construct(private readonly string $templateDir = __DIR__ . '/../../templates') {}

    public function parse(AbstractDocument $document): string
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
        $css = FontRegistry::resolveCssAliases($css, $document->getFonts());

        if (preg_match('#url\(#i', $css)) {
            throw new RuntimeException('CSS url() resources are not allowed; images must use registered aliases in document markup.');
        }

        $values = array_replace($document->templateVariables(), [
            'css' => $css,
            'content' => $document->getHtml() ?? Markdown::toHtml($document->getMarkdown()),
        ]);

        $html = preg_replace_callback('/\{\{([a-zA-Z0-9_.-]+)\}\}/', static fn(array $m): string => (string) ($values[$m[1]] ?? ''), $html)
            ?? throw new RuntimeException('Unable to render PDF template.');

        return $this->resolveResources($document, $html);
    }

    public function resolveResources(AbstractDocument $document, string $html): string
    {
        if (preg_match('#<img\b[^>]*\bsrc=["\'](?!image:[a-zA-Z0-9_.-]+["\'])#i', $html)) {
            throw new RuntimeException('Images in document markup must reference a registered image:<alias>.');
        }

        $images = $document->getImages();
        $html = preg_replace_callback('#(<img\b[^>]*\bsrc=["\'])image:([a-zA-Z0-9_.-]+)(["\'][^>]*>)#i', static function (array $m) use ($images): string {
            $source = $images[$m[2]] ?? null;
            if (!$source instanceof ImageSource) {
                throw new RuntimeException('Unknown image alias: ' . $m[2]);
            }
            return $m[1] . $source->resolve() . $m[3];
        }, $html) ?? throw new RuntimeException('Unable to resolve image aliases.');

        if (preg_match('#<img\b[^>]*\bsrc=["\'](?!data:image/)#i', $html)) {
            throw new RuntimeException('Unable to isolate an image resource before PDF rendering.');
        }

        return $html;
    }
}
