<?php

use App\Models\User;

it('manda visitantes para o login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('abre todas as telas principais', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk()
        ->assertSee('Estudar agora');
})->with(['home', 'courses', 'study', 'reviews', 'more', 'profile', 'settings', 'sessions', 'cycle', 'agenda']);

it('mostra saudação, meta e semana na tela Hoje', function () {
    $user = User::factory()->create(['name' => 'Ana Souza', 'settings' => ['daily_goal_min' => 90]]);

    $this->actingAs($user)->get('/')
        ->assertSee('Ana')
        ->assertSee('de 90 min')
        ->assertSee('Sua semana');
});

it('serve o PWA: manifest, service worker e página offline', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
    expect($manifest['name'])->toBe('Estudy')->and($manifest['display'])->toBe('standalone');
    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }

    $this->get('/sw.js')->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->assertSee('estudy-');
    $this->get('/offline')->assertOk()->assertSee('Sem conexão');
});

it('responde ao health check e envia cabeçalhos de segurança', function () {
    $this->get('/up')->assertOk();
    $this->get('/login')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('mostra páginas de erro em português', function () {
    $this->actingAs(User::factory()->create())->get('/nao-existe')->assertNotFound()->assertSee('Página não encontrada');
});
