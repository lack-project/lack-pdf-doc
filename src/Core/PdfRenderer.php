<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Com\Tecnick\Pdf\Tcpdf;

final class PdfRenderer
{
    public function __construct(private readonly ?DocumentParser $parser = null) {}

    public function render(Document $document): string
    {
        $html = ($this->parser ?? new DocumentParser())->parse($document);
        $pdf = new Tcpdf(fileOptions: [
            'allowedHosts' => [],
            'allowedPaths' => [],
            'markupAllowedPaths' => [],
        ]);
        $pdf->addPage();

        foreach ($document->getFonts() as $font) {
            $metric = $pdf->font->insert($pdf->pon, $font->family(), $font->style(), 11);
            $pdf->page->addContent($metric['out']);
        }

        $pdf->addHTMLCell(html: $html, posx: 0, posy: 0, width: 210);
        return $pdf->getOutPDFString();
    }
}
