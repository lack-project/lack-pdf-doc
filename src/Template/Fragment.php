<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use RuntimeException;

final class Fragment
{
    private function __construct(
        private readonly string $file,
        private readonly array $header,
        private readonly string $body,
    ) {}

    public static function fromFile(string $file): self
    {
        $phoreFile = phore_file($file);
        $contents = $phoreFile->get_contents();
        $header = [];
        $body = $contents;

        if (str_starts_with($contents, "---\n") || str_starts_with($contents, "---\r\n")) {
            $source = $phoreFile->get_front_matter();
            $header = (array) $source->header;
            $body = (string) $source->content;
        }

        $type = $header['type'] ?? null;
        if ($type !== 'fragment') {
            $actual = is_scalar($type) ? (string) $type : 'missing';
            throw new RuntimeException('Expected type "fragment", got "' . $actual . '": ' . $file);
        }

        return new self($file, $header, $body);
    }

    public function file(): string
    {
        return $this->file;
    }

    public function pages(): string
    {
        return (string) ($this->header['pages'] ?? 'all');
    }

    public function position(): ?array
    {
        return isset($this->header['position']) ? (array) $this->header['position'] : null;
    }

    public function placement(): ?string
    {
        $value = $this->header['placement'] ?? null;
        return is_string($value) ? $value : null;
    }

    public function condition(): ?string
    {
        $value = $this->header['if'] ?? null;
        return is_string($value) ? $value : null;
    }

    public function pageBreakBefore(): bool
    {
        return ($this->header['pageBreakBefore'] ?? false) === true;
    }

    public function format(): string
    {
        return (string) ($this->header['format'] ?? 'markdown');
    }

    public function body(): string
    {
        return $this->body;
    }
}
