# Etapa 1 — Fundação: auth, app shell, design system, tema e PWA

## 1.1 Autenticação (Fortify headless, telas próprias)
- Cadastro, login ("lembrar de mim"), logout, esqueci a senha (SMTP), troca de senha.
- `REGISTRATION_ENABLED` no `.env`; rate limit 5/min por e-mail+IP.
- Listener `Registered` → `DefaultUserData`: settings padrão e um **curso de exemplo opcional** ("Meu primeiro curso", tipo autodidata, 1 módulo, 3 tópicos) oferecido no onboarding (não criado à força).

## 1.2 Onboarding (3 passos, pulável)
1. "O que você estuda?" — escolhe tipos (graduação, curso livre, autodidata...) → cria cursos vazios com o nome digitado.
2. Meta diária (30/60/90/120 min ou personalizada) e dias de estudo.
3. Ativar lembretes (pede permissão de push só aqui, com explicação).

## 1.3 App shell
- `layouts/app.blade.php`: app bar (título, busca, sino, avatar com iniciais), bottom nav **Hoje · Cursos · ▶ Estudar · Revisões · Mais**, sidebar no `lg:` com `max-w-4xl`.
- `@persist('timer-bar')`: espaço reservado para a barra do cronômetro (etapa 3).
- Navegação `wire:navigate`; `safe-area-inset` (iPhone).
- Componentes Blade (`resources/views/components/ui/`): `sheet` (bottom sheet com arrastar), `modal`, `card`, `subject-stripe`, `progress` (barra), `ring` (SVG), `duration` (formata 1h05 / 00:42:17), `duration-input` (h:min com atalhos), `color-picker` (paleta de 12), `segmented`, `empty`, `toast`, `icon`, `skeleton`, `confirm`.
- `<x-icon name="...">`: lê `resources/icons/sprite.svg` (~35 ícones Lucide, inline via `<use>`), sem pacote de ícones.

## 1.4 Design system e tema
- Tokens de `design.md` em `resources/css/app.css` (`:root` e `.dark`) + `@theme inline` (`bg-paper`, `bg-sheet`, `text-ink`, `text-pen`, `bg-marker`...).
- `@custom-variant dark (&:where(.dark, .dark *))`; script inline no `<head>` aplica o tema antes da pintura (sem flash); `meta theme-color` dinâmica.
- Página `/_ui` (só local) com todos os componentes nos dois temas, para conferir no navegador.

## 1.5 PWA (mesma abordagem do Financee)
- `public/manifest.webmanifest`: `name: Estudy`, `display: standalone`, `start_url: /`, ícones 192/512 + maskable, `theme_color #3047c9`, `lang pt-BR`, atalhos **"Estudar agora"** (`/estudar`), **"Revisões de hoje"** (`/revisoes`), **"Registrar estudo"**.
- `resources/js/sw.js` (copiado e adaptado): pré-cache do shell (lista a partir do `manifest.json` do Vite), assets cache-first, navegação network-first com fallback `/offline`, nada de cache de dados, versão = `RELEASE`, handlers de `push`/`notificationclick` (usados na etapa 8). Registrado só em produção.
- Banner "Instalar app" (Android `beforeinstallprompt`; instruções no iOS), `apple-touch-icon`, splash iOS (`scripts/splash.mjs`), ícones (`scripts/icons.mjs` a partir de `resources/icons/logo.svg`).
- Página `/offline` no visual novo ("Sem conexão — seu cronômetro continua contando").

## 1.6 Perfil e configurações
- Perfil: nome, e-mail, senha, excluir conta (confirmação por senha, apaga tudo).
- Configurações (JSON `settings`): tema, meta diária, dias de estudo, início da semana. (Revisões, Pomodoro e lembretes entram nas etapas 3, 4 e 8.)

## 1.7 Infraestrutura de domínio
- `BelongsToUser`, `DateOnly` (copiados).
- `App\Support\Duration` (segundos ↔ "1h05", "00:42:17", parse de "1h30"/"90"/"1:30").
- `App\Support\Week` (início/fim da semana conforme configuração) e `App\Support\StudyDays`.
- `App\Support\Palette` (12 cores e cor de texto legível sobre cada uma).

## Testes
Auth (cadastro aberto/fechado, login, reset), onboarding, settings, exclusão de conta, isolamento entre usuários, `Duration`, `Week`.

## Critério de pronto
App instalável no Android e iOS, login, onboarding, tema escuro sem flash, shell navegando instantaneamente entre telas vazias; Lighthouse PWA instalável e performance ≥ 90 no mobile.
