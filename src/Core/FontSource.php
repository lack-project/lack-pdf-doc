<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use InvalidArgumentException;

final class FontSource
{
    private function __construct(
        private readonly string $family,
        private readonly string $style = '',
    ) {}

    public static function registered(string $family, string $style = ''): self
    {
        $family = trim($family);
        if ($family === '' || preg_match('/[^a-zA-Z0-9_-]/', $family)) {
            throw new InvalidArgumentException('Font family must be a registered PDF font name.');
        }

        if (preg_match('/[^BI]/', $style)) {
            throw new InvalidArgumentException('Font style may only contain B and I.');
        }

        return new self($family, $style);
    }

    public function family(): string
    {
        return $this->family;
    }

    public function style(): string
    {
        return $this->style;
    }
}
