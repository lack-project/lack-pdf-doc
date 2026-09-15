<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use InvalidArgumentException;
use Lack\PdfDoc\Core\AbstractDocument;
use Lack\PdfDoc\Resource\FontSource;
use Lack\PdfDoc\Resource\ImageSource;
use Phore\Markdown\Markdown;
use RuntimeException;

final class Document extends AbstractDocument
{
    private array $documentMetadata = [];
    private array $renderers = [];

    public function __construct(
        private readonly TemplateDocument $templateDocument,
        private readonly array $metadataOverrides = [],
    ) {
        $this->configureFonts();
    }

    public static function fromFile(
        string $file,
        bool $safe = false,
        array $metadataOverrides = [],
    ): self {
        $source = self::readFrontMatter($file);
        $header = $source['header'];
        self::assertType($header, 'document', $file);

        $template = $header['template'] ?? null;
        if (!is_string($template) || $template === '') {
            throw new RuntimeException('Document front matter is missing template in: ' . $file);
        }
        if (!$safe) {
            throw new RuntimeException('Document template references require safe mode: ' . $file);
        }
        if (!str_starts_with($template, 'file://')) {
            throw new RuntimeException('Unsupported document template reference in ' . $file . ': ' . $template);
        }

        unset($header['type'], $header['template']);
        $header = self::normalizeFileReferences($header, $file);

        return (new self(
            TemplateDocument::fromFile(self::resolveFileReference($template, $file), safe: true),
            $metadataOverrides,
        ))
            ->metadata($header)
            ->markdown($source['content']);
    }

    /**
     * Legacy helper for callers that load an untyped Markdown content file after a template.
     */
    public function fromMarkdownFile(string $file): self
    {
        $source = self::readFrontMatter($file);
        $header = $source['header'];
        if ($this->templateDocument->safe()) {
            $header = self::normalizeFileReferences($header, $file);
        }
        return $this->metadata($header)->markdown($source['content']);
    }

    public function template(): TemplateDocument
    {
        return $this->templateDocument;
    }

    public function metadata(array $metadata): self
    {
        $this->documentMetadata = self::mergeRecursive($this->documentMetadata, $metadata);
        return $this;
    }

