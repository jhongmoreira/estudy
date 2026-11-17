# Estudy — Especificação funcional

Convenções: durações em **segundos (int)**; datas `AAAA-MM-DD` (`DateOnly`); timestamps em UTC;
"dia" sempre no fuso `America/Sao_Paulo`. Toda tabela de domínio tem `user_id` (FK, cascade) e usa `BelongsToUser`.

## 1. Glossário (termos usados nas telas)
| Termo | Significado |
|---|---|
| **Curso** | Qualquer coisa que se estuda: "Graduação em ADS", "Pós em Ciência de Dados", "Inglês", "Aprender Rust", "Livro: SICP" |
| **Disciplina / Módulo** | Divisão do curso. O rótulo muda pelo tipo: graduação/pós → "Disciplina"; demais → "Módulo" |
| **Tópico** | Menor unidade: aula, capítulo, assunto |
| **Sessão** | Um período de estudo registrado (pelo cronômetro ou manual) |
| **Revisão** | Retomada agendada de um tópico já estudado |
| **Constância** | Dias seguidos atingindo a meta mínima diária |
| **Ciclo** | Sequência rotativa de disciplinas com tempo-alvo por bloco (opcional) |
| **Agenda** | Avaliações, trabalhos e entregas com data |

## 2. Modelo de dados

### users
`name`, `email`, `password`, `settings` (JSON):
| Chave | Padrão | Uso |
|---|---|---|
| `theme` | `system` | `system|light|dark` |
| `study_days` | `[1,2,3,4,5,6]` | dias da semana em que estuda (0=dom); afeta revisões e constância |
| `daily_goal_min` | `60` | meta diária em minutos (constância conta dias ≥ meta) |
| `streak_min` | `15` | mínimo para o dia "contar" na sequência (se menor que a meta) |
| `review_intervals` | `[1,7,30]` | dias entre revisões (editável, até 6 passos) |
| `pomodoro` | `{focus:25, short:5, long:15, every:4, enabled:false}` | |
| `week_starts_on` | `1` | segunda |
| `reminder_time` | `null` | horário do lembrete diário (push) |

### courses
| Coluna | Tipo | Notas |
|---|---|---|
| `name` | string(120) | |
| `type` | enum | `self` (autodidata), `free` (curso livre/online), `extension`, `undergrad`, `postgrad` (especialização/MBA), `masters`, `doctorate`, `language`, `certification`, `other` |
| `institution` | string null | escola, plataforma (Alura, Coursera...) ou autor |
| `color` | string(7) | paleta fixa de 12 cores (design.md) |
| `emoji` | string(8) null | ícone opcional |
| `status` | enum | `planned`, `active`, `paused`, `completed`, `dropped` |
| `started_on`, `target_on`, `completed_on` | date null | |
| `workload_hours` | int null | carga horária oficial (certificado) |
| `weekly_goal_min` | int null | meta semanal do curso |
| `url`, `notes` | text null | |
| `position` | int | ordenação |
| Índices | `(user_id, status, position)` | |

### subjects (disciplinas/módulos)
`course_id` (FK cascade), `name`, `color` (null = herda do curso), `term` (string null, ex.: "2026.2" — graduação/pós),
`status` (`active|completed|archived`), `weekly_goal_min` null, `passing_grade` decimal(4,2) null (média mínima),
`position`, `notes`. Índice `(course_id, position)`.

### topics
`subject_id` (FK cascade), `title` (string 200), `status` (`todo|doing|done`), `difficulty` (tinyint null: 1 fácil, 2 médio, 3 difícil),
`estimated_min` int null, `resource_url` null, `notes` text null, `position`, `done_on` date null.
Índice `(subject_id, position)`, `(user_id, status)`.

### active_timers (1 por usuário)
`user_id` **unique**, `subject_id`, `topic_id` null, `kind`, `started_at` (timestamp), `paused_at` null, `paused_seconds` int,
`pomodoro_phase` null, `phase_started_at` null.

### study_sessions
| Coluna | Tipo | Notas |
|---|---|---|
| `course_id`, `subject_id` | FK | `course_id` desnormalizado para estatísticas rápidas |
| `topic_id` | FK null (set null) | |
| `date` | date | dia (fuso local) de **início** da sessão |
| `started_at` | timestamp null | null em lançamentos manuais sem horário |
| `duration_seconds` | int | tempo **líquido** (sem pausas), 60 s – 12 h |
| `kind` | enum | `theory` (teoria/leitura), `video` (videoaula), `class` (aula ao vivo/presencial), `exercises`, `review`, `practice` (projeto/prática), `writing` (trabalho/TCC) |
| `pages` | int null | |
| `questions_total`, `questions_correct` | int null | correct ≤ total |
| `source` | enum | `timer`, `manual` |
| `notes` | text null | |
| Índices | `(user_id, date)`, `(user_id, subject_id, date)` | |

