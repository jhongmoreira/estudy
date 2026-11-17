# Etapa 5 — Metas, constância, tela Hoje e ciclo de estudos

Regras em [especificacao.md §3.5–3.7](../especificacao.md).

## 5.1 Metas
- Meta diária (configurações) e metas semanais por curso e por disciplina (formulários da etapa 2 ganham o campo).
- `GoalService`: minutos de hoje, da semana por curso/disciplina, déficit proporcional ("deveria ter 2h40 até quarta, tem 1h50").

## 5.2 Constância
- `StreakService`: sequência atual e recorde (dias ≥ `streak_min`; dias fora de `study_days` não quebram). Uma consulta `GROUP BY date` do último ano + cache por usuário, invalidado em `SessionRecorded`/edição/exclusão.
- Componente **heatmap** SVG (servidor): 8 semanas na Hoje, 1 ano nas Estatísticas; legenda de intensidade (0, <meta/2, <meta, ≥meta).

## 5.3 Tela Hoje (`/`)
1. Saudação + data; **anel da meta do dia** (vira marca-texto ao bater) e 🔥 sequência.
2. **Sugestões "Estudar agora"** (§3.6) — cada uma com botão ▶ que inicia o cronômetro já configurado.
3. **Revisões de hoje** (até 5 + "ver todas").
4. **Agenda** dos próximos 7 dias (a partir da etapa 6).
5. **Semana**: barras por curso (realizado × meta) e mini-heatmap.
- Estado vazio guiado: sem cursos → "Crie seu primeiro curso"; sem sessões → "Comece um estudo de 25 minutos".

## 5.4 Ciclo de estudos (opcional, `/ciclo`)
- Criar ciclo: adicionar disciplinas com minutos-alvo (pode repetir), arrastar para ordenar; assistente "distribuir X horas por semana" proporcional a pesos (1–5) que o usuário informa.
- Acompanhamento: volta atual, item atual destacado com "faltam 35 min", lista com barras de cada item; pular item; reiniciar volta.
- Listener `SessionRecorded` → `CycleService::apply()` (regra §3.7). Com ciclo ativo, o item atual vira a 2ª sugestão da Hoje e aparece no topo do `/estudar`.
- Um ciclo ativo por vez; desativar não apaga.

## Testes
Somas diárias/semanais e fuso; déficit proporcional; sequência com dias de folga, recorde, cache invalidado; ciclo: acumula no primeiro item incompleto da disciplina, fecha a volta, pular, sessões de disciplinas fora do ciclo não contam.

## Critério de pronto
Abro o app de manhã e a Hoje me diz o que estudar (revisões + disciplina atrasada na meta), com um toque o cronômetro começa; a sequência aparece correta.
