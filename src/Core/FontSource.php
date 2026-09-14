<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use InvalidArgumentException;

final class FontSource
{
    private const CORE_FAMILIES = ['helvetica', 'times', 'courier', 'symbol', 'zapfdingbats'];

    private function __construct(
        private readonly string $family,
        private readonly string $style = '',
    ) {}

    public static function registered(string $family, string $style = ''): self
    {
        $family = strtolower(trim($family));
        if (!in_array($family, self::CORE_FAMILIES, true)) {
            throw new InvalidArgumentException('Only PDF core fonts are allowed in the isolated renderer.');
        }
        if (preg_match('/[^BI]/', $style)) {
            throw new InvalidArgumentException('Font style may only contain B and I.');
        }
        return new self($family, $style);
    }

    public function family(): string { return $this->family; }
    public function style(): string { return $this->style; }
}
