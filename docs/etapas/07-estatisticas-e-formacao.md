# Etapa 7 — Estatísticas (gráficos em SVG) e histórico de formação

> **Status (17/11/2026):** implementada. Barras e rankings são HTML/CSS puro; rosca e calendário em SVG — nenhum JS de gráfico.
> Rendimento por período usa o horário das sessões (`started_at`). Certificados ficam em `storage/app/private/certificates/{user}`
> (disco `local`), baixados por `/formacao/certificado/{curso}`; o arquivo é apagado junto com o certificado ou o curso.

## 7.1 Gráficos sem biblioteca
Componentes Blade que geram SVG no servidor (`resources/views/components/chart/`), com tokens de cor, `<title>` para tooltip nativo e tabela acessível escondida:
- `chart.bars` (verticais, opcionalmente empilhadas por curso), `chart.hbars` (ranking), `chart.donut`, `chart.heatmap`, `chart.line` (simples, para tendência).
- Responsivos via `viewBox`; nenhuma dependência JS. Tooltip melhorado opcional com ~20 linhas de Alpine.

## 7.2 Estatísticas (`/estatisticas`)
Filtro de período (7 d, 30 d, 12 semanas, ano, tudo, personalizado) e de curso.
| Bloco | Conteúdo |
|---|---|
| Resumo | horas no período, média por dia de estudo, sessões, sequência/recorde, % da meta |
| Tempo | barras por dia (até 31 d) ou por semana (períodos maiores), empilhadas por curso |
| Distribuição | rosca por curso e barras por disciplina; por tipo de estudo (teoria, exercícios, revisão...) |
| Constância | heatmap de 1 ano |
| Rendimento por período | manhã / tarde / noite / madrugada (pelo `started_at`) e por dia da semana |
| Exercícios | questões feitas, % de acerto por disciplina (tendência) |
| Leitura | páginas no período, páginas por hora |
| Revisões | feitas, em dia × atrasadas, distribuição de avaliações (good/hard/forgot) |
| Mapa de dificuldade | por disciplina: tópicos fáceis/médios/difíceis × concluídos, destacando os "esqueci" |
- `StatsService`: consultas agregadas com `GROUP BY` (índices `(user_id, date)`); um método por bloco; resultado em cache curto (5 min) por usuário+filtro.

## 7.3 Histórico de formação (`/formacao`)
- Lista de cursos concluídos (e, em outra aba, em andamento) com tipo, instituição, período, carga horária oficial, horas registradas no app e certificado.
- Totais por tipo e por ano ("Cursos livres: 180 h em 2026") — útil para horas complementares e Lattes.
- **Certificado**: upload (PDF/JPG/PNG ≤ 5 MB, validação de MIME) em `storage/app/private/certificates/{user}`; download por rota autenticada com Policy; substituir/remover.
- Visão "Imprimir" (`@media print`, sem PDF no servidor) e exportação CSV.

## Testes
Agregações por período/fuso, empilhamento, período do dia, % de acerto, cache por filtro; geração dos SVG (snapshot simples do markup); upload: tipo inválido, tamanho, acesso de outro usuário (403); totais da formação.

## Critério de pronto
Vejo quantas horas estudei por semana em cada curso, em que horário rendo mais, meu % de acerto, e tenho a lista dos cursos concluídos com certificados para baixar.
