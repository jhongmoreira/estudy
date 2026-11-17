<?php

it('mostra a página inicial', function () {
    $this->get('/')->assertOk()->assertSee('Estudy');
});

it('responde ao health check', function () {
    $this->get('/up')->assertOk();
});

it('envia os cabeçalhos de segurança', function () {
    $this->get('/')->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
