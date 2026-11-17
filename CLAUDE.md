# Estudy — contexto para o Claude Code

PWA de organização de estudos pessoais (autodidata, cursos livres, extensão, graduação, pós). **Sem foco em concursos**:
o usuário cadastra os próprios cursos, disciplinas/módulos e tópicos.
Destino: https://estudy.jgmoreira.com.br (Hostoo, mesma conta e mesmo fluxo do Financee em `~/financee`).
Laravel 12 · PHP 8.4 · Livewire 3 + Alpine · Tailwind 4 · SQLite · Pest · Larastan. **Sem Docker.**
Mais leve que o Financee: sem chart.js (gráficos SVG no Blade), sem dompdf, sem intervention/image, sem pacote de ícones (sprite próprio).
Idioma do app, commits e conversa: **português do Brasil**.

## Status
Etapa 0 concluída (17/11/2026): Laravel no ar com deploy automático. Próxima: Etapa 1 (fundação). Plano: `docs/plano-geral.md`.

## Fluxo de trabalho
- Antes de publicar: `php artisan test`, `./vendor/bin/phpstan analyse --memory-limit=1G`, `./vendor/bin/pint`.
- Conferir telas no navegador (Chromium do Playwright em `~/.cache/ms-playwright`), nos temas claro e escuro.
- Deploy igual ao Financee: commit na `main` → `git push` → `scripts/release.sh` (PR para `production` com merge automático).
- Servidor: código em `~/public_html/estudy` (não editar lá); `.env`/banco/storage em `~/estudy-data`.
- PHP local: `export PATH="$HOME/.config/herd-lite/bin:$PATH"` se `php` não for encontrado.
- Ao copiar algo do Financee, adaptar nomes (`financee-data` → `estudy-data`) e **não** copiar o visual.

## Regras do domínio (não quebrar)
- Durações em **segundos (int)**; datas `AAAA-MM-DD` (cast `DateOnly`); "dia" no fuso `America/Sao_Paulo`; sessão que cruza meia-noite fica no dia de início.
- Todo model do usuário usa `BelongsToUser`. Em console/cron, `Auth::setUser` por usuário.
- Cronômetro: um por usuário, estado no servidor (`active_timers`); o cliente só desenha.
- Revisões: no máximo 1 pendente por tópico; criada só na primeira sessão sem revisão pendente; respeitam `study_days`.
- Dias fora de `study_days` não quebram a sequência.
- Em views Livewire, não reutilizar nomes de propriedades do componente como variáveis de `@foreach`.

## Documentação
`docs/plano-geral.md` · `docs/especificacao.md` (dados, regras, telas) · `docs/design.md` · `docs/pesquisa-mercado.md` · `docs/etapas/`.
