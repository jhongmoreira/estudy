<?php

namespace App\Support;

/** Paleta de cores de cursos/disciplinas (docs/design.md). */
final class Palette
{
    public const COLORS = [
        '#3047c9' => 'Anil',
        '#7c3aed' => 'Violeta',
        '#c026d3' => 'Magenta',
        '#e11d48' => 'Framboesa',
        '#ea580c' => 'Laranja',
        '#ca8a04' => 'Mostarda',
        '#65a30d' => 'Oliva',
        '#059669' => 'Jade',
        '#0891b2' => 'Petróleo',
        '#0284c7' => 'Céu',
        '#64748b' => 'Grafite',
        '#a16207' => 'Café',
    ];

    public static function isValid(?string $color): bool
    {
        return $color !== null && array_key_exists(strtolower($color), self::COLORS);
    }

    /** Cor de texto legível sobre a cor informada (preto ou branco, pela luminância). */
    public static function onColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = array_map(fn (string $c) => hexdec($c) / 255, str_split($hex, 2));
        $lin = fn (float $c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $luminance > 0.4 ? '#1f2a44' : '#ffffff';
    }
}
