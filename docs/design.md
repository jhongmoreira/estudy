# Estudy — Identidade visual

Objetivo: visual **próprio de estudo** ("caderno + marca-texto"), claramente diferente do Financee
(que usa verde-esmeralda, cinzas neutros, FAB de lançamentos e foco em números).

## Conceito
- **Papel e tinta:** fundo levemente quente como papel, texto em "tinta" azul-escuro, destaques em **marca-texto**.
- **Cor por disciplina:** cada curso/disciplina tem sua cor; ela aparece como faixa lateral dos cards, ponto no cronômetro e segmento nos gráficos.
- **Tempo é o protagonista:** números do cronômetro grandes, com dígitos de largura fixa (`tabular-nums`).

## Tokens (CSS variables, Tailwind 4 `@theme inline`)
| Token | Claro | Escuro | Uso |
|---|---|---|---|
| `--paper` | `#f7f4ee` | `#14161c` | fundo da página |
| `--sheet` | `#ffffff` | `#1c1f27` | cards, folhas |
| `--sheet-2` | `#f1ede4` | `#242833` | hover, campos |
| `--rule` | `#e4ded1` | `#2e3340` | bordas ("pauta do caderno") |
| `--ink` | `#1f2a44` | `#e8eaf0` | texto principal |
| `--ink-soft` | `#5b6478` | `#9aa3b5` | texto secundário |
| `--pen` | `#3047c9` | `#8ea0ff` | cor da marca (ações, links, foco) |
| `--pen-soft` | `#e3e8ff` | `#262e5c` | fundo de chips/seleção |
| `--marker` | `#ffe066` | `#c9a400` | marca-texto (destaques, sequência, meta batida) |
| `--late` | `#c2410c` | `#fb923c` | atrasado (revisões/prazos) |
| `--done` | `#2f855a` | `#68d391` | concluído |

Paleta de cursos (12, testadas em claro/escuro): `#3047c9` anil, `#7c3aed` violeta, `#c026d3` magenta, `#e11d48` framboesa,
`#ea580c` laranja, `#ca8a04` mostarda, `#65a30d` oliva, `#059669` jade, `#0891b2` petróleo, `#0284c7` céu, `#64748b` grafite, `#a16207` café.

## Tipografia (sem fonte externa = leve)
- Títulos: `ui-rounded, "SF Pro Rounded", "Nunito", system-ui` (arredondada no Apple, sistema nos demais).
- Texto: `system-ui` padrão. Números: `font-variant-numeric: tabular-nums`.
- Título de seção com sublinhado de **marca-texto** (`background: linear-gradient(transparent 60%, var(--marker) 60%)`).

## Componentes característicos
- **Card de disciplina:** faixa de 4px na cor à esquerda, nome, barra de progresso fina, "3h10 / 5h esta semana".
- **Anel de meta** (SVG): meta do dia na Hoje; vira marca-texto quando batida.
- **Heatmap de constância** (grade de quadrados, como calendário de caderno); intensidade pela cor `--pen`.
- **Barra do cronômetro** persistente acima da bottom nav (`@persist`), com ponto pulsando na cor da disciplina.
- **Modo foco:** tela limpa, fundo `--paper`, anel grande; no escuro fica quase preto para estudar à noite.
- **Botão central "Estudar"** (▶) na bottom nav, círculo `--pen`; toque longo → "Registrar manualmente".
- **Folhas (bottom sheets)** com "pauta" sutil no topo (linha dupla `--rule`).
- Estados vazios com ilustração em traço simples (SVG inline, 1 cor) e um próximo passo claro ("Crie seu primeiro curso").

## Ícone do app
Livro aberto estilizado com um traço de marca-texto amarelo sobre fundo anil (`#3047c9`), versões normal e maskable.

## Acessibilidade
Contraste AA em todos os pares texto/fundo; alvos de toque ≥ 44 px; `prefers-reduced-motion` desliga pulsos e animações;
cor nunca é o único indicador (atrasado também tem ícone/rótulo).
