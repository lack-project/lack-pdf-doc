<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

abstract class Document
{
    private string $markdown = '';
    private ?string $html = null;

    /** @var array<string, ImageSource> */
    private array $images = [];

    /** @var array<string, FontSource> */
    private array $fonts = [];

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

    final public function image(string $alias, ImageSource $source): static
    {
        $this->images[$alias] = $source;
        return $this;
    }

    final public function imageBytes(string $alias, string $bytes, string $mimeType): static
    {
        return $this->image($alias, ImageSource::bytes($bytes, $mimeType));
    }

    final public function imageResolver(string $alias, callable $resolver, string $mimeType): static
    {
        return $this->image($alias, ImageSource::resolver($resolver, $mimeType));
    }

    final public function font(string $alias, FontSource $source): static
    {
        $this->fonts[$alias] = $source;
        return $this;
    }

    final public function getMarkdown(): string { return $this->markdown; }
    final public function getHtml(): ?string { return $this->html; }
    final public function getImages(): array { return $this->images; }
    final public function getFonts(): array { return $this->fonts; }

    abstract public function template(): string;
    abstract public function variables(): array;
    abstract public function styleVariables(): array;
}
