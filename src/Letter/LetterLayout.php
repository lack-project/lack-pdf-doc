<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letter;

final class LetterLayout
{
    public function __construct(
        public string $logoWidth = '45mm',
        public string $logoHeight = '22mm',
        public string $pageLeft = '20mm',
        public string $pageRight = '20mm',
        public string $bodyFont = 'body',
        public string $bodyFontSize = '11pt',
        public string $bodyLineHeight = '1.45',
        public string $footerFont = 'body',
        public string $footerFontSize = '8pt',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            logoWidth: (string) ($data['logoWidth'] ?? '45mm'),
            logoHeight: (string) ($data['logoHeight'] ?? '22mm'),
            pageLeft: (string) ($data['pageLeft'] ?? '20mm'),
            pageRight: (string) ($data['pageRight'] ?? '20mm'),
            bodyFont: (string) ($data['bodyFont'] ?? 'body'),
            bodyFontSize: (string) ($data['bodyFontSize'] ?? '11pt'),
            bodyLineHeight: (string) ($data['bodyLineHeight'] ?? '1.45'),
            footerFont: (string) ($data['footerFont'] ?? 'body'),
            footerFontSize: (string) ($data['footerFontSize'] ?? '8pt'),
        );
    }

    public function variables(): array
    {
        return [
            'logoWidth' => $this->logoWidth,
            'logoHeight' => $this->logoHeight,
            'pageLeft' => $this->pageLeft,
            'pageRight' => $this->pageRight,
            'bodyFont' => 'font:' . $this->bodyFont,
            'bodyFontSize' => $this->bodyFontSize,
            'bodyLineHeight' => $this->bodyLineHeight,
            'footerFont' => 'font:' . $this->footerFont,
            'footerFontSize' => $this->footerFontSize,
        ];
    }
}
