# Estudy — Desenvolvimento em outro computador

## 1. O que instalar
| Ferramenta | Versão | Para quê |
|---|---|---|
| Git | recente | código |
| PHP | **8.4** com `pdo_sqlite`, `sqlite3`, `mbstring`, `intl`, `gd`, `gmp` (ou `bcmath`), `zip`, `curl`, `openssl`, `fileinfo`, `dom` | Laravel (gmp/openssl: chaves do push) |
| Composer | 2.x | dependências PHP |
| Node.js | 24 | Vite/Tailwind e scripts de ícones |
| GitHub CLI (`gh`) | 2.x | `scripts/release.sh` |

Linux/WSL/macOS sem `sudo`: `/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"` (instala em `~/.config/herd-lite/bin`).

## 2. Clonar e configurar
```bash
git clone https://github.com/jhongmoreira/estudy.git && cd estudy
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed     # cria demo@estudy.test / demo12345 com ~6 meses de dados
```

## 3. Rodar
```bash
composer run dev    # servidor (http://localhost:8000) + Vite + fila + logs
```
O service worker só é registrado em produção. Catálogo de componentes: `/_ui` (só local).

## 4. Qualidade (obrigatório antes de publicar)
```bash
php artisan test                                  # Pest
./vendor/bin/phpstan analyse --memory-limit=1G     # Larastan nível 6
./vendor/bin/pint                                  # formatação
```

## 5. Ícones e splash do PWA
Edite `resources/icons/logo.svg` / `logo-maskable.svg` e rode `node scripts/icons.mjs && node scripts/splash.mjs`.

## 6. Publicar
```bash
gh auth login          # uma vez
git push origin main
scripts/release.sh     # testes + Larastan + build + PR para production + merge automático
```
Operação do servidor: [go-live.md](go-live.md).
