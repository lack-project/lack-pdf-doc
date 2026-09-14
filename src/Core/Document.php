<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

abstract class Document
{
    private string $markdown = '';
    private ?string $html = null;

    final public function markdown(string $markdown): static
    {
        $this->markdown = $markdown;
        $this->html = null;
        return $this;
    }

    final public function html(string $html): static
    {
        $this->html = $html;
        $this->markdown = '';
        return $this;
    }

    final public function getMarkdown(): string { return $this->markdown; }
    final public function getHtml(): ?string { return $this->html; }

    abstract public function template(): string;
    abstract public function variables(): array;
    abstract public function styleVariables(): array;
}
