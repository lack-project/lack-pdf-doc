<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Com\Tecnick\Pdf\Font\Stack;
use Com\Tecnick\Pdf\Tcpdf;
use RuntimeException;

final class PdfRenderer
{
    public function __construct(
        private readonly ?DocumentParser $parser = null,
        private readonly ?FormRenderer $formRenderer = null,
    ) {}

    public function render(AbstractDocument $document): string
    {
        $html = ($this->parser ?? new DocumentParser())->parse($document);
        $fontPackageRoot = dirname((new \ReflectionClass(Stack::class))->getFileName() ?: '', 2);
        $fontDirectory = $fontPackageRoot . '/target/fonts';
        if (!is_dir($fontDirectory)) {
            throw new RuntimeException(
                'PDF font definitions are missing. Run composer install/update to generate tc-lib-pdf fonts: '
                . $fontDirectory,
            );
        }
        if (!defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', $fontDirectory);
        }

        $pdf = new Tcpdf(fileOptions: [
            'allowedHosts' => [],
            'markupAllowedPaths' => [],
        ]);
        $pdf->addPage();

        foreach ($document->getFonts() as $font) {
            $definition = $fontDirectory
                . '/core/'
                . strtolower($font->family() . $font->style())
                . '.json';
            if (!is_file($definition)) {
                throw new RuntimeException('PDF core font definition is missing: ' . $definition);
            }

            $metric = $pdf->font->insert(
                $pdf->pon,
                $font->family(),
                $font->style(),
                11,
                null,
                null,
                $definition,
            );
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