    public function renderer(string $name, callable $renderer): self
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $name)) {
            throw new InvalidArgumentException('Renderer name contains unsupported characters: ' . $name);
        }
        $this->renderers[$name] = $renderer;
        return $this;
    }

    public function meta(string $path): mixed
    {
        $found = false;
        $value = $this->findMeta($path, $found);
        if (!$found) {
            throw new RuntimeException('Missing template metadata value: ' . $path);
        }
        return $value;
    }

    public function resolvedMetadata(): array
    {
        return self::mergeRecursive(
            self::mergeRecursive($this->templateDocument->defaults(), $this->documentMetadata),
            $this->metadataOverrides,
        );
    }

    public function config(): array
    {
        return $this->templateDocument->config();
    }

    public function contentLayout(): array
    {
        return $this->templateDocument->contentLayout();
    }

    public function pageFragments(): array
    {
        $result = [];
        foreach ($this->templateDocument->fragments() as $fragment) {
            if (!$this->fragmentEnabled($fragment)) {
                continue;
            }
            $position = $fragment->position();
            if ($position === null) {
                continue;
            }
            $result[] = [
                'pages' => $fragment->pages(),
                'x' => (string) ($position['x'] ?? '0mm'),
                'y' => (string) ($position['y'] ?? '0mm'),
                'width' => (string) ($position['width'] ?? '0mm'),
                'height' => (string) ($position['height'] ?? '0mm'),
                'html' => $this->renderFragment($fragment),
            ];
        }
        return $result;
    }

    public function renderTemplateName(): string
    {
        return 'simple';
    }

    public function templateVariables(): array
    {
        $this->html($this->renderTemplate());
        return [];
    }

    public function styleVariables(): array
    {
        $layout = (array) ($this->config()['layout'] ?? []);
        return [
            'pageLeft' => (string) ($layout['pageLeft'] ?? '15mm'),
            'pageRight' => (string) ($layout['pageRight'] ?? '15mm'),
            'pageTop' => (string) ($layout['pageTop'] ?? '15mm'),
            'pageBottom' => (string) ($layout['pageBottom'] ?? '15mm'),
            'bodyFont' => 'font:' . (string) ($layout['bodyFont'] ?? 'body'),
            'bodyFontSize' => (string) ($layout['bodyFontSize'] ?? '11pt'),
            'bodyLineHeight' => (string) ($layout['bodyLineHeight'] ?? '1.45'),
        ];
    }

    private function configureFonts(): void
    {
        $fonts = (array) ($this->config()['fonts'] ?? ['body' => 'builtin://helvetica']);
        foreach ($fonts ?: ['body' => 'builtin://helvetica'] as $alias => $source) {
            $source = (string) $source;
            if (!str_starts_with($source, 'builtin://')) {
                throw new InvalidArgumentException('Unsupported font source for ' . $alias . ': ' . $source);
            }
            $this->font((string) $alias, FontSource::builtIn(substr($source, 10)));
        }
    }

    private function renderTemplate(): string
    {
        $html = $this->renderMarkup($this->templateDocument->html(), true);
        foreach ($this->templateDocument->fragments() as $fragment) {
            if (!$this->fragmentEnabled($fragment) || $fragment->placement() !== 'after') {
                continue;
            }
            $rendered = $this->renderFragment($fragment);
            if ($fragment->pageBreakBefore()) {
                $rendered = '<div style="page-break-before: always;">' . $rendered . '</div>';
            }
            $html .= $rendered;
        }
        return $html;
    }

    private function renderFragment(Fragment $fragment): string
    {
        $body = $this->renderMarkup($fragment->body(), false);
        return match ($fragment->format()) {
            'markdown' => Markdown::toHtml($body),
            'html' => $body,
            default => throw new RuntimeException('Unsupported template fragment format: ' . $fragment->format()),
        };
    }

    private function renderMarkup(string $markup, bool $allowContent): string
    {
        $html = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function (array $match) use ($allowContent): string {
            $expression = trim($match[1]);
            if ($expression === 'content') {
                if (!$allowContent) {
                    throw new RuntimeException('{{ content }} is only allowed in the document template.');
                }
                return Markdown::toHtml($this->getMarkdown());
            }
            if ($expression === 'template') {
                throw new RuntimeException('Unresolved {{ template }} placeholder in template chain.');
            }
            if (str_starts_with($expression, 'meta.')) {
                return htmlspecialchars($this->scalar($this->meta(substr($expression, 5)), $expression), ENT_QUOTES);
            }
            if (str_starts_with($expression, 'markdown:meta.')) {
                return Markdown::toHtml($this->scalar($this->meta(substr($expression, 14)), $expression));
            }
            if (str_starts_with($expression, 'image:meta.')) {
                return $this->renderImage(substr($expression, 11));
            }
            if (str_starts_with($expression, 'render:')) {
                $name = substr($expression, 7);
                $renderer = $this->renderers[$name] ?? null;
                if ($renderer === null) {
                    throw new RuntimeException('Missing template renderer: ' . $name);
                }
                $rendered = $renderer(new TemplateContext($this));
                if (!is_string($rendered)) {
                    throw new RuntimeException('Template renderer must return a string: ' . $name);
                }
                return $rendered;
            }
            throw new RuntimeException('Unsupported template placeholder: ' . $expression);
        }, $markup);
        return $html ?? throw new RuntimeException('Unable to render template markup.');
    }

    private function fragmentEnabled(Fragment $fragment): bool
    {
        $condition = $fragment->condition();
        if ($condition === null || $condition === '') {
            return true;
        }
        if (!str_starts_with($condition, 'meta.')) {
            throw new RuntimeException('Fragment if condition must reference meta.*');
        }
        $found = false;
        $value = $this->findMeta(substr($condition, 5), $found);
        return $found && $value === true;
    }

    private function findMeta(string $path, bool &$found): mixed
    {
        $value = $this->resolvedMetadata();
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                $found = false;
                return null;
            }
            $value = $value[$part];
        }
        $found = true;
        return $value;
    }

    private function renderImage(string $path): string
    {
        $value = $this->meta($path);
        if ($value instanceof ImageSource) {
            $alias = 'template-' . substr(sha1($path), 0, 12);
            $this->image($alias, $value);
            return '<img src="image:' . $alias . '">';
        }

        $value = $this->scalar($value, 'image:meta.' . $path);
        $alias = 'template-' . substr(sha1($path . "\0" . $value), 0, 12);
        if (str_starts_with($value, 'data:image/')) {
            $this->image($alias, ImageSource::dataUrl($value));
            return '<img src="image:' . $alias . '">';
        }
        if (str_starts_with($value, 'image:')) {
            return '<img src="' . htmlspecialchars($value, ENT_QUOTES) . '">';
        }
        if (!str_starts_with($value, 'file://')) {
            throw new RuntimeException('Unsupported template image resource for metadata path: ' . $path);
        }
        if (!$this->templateDocument->safe()) {
            throw new RuntimeException('File image references require safe mode: ' . $value);
        }

        $file = self::fileReferenceToPath($value);
        $bytes = phore_file($file)->get_contents();
        $imageInfo = @getimagesizefromstring($bytes);
        $mimeType = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;
        if (!is_string($mimeType) || !str_starts_with($mimeType, 'image/')) {
            throw new RuntimeException('Unable to detect image type for template image: ' . $file);
        }
        $this->imageBytes($alias, $bytes, $mimeType);
        return '<img src="image:' . $alias . '">';
    }

    private function scalar(mixed $value, string $expression): string
    {
        if ($value === null || is_scalar($value)) {
            return (string) $value;
        }
        throw new RuntimeException('Template value must be scalar: ' . $expression);
    }

    private static function readFrontMatter(string $file): array
    {
        $phoreFile = phore_file($file);
        $contents = $phoreFile->get_contents();
        if (!str_starts_with($contents, "---\n") && !str_starts_with($contents, "---\r\n")) {
            return ['header' => [], 'content' => $contents];
        }

        $source = $phoreFile->get_front_matter();
        return ['header' => (array) $source->header, 'content' => (string) $source->content];
    }

    private static function assertType(array $header, string $expected, string $file): void
    {
        $type = $header['type'] ?? null;
        if ($type !== $expected) {
            $actual = is_scalar($type) ? (string) $type : 'missing';
            throw new RuntimeException('Expected type "' . $expected . '", got "' . $actual . '": ' . $file);
        }
    }

    private static function normalizeFileReferences(array $values, string $sourceFile): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::normalizeFileReferences($value, $sourceFile);
            } elseif (is_string($value) && str_starts_with($value, 'file://')) {
                $values[$key] = self::pathToFileReference(self::resolveFileReference($value, $sourceFile));
            }
        }
        return $values;
    }

    private static function resolveFileReference(string $reference, string $sourceFile): string
    {
        $path = self::fileReferenceToPath($reference);
        if (str_starts_with($reference, 'file:///')) {
            return $path;
        }
        return dirname($sourceFile) . '/' . $path;
    }

    private static function fileReferenceToPath(string $reference): string
    {
        if (!str_starts_with($reference, 'file://')) {
            throw new InvalidArgumentException('Not a file reference: ' . $reference);
        }
        $path = substr($reference, 7);
        return str_starts_with($reference, 'file:///') ? '/' . ltrim($path, '/') : $path;
    }

    private static function pathToFileReference(string $path): string
    {
        $realPath = realpath($path) ?: $path;
        return 'file://' . $realPath;
    }

    private static function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = self::mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
