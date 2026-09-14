<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Form;

final class InteractiveForm
{
    /** @var list<FormField> */
    private array $fields = [];

    public function text(string $name, float $x, float $y, float $width, float $height = 7, string $label = ''): self
    {
        $this->fields[] = FormField::text($name, $x, $y, $width, $height, $label);
        return $this;
    }

    public function checkbox(string $name, float $x, float $y, float $size = 5, string $label = ''): self
    {
        $this->fields[] = FormField::checkbox($name, $x, $y, $size, $label);
        return $this;
    }

    public function select(string $name, float $x, float $y, float $width, array $options, float $height = 7, string $label = ''): self
    {
        $this->fields[] = FormField::select($name, $x, $y, $width, $options, $height, $label);
        return $this;
    }

    public function signature(string $name, float $x, float $y, float $width = 70, float $height = 22, string $label = 'Digital signieren'): self
    {
        $this->fields[] = FormField::signature($name, $x, $y, $width, $height, $label);
        return $this;
    }

    /** @return list<FormField> */
    public function fields(): array
    {
        return $this->fields;
    }
}
