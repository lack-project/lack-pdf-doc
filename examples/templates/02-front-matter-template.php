<?php

use Lack\PdfDoc\Template\TemplateContext;
use Lack\PdfDoc\Template\TemplateDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$source = phore_file(__DIR__ . '/candidate-dossier.md')->get_front_matter();

$document = TemplateDocument::fromTemplateFile(
    __DIR__ . '/candidate-dossier.template.html',
    safe: true,
    configOverrides: [
        'variables' => [
            'preparedBy' => 'Recruiting Team',
        ],
    ],
)
    ->renderer('candidateTable', function (TemplateContext $context): string {
        $candidate = $context->meta('candidate');
        $rows = [
            ['Name', $candidate['lastName']],
            ['Vorname', $candidate['firstName']],
            ['Wohnort', $candidate['city']],
            ['Geburtsdatum', $candidate['birthDate']],
            ['Position', $candidate['jobTitle']],
        ];

        $html = '<table class="candidate-table">';
        foreach ($rows as [$label, $value]) {
            $html .= '<tr><th>' . htmlspecialchars((string) $label, ENT_QUOTES) . '</th>';
            $html .= '<td>' . htmlspecialchars((string) $value, ENT_QUOTES) . '</td></tr>';
        }
        return $html . '</table>';
    })
    ->metadata((array) $source->header)
    ->markdown($source->content);

$pdf = $document->toPdf();
file_put_contents(__DIR__ . '/candidate-dossier-front-matter.pdf', $pdf);
