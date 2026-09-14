<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Form;

final readonly class FormField
{
    private function __construct(
        public string $type,
        public string $name,
        public float $x,
        public float $y,
        public float $width,
        public float $height,
        public string $label = '',
        public array $options = [],
    ) {}

    public static function text(string $name, float $x, float $y, float $width, float $height = 7, string $label = ''): self
    {
        return new self('text', $name, $x, $y, $width, $height, $label);
    }

    public static function checkbox(string $name, float $x, float $y, float $size = 5, string $label = ''): self
    {
        return new self('checkbox', $name, $x, $y, $size, $size, $label);
    }

    public static function select(string $name, float $x, float $y, float $width, array $options, float $height = 7, string $label = ''): self
    {
        return new self('select', $name, $x, $y, $width, $height, $label, $options);
    }

    public static function signature(string $name, float $x, float $y, float $width = 70, float $height = 22, string $label = 'Digital signieren'): self
    {
        return new self('signature', $name, $x, $y, $width, $height, $label);
    }
}
