<?php

declare(strict_types=1);

namespace Lack\PdfDoc;

use Com\Tecnick\Pdf\Tcpdf;

final class PdfRenderer
{
    public function __construct(private readonly ?DocumentParser $parser = null)
    {
    }

    public function render(Document $document): string
    {
        $html = ($this->parser ?? new DocumentParser())->parse($document);
        $pdf = new Tcpdf();
        $pdf->addPage();
        $font = $pdf->font->insert($pdf->pon, 'helvetica', '', 11);
        $pdf->page->addContent($font['out']);
        $pdf->addHTMLCell(html: $html, posx: 20, posy: 20, width: 170);

        return $pdf->getOutPDFString();
    }
}
