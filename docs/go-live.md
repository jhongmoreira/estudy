# Estudy — Go-live e operação

## Situação (17/11/2026)
Etapas 0 a 9 implementadas e publicadas em https://estudy.jgmoreira.com.br (versão 1.0).
Testes automatizados (Pest), Larastan nível 6 e Pint rodam a cada release.

## O que roda sozinho no servidor (cron a cada minuto)
| Quando | Comando | O quê |
|---|---|---|
| a cada minuto | `app:post-deploy` | Após um deploy: migrations + caches (cada passo em processo separado; migrations antes do `optimize:clear`) |
| a cada minuto | `queue:work --stop-when-empty` | Fila: envio das notificações push |
| a cada minuto | `timers:pomodoro` | Pomodoro com o app fechado: pausa no fim exato do foco e manda push (fim do foco e fim da pausa) |
| a cada 15 min | `notifications:dispatch` | Lembrete diário, revisões (8h), prazos (8h/19h), sequência (21h) — cada aviso 1x/dia |
| dom 04:00 | `app:db-maintenance` | `PRAGMA optimize` no SQLite |
| dia 1º 04:30 | `app:db-maintenance --vacuum` | Compacta o arquivo do SQLite |

Cron cadastrado no cPanel (a linha do Financee continua separada):
```
* * * * * /usr/local/bin/ea-php84 /home/mv8f1v6j/public_html/estudy/artisan schedule:run >> /dev/null 2>&1
```

## Servidor
| O quê | Onde |
|---|---|
| Código (integração Git, branch `production`) | `~/public_html/estudy` — **não editar arquivos ali** |
| `.env`, banco SQLite, storage, logs | `~/estudy-data/` |
| Certificados enviados | `~/estudy-data/storage/app/private/certificates/` |
| Chaves do push (geradas sozinhas) | `~/estudy-data/storage/app/private/vapid.json` — **não apague** (os aparelhos ativados deixariam de receber) |
| Logs | `~/estudy-data/storage/logs/laravel-AAAA-MM-DD.log` |
| PHP do terminal/cron | `/usr/local/bin/ea-php84` |

## E-mail (SMTP)
Reaproveita a conta `nao-responda@jgmoreira.com.br` do Financee (linhas `MAIL_*` copiadas para `~/estudy-data/.env`, `MAIL_FROM_NAME=Estudy`).
Teste em https://estudy.jgmoreira.com.br/forgot-password.

## Opcional
- **Fechar o cadastro** depois de criar as contas desejadas: `REGISTRATION_ENABLED=false` no `.env` + `/usr/local/bin/ea-php84 ~/public_html/estudy/artisan optimize`.
- **Backup**: o banco é `~/estudy-data/database.sqlite` (+ a pasta de certificados). Cada usuário também baixa tudo em JSON em Perfil → "Exportar backup completo" e pode restaurar em "Importar backup".

## Checklist de go-live
- [x] `.env` de produção (APP_DEBUG=false, LOG_STACK=daily, sessão de 30 dias)
- [x] Cron ativo (scheduler, fila, avisos) e post-deploy validado (inclusive com banco novo)
- [x] HTTPS forçado + HSTS + cabeçalhos de segurança (CSP, X-Frame-Options...)
- [x] `.env`, `vendor` e `storage` inacessíveis pela web (403/404 conferidos)
- [x] PWA: manifest, ícones, splash do iOS, service worker, tela offline
- [x] Páginas de erro em português (403, 404, 419, 429, 500, 503)
- [ ] Testar "Esqueci a senha" em produção (SMTP)
- [ ] Ativar push no celular (Android e iPhone com o app instalado) e usar "Enviar teste"
- [ ] Uso real por 1–2 semanas

## Cuidados
- **Não altere a versão do PHP pelo painel**: o cPanel reescreve `public/.htaccess` e trava o deploy ("arquivos alterados manualmente").
  Se acontecer: `cd ~/public_html/estudy && git status && git checkout -- public/.htaccess` e refaça o deploy.
- Logo após cada release, por até 1 minuto, telas novas podem dar 404 até o cron renovar o cache de rotas.
- Deploy: `scripts/release.sh` (testes + Larastan + build + PR para `production` com merge automático). A branch `release/*` é apagada no GitHub depois do merge (sem `--delete-branch`, que falha por causa do worktree temporário).
- As duas correções de deploy (ordem do post-deploy e remoção da branch `release/*`) também foram aplicadas no Financee em 17/11/2026.
