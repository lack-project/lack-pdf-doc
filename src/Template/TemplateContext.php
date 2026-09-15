<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

final class TemplateContext
{
    public function __construct(private readonly Document $document) {}

    public function meta(string $path): mixed
    {
        return $this->document->meta($path);
    }

    public function metadata(): array
    {
        return $this->document->resolvedMetadata();
    }

    public function config(): array
    {
        return $this->document->config();
    }
}
