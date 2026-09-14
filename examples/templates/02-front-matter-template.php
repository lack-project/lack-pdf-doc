<?php

use Lack\PdfDoc\Letter\LetterConfig;
use Lack\PdfDoc\Letter\LetterDocument;
use Lack\PdfDoc\Template\HtmlDocumentTemplate;
use Lack\PdfDoc\Template\TemplateContext;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Entwurfsbeispiel: HtmlDocumentTemplate/TemplateContext/applyTemplate()/metadata()
// sind noch nicht implementiert. Der Front-Matter-Parser stammt aus phore/filesystem.
$source = phore_file(__DIR__ . '/candidate-dossier.md')->get_front_matter();

$template = HtmlDocumentTemplate::fromFile(__DIR__ . '/candidate-dossier.template.html')
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
    });

$config = LetterConfig::fromFile(dirname(__DIR__) . '/letter/letter.yaml');

$letter = (new LetterDocument($config))
    ->applyTemplate($template)
    ->metadata($source->header)
    ->markdown($source->content);

$pdf = $letter->toPdf();
file_put_contents(__DIR__ . '/candidate-dossier-front-matter.pdf', $pdf);
