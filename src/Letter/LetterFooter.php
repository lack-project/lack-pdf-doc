<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letter;

final class LetterFooter
{
    public function __construct(
        public string|array $company = '',
        public string|array $bank = '',
        public string|array $contact = '',
        public string|array $legal = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            company: $data['company'] ?? '',
            bank: $data['bank'] ?? '',
            contact: $data['contact'] ?? '',
            legal: $data['legal'] ?? '',
        );
    }

    public function variables(): array
    {
        return [
            'footerCompany' => self::renderBlock($this->company),
            'footerBank' => self::renderBlock($this->bank),
            'footerContact' => self::renderBlock($this->contact),
            'footerLegal' => self::renderBlock($this->legal),
        ];
    }

    private static function renderBlock(string|array $value): string
    {
        if (is_string($value)) {
            return nl2br(htmlspecialchars($value, ENT_QUOTES));
        }

        $lines = [];
        foreach ($value as $key => $item) {
            $prefix = is_string($key) ? htmlspecialchars($key, ENT_QUOTES) . ': ' : '';
            $lines[] = $prefix . htmlspecialchars((string) $item, ENT_QUOTES);
        }
        return implode('<br>', $lines);
    }
}
