<?php

namespace App\Support;

/** Durações são sempre guardadas em segundos (int). */
final class Duration
{
    /** "1h05", "45 min", "0 min". */
    public static function human(int $seconds): string
    {
        $minutes = intdiv(max(0, $seconds), 60);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest.' min';
        }

        return $rest === 0 ? $hours.'h' : sprintf('%dh%02d', $hours, $rest);
    }

    /** Compacto para espaços pequenos: "25m", "1h05", "2h". */
    public static function short(int $seconds): string
    {
        $minutes = intdiv(max(0, $seconds), 60);

        return $minutes < 60 ? $minutes.'m' : self::human($seconds);
    }

    /** "00:42:17" (relógio). */
    public static function clock(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /**
     * Interpreta o que o usuário digita: "90" (min), "1h30", "1h", "1:30", "45min", "1,5h".
     * Retorna segundos ou null se não entender.
     */
    public static function parse(string $input): ?int
    {
        $s = strtolower(str_replace([' ', ','], ['', '.'], trim($input)));

        if ($s === '') {
            return null;
        }
        if (preg_match('/^(\d+)$/', $s, $m)) {
            return (int) $m[1] * 60;
        }
        if (preg_match('/^(\d+):([0-5]?\d)$/', $s, $m)) {
            return ((int) $m[1] * 60 + (int) $m[2]) * 60;
        }
        if (preg_match('/^(\d+(?:\.\d+)?)h$/', $s, $m)) {
            return (int) round((float) $m[1] * 3600);
        }
        if (preg_match('/^(\d+)h([0-5]?\d)(?:m|min)?$/', $s, $m)) {
            return ((int) $m[1] * 60 + (int) $m[2]) * 60;
        }
        if (preg_match('/^(\d+)(?:m|min|mins|minutos?)$/', $s, $m)) {
            return (int) $m[1] * 60;
        }

        return null;
    }
}
