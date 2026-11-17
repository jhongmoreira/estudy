# Estudy

PWA de organização de estudos pessoais (Laravel 12 + Livewire + Tailwind + SQLite), mobile-first.
Para quem estuda por conta própria, faz cursos livres, extensão, graduação ou pós — você cadastra o que estuda.

**Recursos:** cursos → disciplinas/módulos → tópicos (com "colar lista"), cronômetro com modo foco e Pomodoro,
registro manual de sessões (páginas, questões), revisões espaçadas configuráveis, metas diárias/semanais e sequência de dias,
sugestões "o que estudar agora", ciclo de estudos, agenda de provas/entregas com médias, estatísticas (sem biblioteca de gráficos),
histórico de formação com certificados, relatórios em PDF (estudos, boletim, progresso, formação), notificações push, backup/importação JSON, CSV, tema escuro e app instalável.

- **Configurar em outro computador:** [`docs/desenvolvimento.md`](docs/desenvolvimento.md)
- Operação em produção: [`docs/go-live.md`](docs/go-live.md)
- Planejamento: [`docs/plano-geral.md`](docs/plano-geral.md) · [`docs/especificacao.md`](docs/especificacao.md) · [`docs/design.md`](docs/design.md) · [`docs/etapas/`](docs/etapas)
- Contexto para o Claude Code: [`CLAUDE.md`](CLAUDE.md)

```bash
composer install && npm install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed   # demo@estudy.test / demo12345
composer run dev
scripts/release.sh      # deploy (Hostoo)
```
