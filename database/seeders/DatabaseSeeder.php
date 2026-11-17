<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\StudyCycle;
use App\Models\User;
use App\Services\CycleService;
use App\Services\DataPortability;
use App\Services\ReviewService;
use App\Services\SessionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Dados de demonstração (só fora de produção): demo@estudy.test / demo12345,
 * com graduação, pós, curso livre e idioma, ~6 meses de sessões, revisões, agenda e ciclo.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Seeder de demonstração ignorado em produção.');

            return;
        }

        $user = User::updateOrCreate(['email' => 'demo@estudy.test'], [
            'name' => 'Ana Demo', 'password' => 'demo12345', 'onboarded_at' => now(),
            'settings' => ['daily_goal_min' => 60, 'study_days' => [1, 2, 3, 4, 5, 6], 'reminder_time' => '19:00'],
        ]);
        Auth::setUser($user);
        app(DataPortability::class)->reset($user);
        mt_srand(42);

        $plan = [
            ['Engenharia de Software', 'undergrad', 'UFMG', '#3047c9', 'active', [
                'Cálculo II' => ['Limites', 'Derivadas parciais', 'Integrais duplas', 'Integrais triplas', 'Séries'],
                'Estrutura de Dados' => ['Listas ligadas', 'Pilhas e filas', 'Árvores binárias', 'Heaps', 'Grafos', 'Hashing'],
                'Banco de Dados' => ['Modelo relacional', 'SQL básico', 'Joins', 'Normalização', 'Índices'],
                'Engenharia de Requisitos' => ['Elicitação', 'Casos de uso', 'Histórias de usuário'],
            ]],
            ['MBA em Gestão de Projetos', 'postgrad', 'FGV', '#7c3aed', 'active', [
                'Gerenciamento de Escopo' => ['EAP', 'Declaração de escopo', 'Controle de mudanças'],
                'Métodos Ágeis' => ['Scrum', 'Kanban', 'Métricas ágeis'],
            ]],
            ['Laravel do zero', 'free', 'Alura', '#e11d48', 'active', [
                'Fundamentos' => ['Rotas', 'Controllers', 'Blade'],
                'Banco' => ['Migrations', 'Eloquent', 'Relacionamentos'],
            ]],
            ['Inglês B2', 'language', null, '#ca8a04', 'active', [
                'Listening' => ['Podcasts', 'Séries com legenda'],
                'Grammar' => ['Present perfect', 'Conditionals', 'Passive voice'],
            ]],
            ['Livro: Hábitos Atômicos', 'self', 'James Clear', '#64748b', 'completed', [
                'Leitura' => ['Parte 1', 'Parte 2', 'Parte 3', 'Parte 4'],
            ]],
        ];

        $subjects = [];
        foreach ($plan as $position => [$name, $type, $institution, $color, $status, $content]) {
            $course = Course::create(compact('name', 'type', 'institution', 'color', 'status', 'position') + [
                'started_on' => now()->subMonths(6)->toDateString(),
                'completed_on' => $status === 'completed' ? now()->subMonth()->toDateString() : null,
                'workload_hours' => $status === 'completed' ? 12 : ($type === 'free' ? 40 : null),
                'weekly_goal_min' => $type === 'undergrad' ? 600 : null,
            ]);
            $p = 0;
            foreach ($content as $subjectName => $topics) {
                $subject = $course->subjects()->create(['name' => $subjectName, 'position' => $p++, 'term' => $course->isAcademic() ? '2026.2' : null, 'passing_grade' => $course->isAcademic() ? 7 : null]);
                foreach ($topics as $i => $title) {
                    $subject->topics()->create(['title' => $title, 'position' => $i, 'difficulty' => mt_rand(0, 3) ?: null, 'estimated_min' => mt_rand(0, 1) ? mt_rand(3, 12) * 10 : null]);
                }
                if ($status === 'active') {
                    $subjects[] = $subject;
                }
            }
        }

        // ~6 meses de sessões
        $sessions = app(SessionService::class);
        $kinds = ['theory', 'theory', 'video', 'exercises', 'exercises', 'class', 'practice'];
        for ($d = CarbonImmutable::today()->subDays(180); $d->lt(CarbonImmutable::today()); $d = $d->addDay()) {
            if (mt_rand(1, 10) <= 3) {
                continue;
            }
            foreach (range(1, mt_rand(1, 3)) as $n) {
                $subject = $subjects[array_rand($subjects)];
                $topic = $subject->topics()->inRandomOrder()->first();
                $kind = $kinds[array_rand($kinds)];
                $q = $kind === 'exercises' ? mt_rand(10, 30) : null;
                $sessions->record([
                    'subject_id' => $subject->id, 'topic_id' => $topic?->id, 'date' => $d->toDateString(),
                    'started_at' => $d->setTime([7, 8, 9, 14, 19, 20, 21][array_rand([7, 8, 9, 14, 19, 20, 21])], mt_rand(0, 5) * 10)->toDateTimeString(),
                    'duration_seconds' => mt_rand(2, 10) * 600, 'kind' => $kind,
                    'pages' => $kind === 'theory' ? mt_rand(5, 30) : null,
                    'questions_total' => $q, 'questions_correct' => $q ? mt_rand((int) ($q * 0.4), $q) : null,
                    'mark_done' => mt_rand(1, 6) === 1,
                ]);
            }
        }
        app(ReviewService::class)->rescheduleOverdue();

        // Agenda da graduação
        $calc = Course::where('type', 'undergrad')->first()?->subjects()->where('name', 'Cálculo II')->first();
        if ($calc) {
            foreach ([['P1', -20, 6.5], ['Lista 1', -10, 9.0], ['P2', 3, null], ['Trabalho final', 12, null]] as [$title, $days, $grade]) {
                AgendaItem::create(['course_id' => $calc->course_id, 'subject_id' => $calc->id, 'title' => $title, 'type' => str_starts_with($title, 'P') ? 'exam' : 'assignment',
                    'due_on' => now()->addDays($days)->toDateString(), 'grade' => $grade, 'max_grade' => 10, 'weight' => $title === 'Trabalho final' ? 2 : 1, 'status' => $grade !== null ? 'done' : 'open']);
            }
        }

        // Ciclo
        $cycles = app(CycleService::class);
        $cycle = StudyCycle::create(['name' => 'Semestre 2026.2']);
        $cycles->replaceItems($cycle, $cycles->distribute(collect($subjects)->take(5)->mapWithKeys(fn ($s, $i) => [$s->id => 5 - $i])->all(), 600, 60));
        $cycles->activate($cycle);

        Auth::forgetUser();
        $this->command?->info('Demo pronto: demo@estudy.test / demo12345');
    }
}
