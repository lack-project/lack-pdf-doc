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
        $style = $document->styleVariables();
        $pageLeft = $this->lengthToMillimeters((string) ($style['pageLeft'] ?? '15mm'));
        $pageRight = $this->lengthToMillimeters((string) ($style['pageRight'] ?? '15mm'));
        $pageTop = 15.0;
        $pageBottom = $document->template() === 'letter' ? 20.0 : 15.0;
        $pdf->addPage([
            'margin' => [
                'PL' => $pageLeft,
                'PR' => $pageRight,
                'PT' => $pageTop,
                'PB' => $pageBottom,
                'CT' => $pageTop,
                'CB' => $pageBottom,
            ],
        ]);

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

        $pdf->addHTMLCell(
            html: $html,
            posx: $pageLeft,
            posy: 15,
            width: 210 - $pageLeft - $pageRight,
        );

        $this->renderPageFooters($pdf, $document, $pageLeft, $pageRight);

        $form = $document->getForm();
        if ($form !== null) {
            ($this->formRenderer ?? new FormRenderer())->render($pdf, $form);
        }

        return $pdf->getOutPDFString();
    }

    private function renderPageFooters(
        Tcpdf $pdf,
        AbstractDocument $document,
        float $pageLeft,
        float $pageRight,
    ): void {
        if ($document->template() !== 'letter') {
            return;
        }

        $values = $document->templateVariables();
        $footer = '<table style="width:100%; font-size:7.5pt; line-height:1.25;"><tr>';
        foreach (['footerCompany', 'footerBank', 'footerContact', 'footerLegal'] as $name) {
            $footer .= '<td style="width:25%; vertical-align:top; padding-right:3mm;">'
                . (string) ($values[$name] ?? '')
                . '</td>';
        }
        $footer .= '</tr></table>';

        foreach (array_keys($pdf->page->getPages()) as $pageId) {
            $page = $pdf->setCurrentPage((int) $pageId);
            $pdf->addHTMLCell(
                html: $footer,
                posx: $pageLeft,
                posy: (float) $page['height'] - 17,
                width: (float) $page['width'] - $pageLeft - $pageRight,
                height: 9,
            );
        }
    }

    private function lengthToMillimeters(string $length): float
    {
        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)(mm|cm|in|pt)$/', trim($length), $matches)) {
            throw new RuntimeException('Unsupported PDF page margin: ' . $length);
        }

        $value = (float) $matches[1];
        return match ($matches[2]) {
            'mm' => $value,
            'cm' => $value * 10,
            'in' => $value * 25.4,
            'pt' => $value * 25.4 / 72,
        };
    }
}
