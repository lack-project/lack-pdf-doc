<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Resource;

use InvalidArgumentException;

final class FontSource
{
    private const BUILT_IN_FONTS = ['courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'];

    private function __construct(
        private readonly string $family,
        private readonly string $style = '',
    ) {}

    public static function builtIn(string $family, string $style = ''): self
    {
        $family = strtolower(trim($family));
        if (!in_array($family, self::BUILT_IN_FONTS, true)) {
            throw new InvalidArgumentException('Font must be a built-in PDF font.');
        }
        if (preg_match('/[^BI]/', $style)) {
            throw new InvalidArgumentException('Font style may only contain B and I.');
        }
        return new self($family, $style);
    }

    public function family(): string { return $this->family; }
    public function style(): string { return $this->style; }
}
