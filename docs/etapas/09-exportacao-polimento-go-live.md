# Etapa 9 — Exportação, polimento e go-live

> **Status (17/11/2026):** implementada — versão 1.0. Backup JSON (`format: 1`) com importação que substitui tudo (prévia + senha),
> CSV de sessões e de formação, "Recomeçar do zero", seeder de demonstração, atalho de espaço no modo foco, `go-live.md` e
> `desenvolvimento.md`. Medição local com o demo (6 meses): tela Hoje em 36 consultas / ~50 ms de PHP; JS total ~2,8 kB gz (+ Livewire/Alpine), CSS ~8,6 kB gz.

## 9.1 Exportação e importação
- **CSV**: sessões (filtro de período/curso) e histórico de formação.
- **JSON completo** ("Exportar todos os meus dados"), versionado (`"format": 1`).
- **Importar JSON** do próprio Estudy (restaurar/migrar conta): validação, pré-visualização de contagens, transação; só em conta vazia ou com "substituir tudo" (confirmação por senha).
- **Zerar dados** (mantém a conta), com confirmação por senha.

## 9.2 Polimento
- Páginas de erro em pt-BR no visual novo (404, 419, 429, 500, 503).
- Skeletons e `wire:loading` nas telas com agregações; transições respeitando `prefers-reduced-motion`.
- Revisão de acessibilidade (contraste AA, foco visível, rótulos), tablet/paisagem (reclamação recorrente nos concorrentes), teclado no desktop (espaço = pausar no modo foco; `n` = novo tópico).
- Performance: `php artisan optimize`, OPcache, índices conferidos com `EXPLAIN QUERY PLAN`, `preventLazyLoading`; meta: TTFB < 200 ms nas telas principais com 2 anos de dados (seeder de carga), JS total < 60 kB gz (Livewire + Alpine + app).
- Seeder de demonstração (só local): `demo@estudy.test / demo12345` com graduação, pós e 2 cursos livres, ~6 meses de sessões, revisões e notas.

## 9.3 Segurança e operação
- `SecurityHeaders` (CSP self, HSTS, X-Frame-Options, Referrer-Policy), cookie seguro, rate limit em auth e upload.
- `app:db-maintenance` (PRAGMA optimize semanal, VACUUM mensal) — copiado.
- `docs/go-live.md` (no formato do Financee): o que roda no cron, `.env`, SMTP, backup (`~/estudy-data/database.sqlite` + export JSON), cuidados com o cPanel.
- `docs/desenvolvimento.md`: configurar em outro computador.
- `CLAUDE.md` atualizado com as regras finais.

## Checklist de go-live
- [ ] `.env` de produção (APP_DEBUG=false, LOG_STACK=daily)
- [ ] Cron e post-deploy validados; Financee intacto
- [ ] HTTPS + cabeçalhos de segurança
- [ ] PWA instalável (Android/iOS), ícones, splash, offline
- [ ] SMTP (esqueci a senha) testado
- [ ] Push testado em Android e iOS (PWA instalado)
- [ ] Uso real por 1–2 semanas
- [ ] Tag `v1.0.0`

## Critério de pronto
Versão 1.0 em https://estudy.jgmoreira.com.br, com testes, Larastan e Pint limpos e documentação de operação.
