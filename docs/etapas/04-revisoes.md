# Etapa 4 — Revisões espaçadas

Tabela `reviews`. Regras em [especificacao.md §3.4](../especificacao.md).

> **Status (17/11/2026):** implementada. "Pular" move a mesma revisão para o próximo dia de estudo (não cria outra linha).
> A integração com sessões fica em `SessionService::record()` (chama `ReviewService`). O menu (barra inferior/lateral) deixou de usar
> `@persist` para o contador de revisões ficar sempre atualizado.

## 4.1 Agendamento automático
- Listener de `SessionRecorded` → `ReviewService::scheduleAfterStudy()` (só com tópico, `kind ≠ review`, sem revisão pendente).
- `ReviewService::nextStudyDay()` aplica `study_days`.
- Configurações → **Revisões**: intervalos (chips editáveis; presets "1-7-30", "1-3-7-15-30", "2-7-21-60"), máximo por dia ao reagendar, ligar/desligar revisão automática.

## 4.2 Tela Revisões (`/revisoes`)
- Seções: **Atrasadas** (cor `--late`, com "há 3 dias") · **Hoje** · **Próximos 7 dias** · **Futuras** (recolhida).
- Item: cor da disciplina, tópico, "Revisão 2 de 3", última avaliação.
- Ações por item: **Revisar agora** (abre cronômetro com `kind = review`), **Marcar feita** (pede avaliação: 😀 Lembrei bem · 😐 Com dificuldade · 😵 Esqueci), **Pular**, **Mudar data**.
- Ação em lote: **Reagendar atrasadas** (espalha a partir de hoje, N/dia).
- Contador no ícone de Revisões da bottom nav (vencidas + hoje).

## 4.3 Integração com sessões
- Encerrar uma sessão `kind = review` de um tópico com revisão pendente → a folha "Como foi?" inclui a avaliação e conclui a revisão (`session_id` preenchido).
- Disciplina e tópico mostram as revisões (próxima data, passo, histórico de avaliações).
- "Esqueci" sugere marcar o tópico como difícil; o mapa de dificuldade (etapa 7) usa isso.

## 4.4 Revisão manual
- No tópico: "Agendar revisão" (data) ou "Revisar agora" — cria/antecipa a revisão pendente (respeitando 1 pendente por tópico).

## Testes
Criação só na primeira sessão; não duplica; good/hard/forgot; fim da cadeia; dia fora de `study_days`; pular; reagendar atrasadas com limite diário e ordem; mudança de intervalos só afeta futuros; sessão de revisão conclui a pendente.

## Critério de pronto
Estudo um tópico hoje, amanhã ele aparece em Revisões; avalio "lembrei bem" e ele volta em 7 dias; nada cai no domingo se eu não estudo aos domingos.
