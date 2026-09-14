<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letter;

use InvalidArgumentException;
use JsonException;
use Lack\PdfDoc\Resource\FontSource;
use Lack\PdfDoc\Resource\ImageSource;
use RuntimeException;

final class LetterConfig
{
    /** @param array<string, FontSource> $fonts */
    public function __construct(
        public ?string $logo = null,
        public ?ImageSource $logoSource = null,
        public string $returnAddress = '',
        public LetterLayout $layout = new LetterLayout(),
        public LetterFooter $footer = new LetterFooter(),
        public array $variables = [],
        public array $fonts = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return self::fromArrayInternal($data, null);
    }

    public static function fromFile(string $file): self
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException('Letter config file not found: ' . $file);
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $data = match ($extension) {
            'json' => self::decodeJsonFile($file),
            'yaml', 'yml' => self::decodeYamlFile($file),
            default => throw new InvalidArgumentException(
                'Unsupported letter config format. Expected .json, .yaml or .yml.',
            ),
        };

        if (!is_array($data)) {
            throw new InvalidArgumentException('Letter config root must be an object/map.');
        }

        $baseDir = dirname(realpath($file) ?: $file);
        return self::fromArrayInternal($data, $baseDir);
    }

    private static function decodeJsonFile(string $file): array
    {
        $json = file_get_contents($file);
        if ($json === false) {
            throw new RuntimeException('Unable to read letter config file: ' . $file);
        }

        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON letter config: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('Letter config root must be an object/map.');
        }

        return $data;
    }

    private static function decodeYamlFile(string $file): array
    {
        if (!function_exists('yaml_parse_file')) {
            throw new RuntimeException(
                'YAML letter configs require the optional PHP YAML extension; use a JSON config when it is not installed.',
            );
        }

        $data = yaml_parse_file($file);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid YAML letter config or root is not an object/map.');
        }

        return $data;
    }

    private static function fromArrayInternal(array $data, ?string $baseDir): self
    {
        [$logo, $logoSource] = self::resolveLogo($data['logo'] ?? null, $baseDir);

        $fonts = [];
        foreach ((array) ($data['fonts'] ?? []) as $alias => $definition) {
            $fonts[(string) $alias] = self::resolveFont((string) $definition, $baseDir);
        }

        return new self(
            logo: $logo,
            logoSource: $logoSource,
            returnAddress: (string) ($data['returnAddress'] ?? ''),
            layout: LetterLayout::fromArray((array) ($data['layout'] ?? [])),
            footer: LetterFooter::fromArray((array) ($data['footer'] ?? [])),
            variables: (array) ($data['variables'] ?? []),
            fonts: $fonts,
        );
    }

    /** @return array{0:?string,1:?ImageSource} */
    private static function resolveLogo(mixed $definition, ?string $baseDir): array
    {
        if ($definition === null || $definition === '') {
            return [null, null];
        }
        if (!is_string($definition)) {
            throw new InvalidArgumentException('logo must be a resource URI or image alias.');
        }
        if (str_starts_with($definition, 'data:image/')) {
            return ['__letter_config_logo', ImageSource::dataUrl($definition)];
        }
        if (str_starts_with($definition, 'file://')) {
            if ($baseDir === null) {
                throw new InvalidArgumentException(
                    'file:// resources require LetterConfig::fromFile() because fromArray() has no base directory.',
                );
            }
            $path = self::resolveFileUri($definition, $baseDir);
            $bytes = file_get_contents($path);
            if ($bytes === false) {
                throw new RuntimeException('Unable to read logo file: ' . $path);
            }
            return ['__letter_config_logo', ImageSource::bytes($bytes, self::imageMimeType($path))];
        }

        return [$definition, null];
    }

    private static function resolveFont(string $definition, ?string $baseDir): FontSource
    {
        if (str_starts_with($definition, 'file://')) {
            if ($baseDir === null) {
                throw new InvalidArgumentException(
                    'file:// font resources require LetterConfig::fromFile() because fromArray() has no base directory.',
                );
            }
            throw new InvalidArgumentException(
                'Custom font files are not supported by the isolated renderer; use builtin://<family> instead.',
            );
        }
        if (!str_starts_with($definition, 'builtin://')) {
            throw new InvalidArgumentException('Font resource must use builtin://<family>.');
        }

        $value = substr($definition, strlen('builtin://'));
        [$family, $query] = array_pad(explode('?', $value, 2), 2, '');
        parse_str($query, $options);
        return FontSource::builtIn($family, (string) ($options['style'] ?? ''));
    }

    private static function resolveFileUri(string $uri, string $baseDir): string
    {
        $path = substr($uri, strlen('file://'));
        if ($path === '') {
            throw new InvalidArgumentException('Empty file:// resource.');
        }

        // file:///path/to/file is absolute; file://assets/file.png is relative to the YAML/JSON file.
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }

    private static function imageMimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => throw new InvalidArgumentException('Unsupported logo image type: ' . $path),
        };
    }
}