### reviews
`topic_id` (FK cascade), `subject_id` (desnormalizado), `origin_session_id` null, `step` (1..n), `due_on` date,
`status` (`pending|done|skipped`), `rating` null (`good|hard|forgot`), `done_on` null, `session_id` null (sessão da revisão).
Índice `(user_id, status, due_on)`. **Regra: no máximo 1 revisão `pending` por tópico.**

### study_cycles / cycle_items (etapa 5)
`study_cycles`: `name`, `is_active` (no máximo 1 ativo), `round` int, `round_started_at`.
`cycle_items`: `cycle_id`, `subject_id`, `target_min`, `position`, `done_seconds` (acumulado na volta atual).

### agenda_items (etapa 6)
`course_id`, `subject_id` null, `title`, `type` (`exam`, `assignment`, `presentation`, `quiz`, `project`, `other`),
`due_on` date, `due_time` null, `status` (`open|done`), `grade` decimal(5,2) null, `max_grade` decimal(5,2) null, `weight` decimal(5,2) default 1, `notes`.

### certificates (etapa 7)
`course_id` unique, `file_path` (storage privado), `original_name`, `mime`, `size`, `issued_on` null, `hours` int null.

### push_subscriptions, notifications, jobs, cache, sessions — infraestrutura (igual ao Financee).

## 3. Regras de negócio

### 3.1 Progresso
- Tópico conta como concluído quando `status = done` (o usuário marca, inclusive ao encerrar uma sessão).
- **Progresso da disciplina** = Σ `estimated_min` dos concluídos ÷ Σ `estimated_min` de todos, se **todos** tiverem estimativa; senão, concluídos ÷ total.
- **Progresso do curso** = média das disciplinas ponderada pelo nº de tópicos. Sem tópicos → mostra só horas estudadas.
- Iniciar uma sessão num tópico `todo` muda para `doing`.
- Curso com `workload_hours` mostra também "horas estudadas / carga horária" (informativo).

### 3.2 Cronômetro
- Um cronômetro por usuário, guardado em `active_timers`, para sobreviver a recarregar a página, fechar o app e trocar de aparelho.
- Tempo decorrido = `agora − started_at − paused_seconds − (pausado ? agora − paused_at : 0)`. O cliente só desenha; o servidor é a fonte da verdade.
- Pausar/retomar/encerrar são ações Livewire. Sem rede, o relógio local continua e a ação é reenviada ao voltar.
- **Encerrar** abre a folha "Como foi?": duração (editável), tipo, tópico, páginas, questões, notas, "marcar tópico como concluído". Salvar cria a `study_session`, remove o timer e dispara as regras de revisão.
- Sessões < 60 s são descartadas (pergunta antes). Se o timer passou de **4 h** sem interação, a folha abre com aviso "Esqueceu o cronômetro ligado?" e sugere a duração até a última pausa/interação. Máximo de 12 h.
- Pomodoro (opcional): fases foco/pausa conforme `settings.pomodoro`; o tempo de pausa **não** conta; aviso por vibração + Notification API local + som curto.
- Sessão que cruza a meia-noite fica no dia em que começou.

### 3.3 Registro manual
- Mesmo formulário da folha "Como foi?", com data (padrão hoje, permite passado, nunca futuro) e duração (atalhos 15/30/45/60/90 min).
- Sessões podem ser editadas e excluídas; excluir não apaga revisões já feitas.

### 3.4 Revisões espaçadas
- **Criação:** ao salvar uma sessão com tópico e `kind ≠ review`, se o tópico **não** tem revisão pendente, cria `step 1` com `due_on = data da sessão + intervals[0]`. Se já tem, nada muda (resolve o problema de dividir um assunto em vários estudos).
- **Concluir uma revisão** (tela Revisões ou sessão `kind = review` naquele tópico) pede a avaliação:
  - `good` (lembrei bem) → cria o próximo passo `step+1` com `intervals[step]`; após o último passo, a cadeia termina (tópico "consolidado").
  - `hard` (com dificuldade) → repete o **mesmo** intervalo do passo atual.
  - `forgot` (esqueci) → volta ao `step 1`; sugere marcar a dificuldade do tópico como "difícil".
- **Dias de estudo:** se `due_on` cair em dia fora de `study_days`, avança para o próximo dia de estudo.
- **Atrasadas:** continuam na lista, em vermelho. Ação "Reagendar atrasadas" espalha-as a partir de hoje, no máximo N por dia (padrão 5), mantendo a ordem de vencimento.
- **Pular:** marca `skipped` e reagenda o mesmo passo para o próximo dia de estudo.
- Revisão manual: o usuário pode criar ou antecipar uma revisão de qualquer tópico.
- Alterar `review_intervals` vale para os próximos agendamentos.

