<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use InvalidArgumentException;
use Lack\PdfDoc\Core\AbstractDocument;
use Lack\PdfDoc\Resource\FontSource;
use Phore\Markdown\Markdown;
use RuntimeException;

final class TemplateDocument extends AbstractDocument
{
    private array $templateDefaults = [];
    private array $documentMetadata = [];
    private array $metadataOverrides = [];
    private array $config = [];
    private array $renderers = [];

    private function __construct(
        private readonly string $templateHtml,
        private readonly bool $safe,
        array $templateDefaults,
        array $config,
        array $metadataOverrides,
    ) {
        $this->templateDefaults = $templateDefaults;
        $this->config = $config;
        $this->metadataOverrides = $metadataOverrides;
        $this->configureFonts();
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
        $value = $this->resolvedMetadata();
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                throw new RuntimeException('Missing template metadata value: ' . $path);
            }
            $value = $value[$part];
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
        $html = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function (array $match): string {
            $expression = trim($match[1]);
            if ($expression === 'content') {
                return Markdown::toHtml($this->getMarkdown());
            }
            if (str_starts_with($expression, 'meta.')) {
                return htmlspecialchars($this->scalar($this->meta(substr($expression, 5)), $expression), ENT_QUOTES);
            }
            if (str_starts_with($expression, 'markdown:meta.')) {
                return Markdown::toHtml($this->scalar($this->meta(substr($expression, 14)), $expression));
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
        }, $this->templateHtml);

        return $html ?? throw new RuntimeException('Unable to render template document.');
    }

    private function scalar(mixed $value, string $expression): string
    {
        if ($value === null || is_scalar($value)) {
            return (string) $value;
        }
        throw new RuntimeException('Template value must be scalar: ' . $expression);
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
