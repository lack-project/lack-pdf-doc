<?php

declare(strict_types=1);

namespace Lack\PdfDoc\Core;

use RuntimeException;

final class FontRegistry
{
    /** @param array<string, FontSource> $fonts */
    public static function resolveCssAliases(string $css, array $fonts): string
    {
        return preg_replace_callback('/font:([a-zA-Z0-9_.-]+)/', static function (array $match) use ($fonts): string {
            $source = $fonts[$match[1]] ?? null;
            if (!$source instanceof FontSource) {
                throw new RuntimeException('Unknown font alias: ' . $match[1]);
            }
            return $source->family();
        }, $css) ?? throw new RuntimeException('Unable to resolve font aliases.');
    }
}
