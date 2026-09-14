<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Com\Tecnick\Pdf\Tcpdf;
use Lack\PdfDoc\Form\FormField;
use Lack\PdfDoc\Form\InteractiveForm;
use RuntimeException;

final class FormRenderer
{
    public function render(Tcpdf $pdf, InteractiveForm $form): void
    {
        foreach ($form->fields() as $field) {
            $this->renderField($pdf, $field);
        }
    }

    private function renderField(Tcpdf $pdf, FormField $field): void
    {
        if ($field->label !== '') {
            $pdf->addHTMLCell(
                html: '<span style="font-size:8pt;color:#555;">' . htmlspecialchars($field->label, ENT_QUOTES) . '</span>',
                posx: $field->x,
                posy: max(0, $field->y - 5),
                width: $field->width,
                height: 4,
            );
        }

        $annotationId = match ($field->type) {
            'text' => $pdf->addFFText($field->name, $field->x, $field->y, $field->width, $field->height),
            'checkbox' => $pdf->addFFCheckBox($field->name, $field->x, $field->y, $field->width),
            'select' => $pdf->addFFComboBox(
                $field->name,
                $field->x,
                $field->y,
                $field->width,
                $field->height,
                $this->normalizeOptions($field->options),
            ),
            'signature' => $this->renderSignature($pdf, $field),
            default => throw new RuntimeException('Unsupported form field type: ' . $field->type),
        };

        $pdf->page->addAnnotRef($annotationId);
    }

    private function renderSignature(Tcpdf $pdf, FormField $field): int
    {
        $pdf->addHTMLCell(
            html: '<div style="border:1px solid #777;background-color:#fafafa;text-align:center;font-size:9pt;color:#555;">'
                . htmlspecialchars($field->label !== '' ? $field->label : 'Digital signieren', ENT_QUOTES)
                . '</div>',
            posx: $field->x,
            posy: $field->y,
            width: $field->width,
            height: $field->height,
        );

        return $pdf->setAnnotation(
            posx: $field->x,
            posy: $field->y,
            width: $field->width,
            height: $field->height,
            txt: $field->name,
            opt: [
                'subtype' => 'Widget',
                'ft' => 'Sig',
                't' => $field->name,
                'tu' => $field->label !== '' ? $field->label : $field->name,
            ],
        );
    }

    private function normalizeOptions(array $options): array
    {
        $result = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $result[] = $value;
                continue;
            }
            $export = is_string($key) ? $key : (string) $value;
            $result[] = [$export, (string) $value];
        }
        return $result;
    }
}
