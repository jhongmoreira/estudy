# Estudy — Plano Geral

> **Status (17/11/2026):** Etapas 0 a 3 concluídas (https://estudy.jgmoreira.com.br). Próxima: Etapa 4 (revisões espaçadas).

PWA de **organização de estudos pessoais**, mobile-first: cursos, disciplinas e tópicos cadastrados pelo
próprio usuário, cronômetro, registro de sessões, revisões espaçadas, metas semanais, prazos/notas e estatísticas.
Inspirado no Estudei/Estudaqui (ver [pesquisa-mercado.md](pesquisa-mercado.md)), mas **sem foco em concursos**:
serve para autodidata, cursos livres, extensão, graduação, pós etc.

Documentos:
- [especificacao.md](especificacao.md) — modelo de dados, regras de negócio e telas
- [design.md](design.md) — identidade visual (própria, diferente do Financee)
- [etapas/](etapas) — plano de cada etapa

## 1. Decisões fechadas

| Tema | Decisão |
|---|---|
| Backend | Laravel 12 (PHP 8.4) — mesma base do Financee |
| Frontend | Blade + Livewire 3 + Alpine.js + Tailwind CSS 4 (Vite, build local) |
| Banco | SQLite (WAL), arquivo **fora** da pasta do deploy (`~/estudy-data`) |
| Auth | E-mail e senha (Fortify headless + telas próprias); cadastro pode ser fechado via `.env` |
| Usuários | Multiusuário, dados isolados por `user_id` |
| Conteúdo | **100% cadastrado pelo usuário** (nada de editais/catálogo). Atalho: colar lista → tópicos |
| Online | Requer internet; service worker cacheia só o shell. O **cronômetro continua contando** sem rede (estado no servidor + relógio local) |
| Idioma/fuso | pt-BR, `America/Sao_Paulo` |
| Layout | Mobile-first (bottom nav com botão central "Estudar"), sidebar no desktop |
| Hospedagem | **Mesma do Financee** (Hostoo compartilhada, integração Git na branch `production`, cron). Sem Docker |
| Fora do escopo | Docker, offline-first, banco de questões, flashcards, ranking/social, IA, catálogo de editais, multi-idioma |

### Mais leve que o Financee
| Financee | Estudy | Por quê |
|---|---|---|
| `chart.js` (~70 kB gz) | **Gráficos em SVG gerados no Blade** (barras, heatmap, rosca) | zero JS de gráfico; renderiza no servidor |
| `blade-ui-kit/blade-heroicons` | **Sprite SVG próprio** (~35 ícones Lucide) via `<x-icon>` | menos pacotes e cache de views menor |
| `barryvdh/laravel-dompdf` | **sem PDF**: exportação CSV/JSON + página "imprimir" (CSS `@media print`) | dompdf é o pacote mais pesado |
| `intervention/image` (avatar) | **sem foto**: avatar com iniciais e cor | sem processamento de imagem |
| — | `laravel-notification-channels/webpush` mantido | lembretes de revisão/prazo são essenciais |

Dependências finais: `laravel/framework`, `livewire/livewire`, `laravel/fortify`, `laravel-notification-channels/webpush`
(+ dev: pest, larastan, pint, pt-br-localization). Front: `tailwindcss`, `@tailwindcss/vite`, `vite`, `laravel-vite-plugin`, `sharp` (só nos scripts de ícones).

## 2. Arquitetura

```
Navegador / PWA instalado
  ├─ Service Worker (shell, assets versionados, offline, push)
  ├─ Alpine: relógio do cronômetro (usa timestamps do servidor)
  └─ Livewire (wire:navigate, @persist da barra do cronômetro)
        │ HTTPS
Hostoo (LiteSpeed + PHP 8.4)
  └─ Laravel 12
       ├─ Livewire (telas finas)
       ├─ Services (Timer, Sessões, Revisões, Progresso, Metas/Constância, Ciclo, Estatísticas, Notificações)
       ├─ Queue "database" + Scheduler → cron 1x/min
       └─ SQLite (~/estudy-data/database.sqlite)
```

Princípios (herdados do Financee):
- **Durações em segundos (int)**; datas `AAAA-MM-DD` (cast `DateOnly`); horários em UTC no banco, exibidos em `America/Sao_Paulo`.
- Regras em **Services testados (Pest)**; componentes Livewire finos.
- Escopo por usuário (`BelongsToUser` + Policies). Em console: `Auth::setUser` por usuário.
- Consultas agregadas com índices, sem N+1 (`Model::shouldBeStrict` em dev).

## 3. Reaproveitar do Financee (copiar e adaptar, não reescrever)
| Arquivo | Ajuste |
|---|---|
| `bootstrap/app.php` (pasta de dados fora do deploy) | `financee-data` → `estudy-data` |
| `scripts/release.sh` | sem mudanças (usa o repositório atual) |
| `app/Console/Commands/PostDeploy*` + agendamentos de `routes/console.php` | mesmos comandos de deploy, fila e manutenção do SQLite |
| `app/Http/Middleware/SecurityHeaders.php` | CSP sem CDN externo |
| `app/Casts/DateOnly.php`, `app/Models/Concerns/BelongsToUser.php` | idem |
| `app/Support/VapidKeys.php`, `resources/js/sw.js`, `scripts/icons.mjs`, `scripts/splash.mjs` | novo ícone/cores |
| `.htaccess` (raiz e `public/`), `lang/pt_BR*`, `config/database.php` (pragmas) | idem |
| Telas de auth/perfil/erros | **reescrever o visual** (ver design.md) |

## 4. Navegação (mobile)

```
┌──────────────────────────────┐
│  Estudy        ⌕   🔔   (JM) │
│                              │
│        conteúdo da tela      │
│                              │
├──────────────────────────────┤
│ ● Cálculo II · 00:42:17   ⏸ ■│  ← barra do cronômetro (só quando rodando)
├──────────────────────────────┤
│ Hoje  Cursos  [▶]  Revisões  Mais │
└──────────────────────────────┘
```
- **Hoje**: meta do dia, sequência, revisões de hoje, prazos próximos, "continuar de onde parei", próximo do ciclo.
- **Cursos**: cursos → disciplinas → tópicos, com progresso.
- **[▶] Estudar**: escolhe o que estudar e abre o **modo foco** (cronômetro em tela cheia); toque longo → registro manual.
- **Revisões**: atrasadas, hoje, próximas.
- **Mais**: Agenda (prazos e notas), Estatísticas, Histórico de formação, Ciclo, Perfil, Configurações, Tema, Sair.

## 5. Etapas

| # | Etapa | Plano | Entrega |
|---|---|---|---|
| 0 | Ambiente e deploy | [00](etapas/00-ambiente-e-deploy.md) | "Hello Estudy" em produção com HTTPS e cron |
| 1 | Fundação | [01](etapas/01-fundacao.md) | Auth, app shell, tema, design system, PWA |
| 2 | Cursos, disciplinas e tópicos | [02](etapas/02-cursos-disciplinas-topicos.md) | Cadastros, colar lista, progresso |
| 3 | Cronômetro e sessões | [03](etapas/03-cronometro-e-sessoes.md) | Modo foco, Pomodoro, registro manual, histórico |
| 4 | Revisões espaçadas | [04](etapas/04-revisoes.md) | Agenda automática de revisões |
| 5 | Metas, Hoje e ciclo | [05](etapas/05-metas-hoje-e-ciclo.md) | Tela Hoje, constância, ciclo opcional |
| 6 | Agenda: avaliações, entregas e notas | [06](etapas/06-agenda-avaliacoes-notas.md) | Prazos e médias por disciplina |
| 7 | Estatísticas e histórico de formação | [07](etapas/07-estatisticas-e-formacao.md) | Gráficos SVG, horas, certificados |
| 8 | Notificações push | [08](etapas/08-notificacoes.md) | Lembretes diários, revisões e prazos |
| 9 | Exportação, polimento e go-live | [09](etapas/09-exportacao-polimento-go-live.md) | Versão 1.0 |

Cada etapa termina com testes, Larastan e Pint sem erros, deploy (`scripts/release.sh`) e validação no celular.
Ordem pensada para uso real cedo: ao fim da etapa 3 o app já serve para cronometrar estudos.
