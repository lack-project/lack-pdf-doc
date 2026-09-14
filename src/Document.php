<?php

declare(strict_types=1);

namespace Lack\PdfDoc;

final class Document
{
    private array $variables = [];
    private string $markdown = '';

    public function __construct(private readonly string $template = 'default')
    {
    }

    public function with(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->variables[$name] = $value;
        return $clone;
    }

    public function markdown(string $markdown): self
    {
        $clone = clone $this;
        $clone->markdown = $markdown;
        return $clone;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getMarkdown(): string
    {
        return $this->markdown;
    }
}
