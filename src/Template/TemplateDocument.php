<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use InvalidArgumentException;
use Lack\PdfDoc\Core\AbstractDocument;
use Lack\PdfDoc\Resource\FontSource;
use Lack\PdfDoc\Resource\ImageSource;
use Phore\Markdown\Markdown;
use RuntimeException;

final class TemplateDocument extends AbstractDocument
{
    private array $documentMetadata = [];
    private array $renderers = [];

    private function __construct(
        private readonly string $templateHtml,
        private readonly bool $safe,
        private readonly array $templateDefaults,
        private readonly array $config,
        private readonly array $metadataOverrides,
        private readonly array $contentLayout,
        private readonly array $fragments,
    ) {
        $this->configureFonts();
    }

    public static function fromTemplateFile(
        string $file,
        bool $safe = false,
        array $metadataOverrides = [],
        array $configOverrides = [],
    ): self {
        $loaded = self::loadTemplate($file, $safe, []);
        return new self(
            $loaded['html'],
            $safe,
            $loaded['defaults'],
            self::mergeRecursive($loaded['config'], $configOverrides),
            $metadataOverrides,
            $loaded['contentLayout'],
            $loaded['fragments'],
        );
    }

    public static function fromDocumentFile(
        string $file,
        bool $safe = false,
        array $metadataOverrides = [],
        array $configOverrides = [],
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
        if ($safe) {
            $header = self::normalizeFileReferences($header, $file);
        }

        return self::fromTemplateFile(
            self::resolveFileReference($template, $file),
            safe: $safe,
            metadataOverrides: $metadataOverrides,
            configOverrides: $configOverrides,
        )
            ->metadata($header)
            ->markdown($source['content']);
    }

