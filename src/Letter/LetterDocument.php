<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letter;

use Lack\PdfDoc\Core\AbstractDocument;
use Lack\PdfDoc\Resource\FontSource;

class LetterDocument extends AbstractDocument
{
    private string $recipientAddress = '';
    private string $referenceBlock = '';

    public function __construct(protected readonly LetterConfig $config = new LetterConfig())
    {
        foreach ($this->config->fonts as $alias => $font) {
            $this->font($alias, $font);
        }
        if (!isset($this->config->fonts['body'])) {
            $this->font('body', FontSource::builtIn('helvetica'));
        }
        if ($this->config->logo !== null && $this->config->logoSource !== null) {
            $this->image($this->config->logo, $this->config->logoSource);
        }
    }

    public function recipientAddress(string $address): self
    {
        $this->recipientAddress = $address;
        return $this;
    }

    public function referenceBlock(string $content): self
    {
        $this->referenceBlock = $content;
        return $this;
    }

    public function template(): string
    {
        return 'letter';
    }

    public function templateVariables(): array
    {
        $values = array_replace($this->config->variables, $this->getDocumentVariables());
        $values = array_replace($values, $this->config->footer->variables());
        $values['logo'] = $this->config->logo === null ? '' : 'image:' . $this->config->logo;
        $values['returnAddress'] = htmlspecialchars($this->config->returnAddress, ENT_QUOTES);
        $values['recipientAddress'] = nl2br(htmlspecialchars($this->recipientAddress, ENT_QUOTES));
        $values['referenceBlock'] = $this->referenceBlock;
        return $values;
    }

    public function styleVariables(): array
    {
        return $this->config->layout->variables();
    }
}
