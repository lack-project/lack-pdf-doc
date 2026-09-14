<?php

use Lack\PdfDoc\Template\TemplateDocument;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Entwurfsbeispiel: TemplateDocument ist noch nicht implementiert.
// safe: true erlaubt der vertrauenswürdigen Template-Kette relative file://-Ressourcen.
$template = TemplateDocument::fromTemplateFile(
    __DIR__ . '/letter.template.html',
    safe: true,
);

$pdf = $template
    ->fromMarkdownFile(__DIR__ . '/letter.md')
    ->toPdf();

file_put_contents(__DIR__ . '/letter-from-template.pdf', $pdf);

// Dieselbe Template-Kette; nur company.logo wird im Dokument-Front-Matter überschrieben.
$pdfWithCustomLogo = TemplateDocument::fromTemplateFile(
    __DIR__ . '/letter.template.html',
    safe: true,
)
    ->fromMarkdownFile(__DIR__ . '/letter-custom-logo.md')
    ->toPdf();

file_put_contents(__DIR__ . '/letter-with-custom-logo.pdf', $pdfWithCustomLogo);
