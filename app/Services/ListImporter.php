<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\Duration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Colar lista": transforma um texto (ementa, sumário de livro, grade de um curso online)
 * em disciplinas/módulos e tópicos. Ver docs/especificacao.md §5.
 *
 * - Uma linha = um tópico. Marcadores (-, *, •, 1., 1), a), [ ]) são removidos.
 * - Dentro de um curso, vira disciplina/módulo a linha que termina com ":", está em CAIXA ALTA
 *   ou é seguida de linhas mais recuadas.
 * - "(45min)", "[1h30]" ou "— 45 min" no fim da linha viram a estimativa do tópico.
 */
class ListImporter
{
    public const MAX_TOPICS = 500;

    private const BULLET = '/^(?:[-*•–·+>]|\d{1,3}[.)]|[a-zA-Z][)]|\[[ xX]?\])\s+/u';

    private const DURATION = '(\d+\s*h\s*\d{0,2}\s*(?:min|m)?|\d+(?:[.,]\d+)?\s*h|\d+\s*(?:min|mins|minutos?|m))';

    /**
     * @return list<array{subject: string|null, topics: list<array{title: string, minutes: int|null}>}>
     */
    public function parse(string $text, bool $allowSubjects): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $raw) {
            $expanded = str_replace("\t", '    ', rtrim($raw));
            if (trim($expanded) === '') {
                continue;
            }
            $lines[] = ['indent' => strlen($expanded) - strlen(ltrim($expanded)), 'text' => trim($expanded)];
        }

        $groups = [];
        $current = null;

        foreach ($lines as $i => $line) {
            $text = trim((string) preg_replace(self::BULLET, '', $line['text']));
            $nextIndent = $lines[$i + 1]['indent'] ?? -1;

            if ($allowSubjects && $this->isHeader($text, $line['indent'], $nextIndent)) {
                $name = Str::limit(rtrim($text, ': '), 120, '');
                $groups[] = ['subject' => $this->tidyHeader($name), 'topics' => []];
                $current = array_key_last($groups);

                continue;
            }

            [$title, $minutes] = $this->splitDuration(rtrim($text, ':'));
            if ($title === '') {
                continue;
            }

            if ($current === null) {
                $groups[] = ['subject' => null, 'topics' => []];
                $current = array_key_last($groups);
            }

            $groups[$current]['topics'][] = ['title' => Str::limit($title, 200, ''), 'minutes' => $minutes];
        }

        return array_values(array_filter($groups, fn ($g) => $g['subject'] !== null || $g['topics'] !== []));
    }

    /**
     * Grava o resultado do parse num curso (cria disciplinas) ou numa disciplina.
     *
     * @param  list<array{subject: string|null, topics: list<array{title: string, minutes: int|null}>}>  $groups
     * @return array{subjects: int, topics: int}
     */
    public function store(array $groups, Course $course, ?Subject $into = null): array
    {
        return DB::transaction(function () use ($groups, $course, $into) {
            $subjects = 0;
            $topics = 0;
            $nextSubjectPosition = (int) $course->subjects()->max('position') + 1;

            foreach ($groups as $group) {
                $subject = $into;

                if ($subject === null) {
                    $name = $group['subject'] ?? 'Conteúdo';
                    $subject = $course->subjects()->where('name', $name)->first()
                        ?? $course->subjects()->create(['name' => $name, 'position' => $nextSubjectPosition++]);
                    $subjects += $subject->wasRecentlyCreated ? 1 : 0;
                }

                $position = (int) $subject->topics()->max('position') + 1;
                foreach ($group['topics'] as $topic) {
                    if ($topics >= self::MAX_TOPICS) {
                        break 2;
                    }
                    Topic::create([
                        'subject_id' => $subject->id,
                        'title' => $topic['title'],
                        'estimated_min' => $topic['minutes'],
                        'position' => $position++,
                    ]);
                    $topics++;
                }
            }

            return ['subjects' => $subjects, 'topics' => $topics];
        });
    }

    private function isHeader(string $text, int $indent, int $nextIndent): bool
    {
        if ($text === '' || mb_strlen($text) > 120) {
            return false;
        }

        $letters = preg_replace('/[^\p{L}]/u', '', $text) ?? '';
        $upper = mb_strlen($letters) >= 3 && mb_strtoupper($letters) === $letters;

        return str_ends_with($text, ':') || $upper || $nextIndent > $indent;
    }

    /** "UNIDADE 1 - INTRODUÇÃO" → "Unidade 1 - Introdução". */
    private function tidyHeader(string $name): string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $name) ?? '';

        return mb_strtoupper($letters) === $letters && $letters !== ''
            ? mb_convert_case(mb_strtolower($name), MB_CASE_TITLE)
            : $name;
    }

    /** @return array{0: string, 1: int|null} */
    private function splitDuration(string $text): array
    {
        $patterns = [
            '/\s*[(\[]\s*'.self::DURATION.'\s*[)\]]\s*$/iu',
            '/\s+[—–-]\s*'.self::DURATION.'\s*$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $seconds = Duration::parse($m[1]);
                if ($seconds !== null && $seconds > 0) {
                    return [trim((string) preg_replace($pattern, '', $text)), intdiv($seconds, 60)];
                }
            }
        }

        return [$text, null];
    }
}
