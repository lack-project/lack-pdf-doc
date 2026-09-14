<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Letterhead;

use Lack\PdfDoc\Core\Document;
use Lack\PdfDoc\Core\FontSource;

final class LetterheadDocument extends Document
{
    private array $values = [];

    public function __construct(private readonly LetterheadStyle $style = new LetterheadStyle())
    {
        $this->font('body', FontSource::registered('helvetica'));
    }

    public function logo(string $alias): self { $this->values['logo'] = 'image:' . $alias; return $this; }
    public function sender(string $text): self { $this->values['sender'] = $text; return $this; }
    public function recipient(string $text): self { $this->values['recipient'] = nl2br(htmlspecialchars($text, ENT_QUOTES)); return $this; }
    public function header(string $html): self { $this->values['header'] = $html; return $this; }

    public function footer(string $address, string $bank, string $contact, string $extra = ''): self
    {
        $this->values['footerAddress'] = $address;
        $this->values['footerBank'] = $bank;
        $this->values['footerContact'] = $contact;
        $this->values['footerExtra'] = $extra;
        return $this;
    }

    public function template(): string { return 'letterhead'; }
    public function variables(): array { return $this->values; }
    public function styleVariables(): array { return $this->style->variables(); }
}
