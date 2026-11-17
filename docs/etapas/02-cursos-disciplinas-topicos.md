# Etapa 2 — Cursos, disciplinas/módulos e tópicos

Tabelas: `courses`, `subjects`, `topics` (colunas em [especificacao.md §2](../especificacao.md)).

> **Status (17/11/2026):** implementada. Ajuste: em vez de arrastar, a ordem muda no modo **Organizar** (setas ↑↓) — arrastar no
> celular exigiria SortableJS (~45 kB), contra a meta de app leve. Formulários de curso, disciplina e "colar lista" são
> componentes Livewire únicos (`CourseForm`, `SubjectForm`, `PasteList`) carregados no layout e abertos por eventos.

## 2.0 Pendências vindas da Etapa 1
- Trazer `app/Models/Concerns/BelongsToUser.php` do Financee.
- Onboarding: passo "O que você estuda?" (tipos + nomes → cria cursos vazios).
- Trocar os estados vazios "em breve" da Hoje/Cursos pelos reais.

## 2.1 Cursos (`/cursos`)
- Abas: **Em andamento** (`active`), **Planejados**, **Pausados**, **Concluídos/Abandonados**.
- Card: faixa de cor, emoji, nome, tipo + instituição, barra de progresso, "X h esta semana / meta".
- Formulário (sheet): nome, tipo (lista com ícones), instituição/plataforma, cor, emoji, status, datas (início, previsão, conclusão), carga horária, meta semanal, link, notas.
- Mudar status para `completed` pede a data de conclusão (alimenta o histórico de formação).
- Reordenar por arrastar (`Reorder` do Financee, adaptado).

## 2.2 Curso (`/cursos/{id}`)
- Cabeçalho: progresso, horas totais, horas na semana, carga horária (se houver), botão **Estudar** (abre o cronômetro já no curso).
- Disciplinas/módulos (rótulo pelo tipo do curso), agrupadas por `term` quando preenchido ("2026.2"), com progresso e cor.
- Ações: nova disciplina, **colar lista** (cria disciplinas + tópicos, ver especificação §5), arquivar, excluir (com contagem de sessões/horas).

## 2.3 Disciplina (`/disciplinas/{id}`)
- Lista de tópicos com checkbox (concluir), chip de status (`todo/doing/done`), dificuldade (● fácil, ●● médio, ●●● difícil), estimativa.
- Arrastar para ordenar; adicionar rápido (campo no fim da lista, Enter cria e mantém o foco); **colar lista**.
- Tópico (sheet): título, status, dificuldade, estimativa, link do material, notas (textarea com Markdown básico renderizado: negrito, listas, links).
- Filtro: todos / pendentes / concluídos / difíceis.
- Seções "Sessões recentes" e "Revisões" ficam como placeholders até as etapas 3 e 4.

## 2.4 Busca global
- Ícone ⌕ na app bar: busca cursos, disciplinas e tópicos (LIKE com índice; resultados agrupados). Útil para iniciar estudo rápido.

## 2.5 Services
- `ProgressService`: progresso de disciplina e curso (regra §3.1), em uma consulta agregada por curso (`withCount`/`withSum`), sem N+1.
- `ListImporter`: parse da colagem (marcadores, recuo, `:`/CAIXA ALTA → disciplina, estimativas) → DTO de pré-visualização → gravação em transação.

## Testes
CRUD e isolamento; progresso com/sem estimativas; arquivar × excluir; importador (casos: marcadores, numeração, recuo com tabs/espaços, estimativas "1h30", "(45min)", linhas vazias, só tópicos, disciplinas + tópicos); reordenação.

## Critério de pronto
Consigo cadastrar uma graduação com 5 disciplinas do semestre e um curso da Alura colando o sumário, e ver o progresso ao marcar tópicos.
