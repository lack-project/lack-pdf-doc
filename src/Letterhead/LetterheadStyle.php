<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letterhead;

final class LetterheadStyle
{
    public function __construct(
        public string $logoWidth = '45mm',
        public string $logoHeight = '22mm',
        public string $pageLeft = '20mm',
        public string $pageRight = '20mm',
        public string $bodyFontSize = '11pt',
        public string $bodyLineHeight = '1.45',
        public string $footerFontSize = '8pt',
    ) {}

    public function variables(): array
    {
        return get_object_vars($this);
    }
}
