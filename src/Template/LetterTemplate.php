<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use InvalidArgumentException;
use Lack\PdfDoc\Letter\LetterConfig;

final class LetterTemplate
{
    /** @param list<array<string, mixed>> $sections */
    public function __construct(private readonly array $sections)
    {
        foreach ($sections as $section) {
            $type = (string) ($section['type'] ?? '');
            if (!in_array($type, ['table', 'markdown', 'fixed'], true)) {
                throw new InvalidArgumentException('Unsupported letter template section type: ' . $type);
            }
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(array_values((array) ($data['sections'] ?? [])));
    }

    public function document(?LetterConfig $config = null): LetterTemplateDocument
    {
        return new LetterTemplateDocument($this, $config ?? new LetterConfig());
    }

    /** @return list<array<string, mixed>> */
    public function sections(): array
    {
        return $this->sections;
    }
}
