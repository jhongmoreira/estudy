<?php

use App\Models\Course;
use App\Models\User;
use App\Services\ListImporter;

beforeEach(fn () => $this->importer = new ListImporter);

it('transforma cada linha em tópico, removendo marcadores', function () {
    $groups = $this->importer->parse("- Introdução\n* Variáveis\n• Tipos\n1. Funções\n2) Classes\n[ ] Testes\na) Extra\n\n   \n", false);

    expect($groups)->toHaveCount(1)
        ->and(array_column($groups[0]['topics'], 'title'))->toBe(['Introdução', 'Variáveis', 'Tipos', 'Funções', 'Classes', 'Testes', 'Extra']);
});

it('mantém numeração de seção como 1.1', function () {
    $groups = $this->importer->parse("1.1 Conceitos\n1.2 Exemplos", false);

    expect(array_column($groups[0]['topics'], 'title'))->toBe(['1.1 Conceitos', '1.2 Exemplos']);
});

it('lê estimativas no fim da linha', function () {
    $groups = $this->importer->parse("Aula 1 (45min)\nAula 2 [1h30]\nAula 3 — 20 min\nAula 4 - 2h\nAula 5 (1,5h)\nCapítulo 10", false);

    expect(array_map(fn ($t) => [$t['title'], $t['minutes']], $groups[0]['topics']))->toBe([
        ['Aula 1', 45], ['Aula 2', 90], ['Aula 3', 20], ['Aula 4', 120], ['Aula 5', 90], ['Capítulo 10', null],
    ]);
});

it('cria disciplinas a partir de CAIXA ALTA, dois-pontos e recuo', function () {
    $text = <<<'TXT'
    UNIDADE 1 - FUNDAMENTOS
    - Introdução
    - Histórico
    Módulo 2:
    - Prática
    Projeto final
        Escopo
    	Entrega
    TXT;

    $groups = $this->importer->parse($text, true);

    expect(array_column($groups, 'subject'))->toBe(['Unidade 1 - Fundamentos', 'Módulo 2', 'Projeto final'])
        ->and(array_column($groups[0]['topics'], 'title'))->toBe(['Introdução', 'Histórico'])
        ->and(array_column($groups[2]['topics'], 'title'))->toBe(['Escopo', 'Entrega']);
});

it('dentro de uma disciplina, tudo vira tópico', function () {
    $groups = $this->importer->parse("PARTE A:\n- item", false);

    expect($groups[0]['subject'])->toBeNull()
        ->and(array_column($groups[0]['topics'], 'title'))->toBe(['PARTE A', 'item']);
});

it('grava em curso: tópicos soltos vão para "Conteúdo" e disciplinas repetidas são reaproveitadas', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $course = Course::factory()->create(['user_id' => $user->id]);
    $course->subjects()->create(['name' => 'Revisão']);

    $result = $this->importer->store($this->importer->parse("Solto\nREVISÃO:\n- a\n- b\nNOVO:\n- c", true), $course);

    expect($result)->toBe(['subjects' => 2, 'topics' => 4])
        ->and($course->subjects()->pluck('name')->all())->toBe(['Revisão', 'Conteúdo', 'Novo'])
        ->and($course->subjects()->where('name', 'Revisão')->first()->topics()->pluck('title')->all())->toBe(['a', 'b']);
});
