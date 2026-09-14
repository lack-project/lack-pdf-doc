<?php

declare(strict_types=1);

namespace Lack\PdfDoc;

use Lack\PdfDoc\Core\AbstractDocument;
use Lack\PdfDoc\Resource\FontSource;

final class SimpleDocument extends AbstractDocument
{
    public function __construct()
    {
        $this->font('body', FontSource::builtIn('helvetica'));
    }

    public function template(): string
    {
        return 'simple';
    }

    public function templateVariables(): array
    {
        return $this->getDocumentVariables();
    }

    public function styleVariables(): array
    {
        return [
            'bodyFont' => 'font:body',
            'bodyFontSize' => '11pt',
            'bodyLineHeight' => '1.45',
        ];
    }
}
