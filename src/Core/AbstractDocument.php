<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use Lack\PdfDoc\Form\InteractiveForm;
use Lack\PdfDoc\Resource\FontSource;
use Lack\PdfDoc\Resource\ImageSource;

abstract class AbstractDocument
{
    private string $markdown = '';
    private ?string $html = null;
    private array $variables = [];
    private ?InteractiveForm $form = null;
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

    final public function variables(array $variables): static
    {
        $this->variables = array_replace($this->variables, $variables);
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

    final public function imageFromCallback(string $alias, callable $callback, string $mimeType): static
    {
        return $this->image($alias, ImageSource::fromCallback($callback, $mimeType));
    }

    final public function font(string $alias, FontSource $source): static
    {
        $this->fonts[$alias] = $source;
        return $this;
    }

    final public function form(): InteractiveForm
    {
        return $this->form ??= new InteractiveForm();
    }

    final public function toPdf(): string
    {
        return (new PdfRenderer())->render($this);
    }

    final public function getMarkdown(): string { return $this->markdown; }
    final public function getHtml(): ?string { return $this->html; }
    final public function getDocumentVariables(): array { return $this->variables; }
    final public function getImages(): array { return $this->images; }
    final public function getFonts(): array { return $this->fonts; }
    final public function getForm(): ?InteractiveForm { return $this->form; }

    abstract public function template(): string;
    abstract public function templateVariables(): array;
    abstract public function styleVariables(): array;
}
