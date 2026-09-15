<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use InvalidArgumentException;
use RuntimeException;

final class TemplateDocument
{
    private function __construct(
        private readonly string $templateHtml,
        private readonly bool $safe,
        private readonly array $templateDefaults,
        private readonly array $config,
        private readonly array $contentLayout,
        /** @var list<Fragment> */
        private readonly array $fragments,
    ) {}

    public static function fromFile(
        string $file,
        bool $safe = false,
        array $configOverrides = [],
    ): self {
        $loaded = self::loadTemplate($file, $safe, [], true);
        return new self(
            $loaded['html'],
            $safe,
            $loaded['defaults'],
            self::mergeRecursive($loaded['config'], $configOverrides),
            $loaded['contentLayout'],
            $loaded['fragments'],
        );
    }

    /**
     * Legacy entry point kept for existing callers.
     */
    public static function fromTemplateFile(
        string $file,
        bool $safe = false,
        array $metadataOverrides = [],
        array $configOverrides = [],
    ): Document {
        $loaded = self::loadTemplate($file, $safe, [], false);
        $template = new self(
            $loaded['html'],
            $safe,
            $loaded['defaults'],
            self::mergeRecursive($loaded['config'], $configOverrides),
            $loaded['contentLayout'],
            $loaded['fragments'],
        );
        return new Document($template, $metadataOverrides);
    }

    public function createDocument(array $metadata = []): Document
    {
        return (new Document($this))->metadata($metadata);
    }

    public function html(): string
    {
        return $this->templateHtml;
    }

    public function safe(): bool
    {
        return $this->safe;
    }

    public function defaults(): array
    {
        return $this->templateDefaults;
    }

    public function config(): array
    {
        return $this->config;
    }

    public function contentLayout(): array
    {
        return $this->contentLayout;
    }

    /** @return list<Fragment> */
    public function fragments(): array
    {
        return $this->fragments;
    }

    private static function loadTemplate(string $file, bool $safe, array $stack, bool $requireType): array
    {
        $realFile = realpath($file) ?: $file;
        if (in_array($realFile, $stack, true)) {
            throw new RuntimeException('Circular template inheritance detected at: ' . $file);
        }
        $stack[] = $realFile;

        $source = self::readFrontMatter($file);
        $header = $source['header'];
        if ($requireType || array_key_exists('type', $header)) {
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
        $parent = self::loadTemplate($parentFile, $safe, $stack, $requireType);
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

    /** @return list<Fragment> */
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
            $fragments[] = Fragment::fromFile(self::resolveFileReference($reference, $templateFile));
        }
        return $fragments;
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