### 3.5 Metas e constância
- **Meta diária** (`daily_goal_min`) e **meta semanal** por curso/disciplina (opcional).
- **Dia válido para a sequência:** soma do dia ≥ `streak_min`. Dias fora de `study_days` **não quebram** a sequência (e contam se houver estudo).
- Sequência atual e recorde calculados a partir das somas diárias (consulta agregada + cache por usuário invalidado ao salvar sessão).
- **Semana**: começa em `week_starts_on`. "Faltam X h para a meta de <curso>".

### 3.6 "O que estudar agora" (tela Hoje e botão Estudar)
Ordem das sugestões (máx. 5):
1. Revisões vencidas/de hoje (agrupadas por disciplina).
2. Se houver **ciclo ativo**: o item atual do ciclo e quanto falta no bloco.
3. Itens da agenda nos próximos 3 dias → sua disciplina.
4. Disciplinas de cursos `active` com maior **déficit** em relação à meta semanal (proporcional ao dia da semana).
5. "Continuar": último tópico `doing` estudado.
Cada sugestão abre o cronômetro já com disciplina/tópico preenchidos.

### 3.7 Ciclo de estudos (opcional)
- O usuário monta uma lista ordenada de disciplinas com minutos-alvo (pode repetir a disciplina).
- Sessões de uma disciplina do ciclo somam em `done_seconds` do **primeiro item incompleto** dessa disciplina na volta atual.
- Item atual = primeiro com `done_seconds < target`. Quando todos completam: `round++` e zera os acumulados.
- Pode pular um item (marca como completo na volta). Não há data: se faltar um dia, continua de onde parou.

### 3.8 Agenda e notas
- Itens com data; os vencidos em aberto ficam destacados. Marcar como feito; lançar nota.
- **Média da disciplina** = Σ(nota/máx × 10 × peso) ÷ Σ pesos dos itens com nota; compara com `passing_grade`.

### 3.9 Histórico de formação
- Cursos `completed` com instituição, período, carga horária (`workload_hours` ou certificado) e horas efetivamente registradas.
- Total por tipo (útil para horas complementares/Lattes). Upload do certificado (PDF/JPG/PNG, até 5 MB) em storage **privado**, baixado por rota autenticada.

### 3.10 Exclusões
- Excluir curso/disciplina pede confirmação e informa quantas sessões e horas serão apagadas. Alternativa sugerida: **arquivar** (status `completed`/`dropped`/`archived`), que mantém as estatísticas.

## 4. Telas

| Tela | Rota | Conteúdo |
|---|---|---|
| Hoje | `/` | Saudação; anel da meta do dia; sequência 🔥; sugestões (3.6); revisões de hoje; agenda 7 dias; mini-heatmap de 8 semanas |
| Cursos | `/cursos` | Abas Em andamento / Planejados / Concluídos; card com cor, tipo, progresso, horas da semana × meta |
| Curso | `/cursos/{id}` | Cabeçalho (progresso, horas, carga horária); disciplinas (agrupadas por `term` se houver); agenda do curso; ações |
| Disciplina | `/disciplinas/{id}` | Tópicos (arrastar para ordenar, marcar concluído, dificuldade), "Colar lista", sessões recentes, revisões, notas |
| Estudar | `/estudar` | Seleção rápida (sugestões + busca de disciplina/tópico) → modo foco |
| Modo foco | `/estudar/foco` | Anel grande com tempo líquido, disciplina/tópico, pausar, encerrar, Pomodoro; tela sempre ligada (Wake Lock API) |
| Sessões | `/sessoes` | Histórico por dia com filtros (curso, disciplina, tipo, período); total do filtro |
| Revisões | `/revisoes` | Atrasadas · Hoje · Próximos 7 dias · Futuras; ações concluir (com avaliação), pular, reagendar atrasadas |
| Agenda | `/agenda` | Lista por data e visão de mês; notas e médias por disciplina |
| Estatísticas | `/estatisticas` | Ver etapa 7 |
| Formação | `/formacao` | Histórico de formação e certificados |
| Ciclo | `/ciclo` | Montagem e acompanhamento da volta |
| Perfil/Config. | `/perfil`, `/configuracoes` | Dados, senha, exclusão de conta; metas, dias de estudo, revisões, Pomodoro, lembretes, tema; exportação |

## 5. Formato do "Colar lista"
- Uma linha = um tópico; linhas vazias ignoradas; marcadores (`-`, `*`, `•`, `1.`, `1)`, `[ ]`) removidos.
- Linha terminando com `:` ou em CAIXA ALTA **e** seguida de linhas recuadas → vira **nova disciplina** (só no colar dentro do curso).
- `(45min)`, `(1h30)` ou `— 45 min` no fim da linha → `estimated_min`.
- Pré-visualização antes de salvar, com contagem ("12 tópicos em 3 módulos").
