<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Com\Tecnick\Pdf\Tcpdf;

final class PdfRenderer
{
    public function __construct(
        private readonly ?DocumentParser $parser = null,
        private readonly ?FormRenderer $formRenderer = null,
    ) {}

    public function render(AbstractDocument $document): string
    {
        $html = ($this->parser ?? new DocumentParser())->parse($document);
        $pdf = new Tcpdf(fileOptions: [
            'allowedHosts' => [],
            'markupAllowedPaths' => [],
        ]);
        $pdf->addPage();

        foreach ($document->getFonts() as $font) {
            $metric = $pdf->font->insert($pdf->pon, $font->family(), $font->style(), 11);
            $pdf->page->addContent($metric['out']);
        }

        $pdf->addHTMLCell(html: $html, posx: 0, posy: 0, width: 210);

        $form = $document->getForm();
        if ($form !== null) {
            ($this->formRenderer ?? new FormRenderer())->render($pdf, $form);
        }

        return $pdf->getOutPDFString();
    }
}
