<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Resource;

use Closure;
use InvalidArgumentException;
use RuntimeException;

final class ImageSource
{
    private function __construct(private readonly Closure $resolver) {}

    public static function dataUrl(string $dataUrl): self
    {
        if (!preg_match('#^data:image/[a-zA-Z0-9.+-]+;base64,#', $dataUrl)) {
            throw new InvalidArgumentException('Only base64 image data URLs are supported.');
        }
        return new self(static fn(): string => $dataUrl);
    }

    public static function bytes(string $bytes, string $mimeType): self
    {
        if (!str_starts_with($mimeType, 'image/')) {
            throw new InvalidArgumentException('Image MIME type must start with image/.');
        }
        return self::dataUrl('data:' . $mimeType . ';base64,' . base64_encode($bytes));
    }

    public static function fromCallback(callable $callback, string $mimeType): self
    {
        if (!str_starts_with($mimeType, 'image/')) {
            throw new InvalidArgumentException('Image MIME type must start with image/.');
        }

        $resolver = Closure::fromCallable($callback);
        return new self(static function () use ($resolver, $mimeType): string {
            $bytes = $resolver();
            if (!is_string($bytes)) {
                throw new RuntimeException('Image callback must return binary image data as string.');
            }
            return 'data:' . $mimeType . ';base64,' . base64_encode($bytes);
        });
    }

    public function resolve(): string
    {
        $dataUrl = ($this->resolver)();
        if (!preg_match('#^data:image/[a-zA-Z0-9.+-]+;base64,#', $dataUrl)) {
            throw new RuntimeException('Image source did not resolve to an image data URL.');
        }
        return $dataUrl;
    }
}
