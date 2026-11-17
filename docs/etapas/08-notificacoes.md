# Etapa 8 — Notificações (push e central no app)

Reaproveita do Financee: `laravel-notification-channels/webpush`, `VapidKeys` (gera `storage/app/private/vapid.json` sozinho), handlers do `sw.js`, tabela `push_subscriptions`, fila `database` processada pelo cron.

> **Status (17/11/2026):** implementada. `notifications:dispatch` roda a cada 15 min; cada aviso sai dentro de 1 h após o horário,
> uma vez por dia (`notification_logs`). Revisões e prazos do dia às 8h, véspera às 19h, sequência às 21h, lembrete diário no horário
> escolhido (padrão 19h). Convite para ativar na tela Hoje (dispensável). Chaves VAPID geradas sozinhas em `storage/app/private/vapid.json`.

## 8.1 Avisos
| Aviso | Quando | Texto (exemplo) |
|---|---|---|
| Lembrete diário | `settings.reminder_time`, só em dias de estudo, só se ainda não bateu a meta | "Bora estudar? Hoje: 3 revisões e Cálculo II está atrasado na meta." |
| Revisões | junto do lembrete diário (ou 08:00 se não houver) | "4 revisões para hoje (1 atrasada)." |
| Agenda | véspera 19:00 e no dia 08:00 | "Amanhã: P2 de Estrutura de Dados." |
| Sequência em risco | 21:00, se tem sequência ≥ 3 e ainda não estudou o mínimo | "Sua sequência de 12 dias acaba hoje 🔥" |
| Pomodoro | local (Notification API do navegador, não passa pelo servidor) | "Pausa de 5 minutos." |
- Configurações → **Lembretes**: liga/desliga cada tipo, horário do diário, ativar neste aparelho (lista de aparelhos inscritos com "remover").
- Central no app (sino): últimos 30 avisos, marcar como lidos; `notificationclick` abre a tela certa (Revisões, Agenda, Hoje).

## 8.2 Agendamento (routes/console.php)
- `notifications:dispatch` **a cada 15 min**: para cada usuário com algum aviso no horário (janela de 15 min), monta e enfileira (`Auth::setUser` por usuário). Idempotente: registra `(user, tipo, data)` para não repetir.
- `reviews:roll` diário 03:20: nada se move sozinho (atrasadas continuam atrasadas), mas recalcula contadores em cache.

## Testes
Janela de horário e fuso; só dias de estudo; não envia se a meta já foi batida; idempotência; preferências desligadas; usuários sem inscrição recebem só na central.

## Critério de pronto
Com o app instalado no celular, recebo o lembrete no horário escolhido com o resumo do dia e, ao tocar, caio na tela de Revisões.
