# Etapa 10 — Relatórios em PDF

> **Status (17/11/2026):** implementada (pedido do usuário depois da v1.0).

Gerados no servidor com `barryvdh/laravel-dompdf` (o mesmo do Financee, já validado na Hostoo) — única exceção à regra
"sem dompdf" do plano original; o peso fica só no servidor (nada vai para o navegador).

| Relatório | Rota | Conteúdo |
|---|---|---|
| Estudos do período | `/relatorios/estudos.pdf?de=&ate=&curso=&sessoes=1` | Resumo (tempo, dias, média, sequência), por curso, por tipo, período do dia, disciplinas, semana a semana, exercícios, leitura e revisões; opcional: lista de sessões (até 1.500) |
| Boletim de notas | `/relatorios/boletim/{curso}.pdf` | Média ponderada, mínima, quanto falta e situação por disciplina; avaliações de cada uma |
| Progresso do curso | `/relatorios/curso/{curso}.pdf` | Progresso, tópicos concluídos, tempo por disciplina, próximos prazos e todos os tópicos (status, dificuldade, estimativa, próxima revisão) |
| Histórico de formação | `/relatorios/formacao.pdf?andamento=1` | Cursos concluídos (e opcionalmente em andamento), carga horária, horas por tipo e ano |

- Tela **Mais → Relatórios em PDF** (`/relatorios`), com atalhos de período (semana, mês, mês passado, 30 dias, semestre, ano).
- Atalhos também no curso (Progresso / Boletim), na Formação e nas Estatísticas (PDF do período filtrado).
- Dados em `App\Services\ReportService`; modelos em `resources/views/reports/` (layout com tabelas — o dompdf não suporta flex/grid).
- Isolamento: os cursos são buscados pelo escopo do usuário (curso de outra pessoa → 404).
