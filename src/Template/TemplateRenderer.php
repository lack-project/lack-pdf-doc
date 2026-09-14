<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Template;

use InvalidArgumentException;
use Phore\Markdown\Markdown;

final class TemplateRenderer
{
    /** @param array<string, mixed> $data @param array<string, string> $texts */
    public function render(LetterTemplate $template, array $data, array $texts): string
    {
        $html = '';
        foreach ($template->sections() as $section) {
            $html .= match ((string) $section['type']) {
                'table' => $this->renderTable($section, $data),
                'markdown' => $this->renderMarkdown($section, $texts),
                'fixed' => $this->renderFixed($section, $data),
                default => throw new InvalidArgumentException('Unsupported template section.'),
            };
        }
        return $html;
    }

    /** @param array<string, mixed> $section @param array<string, mixed> $data */
    private function renderTable(array $section, array $data): string
    {
        $title = $this->escape((string) ($section['title'] ?? ''));
        $rows = (array) ($section['rows'] ?? []);
        $imagePath = isset($section['image']) ? (string) $section['image'] : '';
        $imageAlias = $imagePath === '' ? '' : (string) $this->value($data, $imagePath);
        $imageWidth = $this->escape((string) ($section['imageWidth'] ?? '32mm'));

        $html = '<section class="template-section template-table-section">';
        if ($title !== '') {
            $html .= '<h2>' . $title . '</h2>';
        }
        if ($imageAlias !== '') {
            $this->assertAlias($imageAlias);
            $html .= '<div class="template-image"><img src="image:' . $this->escape($imageAlias) . '" style="max-width:' . $imageWidth . ';"></div>';
        }
        $html .= '<table class="template-data-table">';
        foreach ($rows as $row) {
            $row = (array) $row;
            $label = $this->escape((string) ($row['label'] ?? ''));
            $path = (string) ($row['value'] ?? '');
            $value = $path === '' ? '' : $this->scalar($this->value($data, $path));
            $html .= '<tr><th>' . $label . '</th><td>' . $this->escape($value) . '</td></tr>';
        }
        return $html . '</table></section>';
    }

    /** @param array<string, mixed> $section @param array<string, string> $texts */
    private function renderMarkdown(array $section, array $texts): string
    {
        $slot = (string) ($section['slot'] ?? '');
        if ($slot === '') {
            throw new InvalidArgumentException('Markdown template sections require a slot name.');
        }
        $title = $this->escape((string) ($section['title'] ?? ''));
        $html = '<section class="template-section template-text-section">';
        if ($title !== '') {
            $html .= '<h2>' . $title . '</h2>';
        }
        return $html . Markdown::toHtml($texts[$slot] ?? '') . '</section>';
    }

    /** @param array<string, mixed> $section @param array<string, mixed> $data */
    private function renderFixed(array $section, array $data): string
    {
        $title = $this->escape((string) ($section['title'] ?? ''));
        $text = (string) ($section['text'] ?? '');
        $text = preg_replace_callback('/\{\{([a-zA-Z0-9_.-]+)\}\}/', fn(array $m): string => $this->escape($this->scalar($this->value($data, $m[1]))), $text) ?? '';
        $html = '<section class="template-section template-fixed-section">';
        if ($title !== '') {
            $html .= '<h2>' . $title . '</h2>';
        }
        return $html . '<p>' . nl2br($text) . '</p></section>';
    }

    private function value(array $data, string $path): mixed
    {
        $value = $data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return '';
            }
            $value = $value[$part];
        }
        return $value;
    }

    private function scalar(mixed $value): string
    {
        if ($value === null || is_scalar($value)) {
            return (string) $value;
        }
        throw new InvalidArgumentException('Template values must resolve to scalar values.');
    }

    private function assertAlias(string $alias): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $alias)) {
            throw new InvalidArgumentException('Image template values must contain a registered image alias.');
        }
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
