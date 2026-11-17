# Etapa 6 — Agenda: avaliações, entregas e notas

Tabela `agenda_items`. Regras em [especificacao.md §3.8](../especificacao.md). Pensada para graduação, pós e cursos com prazos, mas útil para qualquer curso (ex.: "terminar módulo 3 até 30/11").

## 6.1 Agenda (`/agenda`)
- Lista por data (Atrasados · Esta semana · Próximos · Concluídos) e **visão de mês** (grade simples com pontos na cor da disciplina).
- Item (sheet): título, tipo (prova, trabalho, apresentação, quiz, projeto, outro), curso/disciplina, data e hora, peso, nota obtida / nota máxima, notas, feito.
- Atalho "Estudar para isso" → cronômetro na disciplina do item.

## 6.2 Notas e médias
- Na disciplina: lista de avaliações com nota, **média ponderada** (0–10) e situação em relação à `passing_grade` ("faltam 6,5 na P2 para média 7").
- No curso (por `term`): média de cada disciplina do semestre.
- `GradeService`: média ponderada, nota necessária na próxima avaliação para atingir a média mínima.

## 6.3 Integração
- Hoje: bloco "Agenda" (7 dias); itens em até 3 dias viram sugestão de estudo (§3.6).
- Curso e disciplina mostram seus itens.
- Push (etapa 8): aviso na véspera e no dia.

## Testes
Média ponderada com pesos e notas máximas diferentes; itens sem nota ignorados; nota necessária (incluindo "impossível" e "já aprovado"); ordenação e atrasados; isolamento.

## Critério de pronto
Cadastro as provas e trabalhos do semestre, lanço as notas e vejo a média de cada disciplina e quanto preciso tirar na próxima.
