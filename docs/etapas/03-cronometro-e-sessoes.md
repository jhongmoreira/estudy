# Etapa 3 — Cronômetro, modo foco e sessões de estudo

Tabelas: `active_timers`, `study_sessions`. Regras em [especificacao.md §3.2–3.3](../especificacao.md).

> **Status (17/11/2026):** implementada. Notas: o Pomodoro é controlado pelo navegador (ao fim do foco ele chama `pause`;
> a pausa é tempo pausado, que não conta) — se o app estiver fechado na hora, o cronômetro segue contando até você voltar.
> Sem rede, o relógio continua (é calculado dos horários do servidor), mas pausar/encerrar exigem conexão (aviso "Sem conexão").
> Evento `App\Events\SessionRecorded` já é disparado para as etapas 4 e 5.
>
> **Atualização (17/11/2026, pedido do usuário — "sinal a gente nem vê"):** o Pomodoro agora também roda no servidor
> (`timers:pomodoro`, cron a cada minuto): pausa no horário exato em que o foco acabou e manda **push** no fim do foco e da pausa
> (toca o som do celular mesmo bloqueado; tag `estudy-timer` vibra e fica na tela). Colunas `pomodoro_block` e `break_alerted_at`
> evitam aviso duplicado quando o navegador já pausou/avisou. Com o app aberto, alarme de 3 notas (2x) + vibração; dá para desligar em
> Configurações → Pomodoro → "Tocar alarme". O modo foco sugere ativar as notificações quando não há push neste aparelho.

## 3.1 Iniciar (`/estudar`)
- Sheet/página de seleção: "Continuar" (última disciplina/tópico), disciplinas dos cursos ativos (com cor), busca; tópico opcional; tipo de estudo (teoria, videoaula, aula, exercícios, revisão, prática, escrita) — padrão = último usado na disciplina.
- Botão **Começar** → cria `active_timers` e abre o modo foco.
- Atalhos para iniciar a partir de: card da disciplina, tópico, sugestões da Hoje, atalho do PWA.

## 3.2 Modo foco (`/estudar/foco`)
- Anel SVG grande + tempo `HH:MM:SS` (Alpine `requestAnimationFrame`/`setInterval` 1 s, calculado a partir de `started_at`/`paused_seconds` vindos do servidor).
- Ações: **Pausar/Retomar**, **Encerrar**, trocar tópico, anotar (rascunho salvo junto do timer).
- **Wake Lock API** (tela ligada, se suportado); `document.title` mostra o tempo; ao voltar ao app o relógio se recalcula.
- **Pomodoro** (se ativo): fases foco/pausa curta/pausa longa; troca de fase com vibração, som curto e `Notification` local; durante a pausa o tempo líquido não avança; opção "pular pausa".
- Sem rede: relógio continua; ações ficam numa fila Alpine e são reenviadas (`wire:offline` mostra aviso discreto).

## 3.3 Barra persistente
- Quando há timer, a barra acima da bottom nav aparece em todas as telas (`@persist`): ponto na cor da disciplina, nome, tempo, ⏸ e ■. Toque → modo foco.
- Outro aparelho: ao abrir o app, o timer em andamento aparece (estado vem do servidor).

## 3.4 Encerrar → folha "Como foi?"
- Duração líquida (editável), tipo, tópico, páginas, questões (acertos/total, com %), notas, ☐ "Marcar tópico como concluído".
- Aviso de "esqueceu ligado?" após 4 h; descartar sessões < 1 min.
- Salvar → `SessionService::record()` (transação): cria sessão, remove timer, atualiza status do tópico, dispara evento `SessionRecorded` (revisões na etapa 4, ciclo/constância na etapa 5).

## 3.5 Registro manual
- Toque longo no ▶ ou botão "Registrar estudo": mesmo formulário + data (≤ hoje) e duração com atalhos (15/30/45/60/90 min) ou horário início–fim.

## 3.6 Histórico (`/sessoes`)
- Agrupado por dia, com total do dia; filtros (curso, disciplina, tipo, período); total do filtro.
- Editar/excluir sessão (sheet). Seções "Sessões recentes" na disciplina e no curso passam a funcionar.

## 3.7 Services
- `TimerService`: start (falha se já existe — oferece encerrar o atual), pause, resume, elapsed, stop → dados para a folha.
- `SessionService`: record, update, delete; validações (60 s–12 h, data ≤ hoje, acertos ≤ total, disciplina/tópico do mesmo usuário e coerentes).

## Testes
Cálculo do decorrido com pausas (Carbon `setTestNow`); timer único por usuário; Pomodoro não conta pausas; sessão que cruza meia-noite; 4 h de alerta; manual no passado/futuro; status do tópico; isolamento.

## Critério de pronto
Inicio o cronômetro no celular, fecho o app, abro no computador e o tempo está certo; encerro, preencho páginas e a sessão aparece no histórico.
