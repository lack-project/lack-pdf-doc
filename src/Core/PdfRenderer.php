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
        $parser = $this->parser ?? new DocumentParser();
        $html = $parser->parse($document);
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
        $defaultBox = [
            'x' => $this->lengthToMillimeters((string) ($style['pageLeft'] ?? '15mm')),
            'y' => $this->lengthToMillimeters((string) ($style['pageTop'] ?? '15mm')),
            'width' => 210.0
                - $this->lengthToMillimeters((string) ($style['pageLeft'] ?? '15mm'))
                - $this->lengthToMillimeters((string) ($style['pageRight'] ?? '15mm')),
            'bottom' => $this->lengthToMillimeters((string) ($style['pageBottom'] ?? ($document->template() === 'letter' ? '20mm' : '15mm'))),
        ];
        $layout = $document->contentLayout();
        $followingBox = $this->normalizeContentBox((array) ($layout['following'] ?? []), $defaultBox);
        $firstBox = $this->normalizeContentBox((array) ($layout['first'] ?? []), $followingBox);

        $pageRight = 210.0 - $followingBox['x'] - $followingBox['width'];
        if ($pageRight < 0) {
            throw new RuntimeException('Content layout exceeds the A4 page width.');
        }

        $page = $pdf->addPage([
            'margin' => [
                'PL' => $followingBox['x'],
                'PR' => $pageRight,
                'PT' => $followingBox['y'],
                'PB' => $followingBox['bottom'],
                'CT' => $followingBox['y'],
                'CB' => $followingBox['bottom'],
            ],
        ]);
        $this->applyFirstPageContentLayout($pdf, $page, $firstBox, $followingBox);

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
            posx: $followingBox['x'],
            posy: $followingBox['y'],
            width: $followingBox['width'],
        );

        $this->renderPageFragments($pdf, $document, $parser);
        $this->renderPageFooters($pdf, $document, $followingBox['x'], $pageRight);

        $form = $document->getForm();
        if ($form !== null) {
            ($this->formRenderer ?? new FormRenderer())->render($pdf, $form);
        }

        return $pdf->getOutPDFString();
    }

    private function normalizeContentBox(array $box, array $defaults): array
    {
        $normalized = $defaults;
        foreach (['x', 'y', 'width', 'bottom'] as $name) {
            if (array_key_exists($name, $box)) {
                $normalized[$name] = $this->lengthToMillimeters((string) $box[$name]);
            }
        }
        return $normalized;
    }

    private function applyFirstPageContentLayout(Tcpdf $pdf, array $page, array $first, array $following): void
    {
        $followRight = $following['x'] + $following['width'];
        $firstRight = $first['x'] + $first['width'];
        $pageHeight = (float) $page['height'];
        $followBottomY = $pageHeight - $following['bottom'];
        $firstBottomY = $pageHeight - $first['bottom'];

        if (
            $first['x'] < $following['x']
            || $first['y'] < $following['y']
            || $firstRight > $followRight
            || $firstBottomY > $followBottomY
        ) {
            throw new RuntimeException('The first-page content box must fit inside the following-page content box.');
        }

        $regions = [];
        $thin = 0.1;
        if ($first['y'] > $following['y']) {
            $regions[] = [
                'xt' => $following['x'] + $thin,
                'yt' => $following['y'],
                'xb' => $following['x'] + $thin,
                'yb' => $first['y'],
                'side' => 'R',
            ];
        }
        if ($firstBottomY < $followBottomY) {
            $regions[] = [
                'xt' => $following['x'] + $thin,
                'yt' => $firstBottomY,
                'xb' => $following['x'] + $thin,
                'yb' => $followBottomY,
                'side' => 'R',
            ];
        }
        if ($first['x'] > $following['x']) {
            $regions[] = [
                'xt' => $first['x'],
                'yt' => $first['y'],
                'xb' => $first['x'],
                'yb' => $firstBottomY,
                'side' => 'L',
            ];
        }
        if ($firstRight < $followRight) {
            $regions[] = [
                'xt' => $firstRight,
                'yt' => $first['y'],
                'xb' => $firstRight,
                'yb' => $firstBottomY,
                'side' => 'R',
            ];
        }

        if ($regions !== []) {
            $pdf->page->setNoWriteRegions($regions, 6.0);
        }
    }

    private function renderPageFragments(Tcpdf $pdf, AbstractDocument $document, DocumentParser $parser): void
    {
        $pageIds = array_keys($pdf->page->getPages());
        foreach ($document->pageFragments() as $fragment) {
            $pages = (string) ($fragment['pages'] ?? 'all');
            $html = $parser->resolveResources($document, (string) ($fragment['html'] ?? ''));
            $x = $this->lengthToMillimeters((string) ($fragment['x'] ?? '0mm'));
            $y = $this->lengthToMillimeters((string) ($fragment['y'] ?? '0mm'));
            $width = $this->lengthToMillimeters((string) ($fragment['width'] ?? '0mm'));
            $height = $this->lengthToMillimeters((string) ($fragment['height'] ?? '0mm'));
            if ($width <= 0 || $height <= 0) {
                throw new RuntimeException('Positioned page fragments require positive width and height.');
            }

            foreach ($pageIds as $index => $pageId) {
                if (!$this->matchesPageSelector($pages, $index)) {
                    continue;
                }
                $pdf->setCurrentPage((int) $pageId);
                $pdf->page->addContent($pdf->getHTMLCell(
                    html: $html,
                    posx: $x,
                    posy: $y,
                    width: $width,
                    height: $height,
                ));
            }
        }
    }

    private function matchesPageSelector(string $selector, int $pageIndex): bool
    {
        return match ($selector) {
            'first' => $pageIndex === 0,
            'following' => $pageIndex > 0,
            'all' => true,
            default => throw new RuntimeException('Unsupported page selector: ' . $selector),
        };
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
            throw new RuntimeException('Unsupported PDF length: ' . $length);
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