    public function fromMarkdownFile(string $file): self
    {
        $source = self::readFrontMatter($file);
        $header = $source['header'];
        if ($this->safe) {
            $header = self::normalizeFileReferences($header, $file);
        }
        return $this->metadata($header)->markdown($source['content']);
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
            self::mergeRecursive($this->templateDefaults, $this->documentMetadata),
            $this->metadataOverrides,
        );
    }

    public function config(): array
    {
        return $this->config;
    }

    public function contentLayout(): array
    {
        return $this->contentLayout;
    }

    public function pageFragments(): array
    {
        $result = [];
        foreach ($this->fragments as $fragment) {
            if (!$this->fragmentEnabled($fragment) || !isset($fragment['position'])) {
                continue;
            }
            $position = (array) $fragment['position'];
            $result[] = [
                'pages' => (string) ($fragment['pages'] ?? 'all'),
                'x' => (string) ($position['x'] ?? '0mm'),
                'y' => (string) ($position['y'] ?? '0mm'),
                'width' => (string) ($position['width'] ?? '0mm'),
                'height' => (string) ($position['height'] ?? '0mm'),
                'html' => $this->renderFragment($fragment),
            ];
        }
        return $result;
    }

    public function template(): string
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
        $layout = (array) ($this->config['layout'] ?? []);
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
        $fonts = (array) ($this->config['fonts'] ?? ['body' => 'builtin://helvetica']);
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
        $html = $this->renderMarkup($this->templateHtml, true);
        foreach ($this->fragments as $fragment) {
            if (!$this->fragmentEnabled($fragment) || ($fragment['placement'] ?? null) !== 'after') {
                continue;
            }
            $rendered = $this->renderFragment($fragment);
            if (($fragment['pageBreakBefore'] ?? false) === true) {
                $rendered = '<div style="page-break-before: always;">' . $rendered . '</div>';
            }
            $html .= $rendered;
        }
        return $html;
    }

    private function renderFragment(array $fragment): string
    {
        $body = $this->renderMarkup((string) ($fragment['body'] ?? ''), false);
        return match ((string) ($fragment['format'] ?? 'markdown')) {
            'markdown' => Markdown::toHtml($body),
            'html' => $body,
            default => throw new RuntimeException('Unsupported template fragment format: ' . (string) $fragment['format']),
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

    private function fragmentEnabled(array $fragment): bool
    {
        $condition = $fragment['if'] ?? null;
        if ($condition === null || $condition === '') {
            return true;
        }
        if (!is_string($condition) || !str_starts_with($condition, 'meta.')) {
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
        if (!$this->safe) {
            throw new RuntimeException('File image references require safe mode: ' . $value);
        }

        $file = self::fileReferenceToPath($value);
        self::assertReadableFile($file, 'Template image');
        $bytes = file_get_contents($file);
        if ($bytes === false) {
            throw new RuntimeException('Unable to read template image: ' . $file);
        }
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

    private static function loadTemplate(string $file, bool $safe, array $stack): array
    {
        $realFile = realpath($file) ?: $file;
        if (in_array($realFile, $stack, true)) {
            throw new RuntimeException('Circular template inheritance detected at: ' . $file);
        }
        $stack[] = $realFile;

        $source = self::readFrontMatter($file);
        $header = $source['header'];
        if (array_key_exists('type', $header)) {
            self::assertType($header, 'template', $file);
        }
        $defaults = (array) ($header['defaults'] ?? []);
        $config = (array) ($header['config'] ?? []);
        $contentLayout = (array) ($header['content'] ?? []);
        $fragmentRefs = $header['fragments'] ?? ($header['elements'] ?? []);
        $fragments = self::loadFragments($fragmentRefs, $file, $safe);
        if ($safe) {
            $defaults = self::normalizeFileReferences($defaults, $file);
            $config = self::normalizeFileReferences($config, $file);
        }
        $html = $source['content'];
        $extends = $header['extends'] ?? null;

        if ($extends === null || $extends === '') {
            return [
                'html' => $html,
                'defaults' => $defaults,
                'config' => $config,
                'contentLayout' => $contentLayout,
                'fragments' => $fragments,
            ];
        }
        if (!is_string($extends)) {
            throw new RuntimeException('Template extends must be a string in: ' . $file);
        }
        if (!$safe) {
            throw new RuntimeException('Template inheritance through file references requires safe mode: ' . $file);
        }
        if (!str_starts_with($extends, 'file://')) {
            throw new RuntimeException('Unsupported template parent reference in ' . $file . ': ' . $extends);
        }

        $parentFile = self::resolveFileReference($extends, $file);
        $parent = self::loadTemplate($parentFile, $safe, $stack);
        if (!str_contains($parent['html'], '{{ template }}')) {
            throw new RuntimeException('Parent template is missing {{ template }} placeholder: ' . $parentFile);
        }

        return [
            'html' => str_replace('{{ template }}', $html, $parent['html']),
            'defaults' => self::mergeRecursive($parent['defaults'], $defaults),
            'config' => self::mergeRecursive($parent['config'], $config),
            'contentLayout' => self::mergeRecursive($parent['contentLayout'], $contentLayout),
            'fragments' => array_merge($parent['fragments'], $fragments),
        ];
    }

    private static function loadFragments(mixed $fragmentRefs, string $templateFile, bool $safe): array
    {
        if ($fragmentRefs === null || $fragmentRefs === []) {
            return [];
        }
        if (is_string($fragmentRefs)) {
            $fragmentRefs = [$fragmentRefs];
        }
        if (!is_array($fragmentRefs)) {
            throw new RuntimeException('Template fragments must be a file reference list in: ' . $templateFile);
        }
        if (!$safe) {
            throw new RuntimeException('Template fragment imports require safe mode: ' . $templateFile);
        }

        $fragments = [];
        foreach ($fragmentRefs as $reference) {
            if (!is_string($reference) || !str_starts_with($reference, 'file://')) {
                throw new RuntimeException('Template fragment must be a file:// reference in: ' . $templateFile);
            }
            $file = self::resolveFileReference($reference, $templateFile);
            $source = self::readFrontMatter($file);
            $header = $source['header'];
            self::assertType($header, 'fragment', $file);
            $fragments[] = [
                'file' => $file,
                'pages' => $header['pages'] ?? 'all',
                'position' => $header['position'] ?? null,
                'placement' => $header['placement'] ?? null,
                'if' => $header['if'] ?? null,
                'pageBreakBefore' => $header['pageBreakBefore'] ?? false,
                'format' => $header['format'] ?? 'markdown',
                'body' => $source['content'],
            ];
        }
        return $fragments;
    }

    private static function assertType(array $header, string $expected, string $file): void
    {
        $type = $header['type'] ?? null;
        if ($type !== $expected) {
            $actual = is_scalar($type) ? (string) $type : 'missing';
            throw new RuntimeException('Expected type "' . $expected . '", got "' . $actual . '": ' . $file);
        }
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

    private static function assertReadableFile(string $file, string $label): void
    {
        if (!is_file($file)) {
            throw new RuntimeException($label . ' does not exist: ' . $file);
        }
        if (!is_readable($file)) {
            throw new RuntimeException($label . ' is not readable: ' . $file);
        }
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
