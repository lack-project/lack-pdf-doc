<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use Lack\PdfDoc\Letter\LetterConfig;
use Lack\PdfDoc\Letter\LetterDocument;

final class LetterTemplateDocument extends LetterDocument
{
    private array $data = [];
    private array $texts = [];

    public function __construct(
        private readonly LetterTemplate $letterTemplate,
        LetterConfig $config = new LetterConfig(),
        private readonly TemplateRenderer $templateRenderer = new TemplateRenderer(),
    ) {
        parent::__construct($config);
        $this->refreshContent();
    }

    public function data(array $data): self
    {
        $this->data = array_replace_recursive($this->data, $data);
        $this->refreshContent();
        return $this;
    }

    public function text(string $slot, string $markdown): self
    {
        $this->texts[$slot] = $markdown;
        $this->refreshContent();
        return $this;
    }

    private function refreshContent(): void
    {
        $this->html($this->templateRenderer->render($this->letterTemplate, $this->data, $this->texts));
    }
}
