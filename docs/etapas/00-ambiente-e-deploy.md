# Etapa 0 — Ambiente local e pipeline de deploy

**Objetivo:** "Hello Estudy" no ar no **mesmo servidor do Financee**, com o mesmo fluxo de deploy, antes de qualquer código de negócio.

## 0.1 Ambiente local (já existe — WSL, sem Docker)
- PHP 8.4 + Composer do php.new em `~/.config/herd-lite/bin` (`export PATH="$HOME/.config/herd-lite/bin:$PATH"`).
- Node 24, `gh` autenticado, Chromium do Playwright em `~/.cache/ms-playwright` para conferir telas.

## 0.2 Projeto
- `laravel new estudy` (sem starter kit, Pest, SQLite) **dentro de** `/home/jgmoreira/estudy` (preservando `docs/`).
- `composer require livewire/livewire laravel/fortify laravel-notification-channels/webpush`
- `composer require --dev larastan/larastan laravel/pint lucascudo/laravel-pt-br-localization`
- `npm i -D tailwindcss @tailwindcss/vite sharp` (sem chart.js).
- Copiar do Financee: `config/database.php` (WAL, `busy_timeout=5000`, `synchronous=NORMAL`, FKs), `phpstan.neon` (nível 6), `.editorconfig`, `lang/pt_BR*`.
- `AppServiceProvider`: `Model::shouldBeStrict(!app()->isProduction())`, `Date::use(CarbonImmutable::class)`, locale `pt_BR`, timezone `America/Sao_Paulo`, `URL::forceScheme('https')` em produção.
- Git: `main`; repositório GitHub `estudy` (privado) na mesma conta do Financee.

## 0.3 Servidor (Hostoo — mesma conta do Financee)
```
/home/mv8f1v6j/
├─ financee-data/          (existente, não mexer)
├─ estudy-data/            ← NOVO: .env, database.sqlite, storage/ (fora do deploy e da web)
└─ public_html/
    ├─ financee/           (existente)
    └─ estudy/             ← NOVO: integração Git (branch production); .htaccess bloqueia tudo
        └─ public/         ← document root de estudy.jgmoreira.com.br
```
Passos (cPanel/SSH):
1. Criar o subdomínio **estudy.jgmoreira.com.br** com document root `public_html/estudy/public`.
2. Integração Git da Hostoo → repositório `estudy`, branch `production`, caminho `public_html/estudy`.
3. SSH: `mkdir -p ~/estudy-data/storage/{app/private,app/public,framework/{cache,sessions,views},logs}` e `touch ~/estudy-data/database.sqlite`.
4. Criar `~/estudy-data/.env` (APP_ENV=production, APP_DEBUG=false, LOG_STACK=daily, `DB_DATABASE=/home/mv8f1v6j/estudy-data/database.sqlite`, APP_URL https, `APP_KEY` gerado localmente, SMTP **reaproveitando** `nao-responda@jgmoreira.com.br` com `MAIL_FROM_NAME=Estudy`).
5. SSL (AutoSSL/Let's Encrypt) para o subdomínio.
6. **Segunda linha** no cron do cPanel (a do Financee continua):
   `* * * * * /usr/local/bin/ea-php84 /home/mv8f1v6j/public_html/estudy/artisan schedule:run >> /dev/null 2>&1`
- **Não** alterar a versão do PHP pelo painel (o cPanel reescreve `public/.htaccess` e trava o deploy); o handler `ea-php84` já vai no `.htaccess` versionado.

## 0.4 Arquivos copiados do Financee
| Arquivo | Ajuste |
|---|---|
| `bootstrap/app.php` (bloco da pasta de dados) | `financee-data` → `estudy-data` |
| `.htaccess` da raiz e `public/.htaccess` | idem (bloco cPanel de `error_log` mantido) |
| `scripts/release.sh` | sem mudanças |
| `app/Console/Commands/PostDeploy.php` + agendamentos (post-deploy, fila, manutenção SQLite) em `routes/console.php` | cada passo em processo PHP separado |
| `app/Http/Middleware/SecurityHeaders.php` | CSP `self` apenas |
| `.gitignore` (inclui `/public/.user.ini`, `/public/php.ini`) | idem |

## 0.5 Validação
- `/up` e página "Hello Estudy" com HTTPS.
- Checklist: [ ] subdomínio + SSL [ ] integração Git na `production` [ ] `~/estudy-data` com .env [ ] cron [ ] `release.sh` → no ar em ~2 min com migrations [ ] `https://estudy.jgmoreira.com.br/../.env` e `/vendor` → 403 [ ] Financee continua funcionando.

## Critério de pronto
Commit em `main` → `scripts/release.sh` → nova versão no ar com migrations aplicadas, sem ação manual e sem afetar o Financee.
