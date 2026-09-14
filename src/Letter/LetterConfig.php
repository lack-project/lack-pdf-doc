<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letter;

final class LetterConfig
{
    public function __construct(
        public ?string $logo = null,
        public string $returnAddress = '',
        public LetterLayout $layout = new LetterLayout(),
        public LetterFooter $footer = new LetterFooter(),
        public array $variables = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            logo: isset($data['logo']) ? (string) $data['logo'] : null,
            returnAddress: (string) ($data['returnAddress'] ?? ''),
            layout: LetterLayout::fromArray((array) ($data['layout'] ?? [])),
            footer: LetterFooter::fromArray((array) ($data['footer'] ?? [])),
            variables: (array) ($data['variables'] ?? []),
        );
    }
}
