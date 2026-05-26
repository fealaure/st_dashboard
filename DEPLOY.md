# Deploy — Save State Dashboard

Runbook real do deploy em HostGator (cPanel + SFTP, shell SSH **desabilitado**).

- **Frontend:** https://dashboard.savestate.com.br → Angular estático
- **API:** https://api.savestate.com.br → Laravel 11
- **Host:** HostGator US — IP `162.241.63.57`, user `savest57`, home `/home2/savest57`
- **DNS:** Cloudflare (proxy 🟠 ativo em ambos os subdomínios)

## Layout no servidor

A hospedagem **não permite apontar subdomínio direto pra subpasta `public/`**, então usamos o split tradicional do Laravel pra shared hosting:

```
/home2/savest57/
├── dashboard-api-app/          ← código completo do Laravel (FORA do public_html, não web-acessível)
│   ├── app/  bootstrap/  config/  database/  src/  storage/  vendor/
│   ├── .env                    ← criado direto no servidor, NUNCA commitado
│   └── …
└── public_html/
    ├── dashboard/              ← docroot do subdomínio dashboard
    │   ├── index.html .htaccess chunk-*.js main-*.js styles-*.css images/
    └── dashboard-api/          ← docroot do subdomínio api
        ├── index.php           ← patched: aponta pra ../../dashboard-api-app/
        ├── .htaccess  favicon.ico  robots.txt
```

O `dashboard-api/index.php` é uma versão patchada que troca os caminhos relativos do Laravel:

```php
$app_root = __DIR__ . '/../../dashboard-api-app';
require $app_root . '/vendor/autoload.php';
(require_once $app_root . '/bootstrap/app.php')->handleRequest(Request::capture());
```

## Pré-requisitos no cPanel (uma vez)

1. **Subdomínios** — em *Domains*:
   - `dashboard.savestate.com.br` → docroot `public_html/dashboard`
   - `api.savestate.com.br` → docroot `public_html/dashboard-api`
2. **DNS no Cloudflare** — adicionar dois registros A apontando pra `162.241.63.57`, proxy 🟠 ativo (Cloudflare entrega o SSL grátis, não precisa AutoSSL na HostGator).
3. **MySQL** — criar banco + user pelo *MySQL Databases* do cPanel; nomes saem prefixados (`savest57_<db>`, `savest57_<user>`).
4. **SSH key (opcional)** — necessária pra automatizar uploads via SFTP. Adicionar a public key em *SSH Access → Manage SSH Keys → Import Key → Authorize*. Mesmo com shell SSH desabilitado, SFTP funciona.

## Build local

```powershell
# Frontend
cd web
npm run build -- --configuration production
# saída: web\dist\web\browser\

# Backend — instala vendor sem dev deps
cd ..\api
composer install --no-dev --optimize-autoloader
```

## Primeiro deploy

### 1. Subir o frontend (SFTP, ~10 arquivos)

```bash
cd web/dist/web/browser
sftp -P 22 savest57@162.241.63.57 <<EOF
cd public_html/dashboard
put -r .
EOF
```

### 2. Empacotar o backend e subir como zip único

SFTP file-by-file pro `vendor/` (6k+ arquivos) é inviável. Subir como zip e extrair via cPanel File Manager.

Empacotar localmente com **`dashboard-api-app/` como pasta raiz** dentro do zip:

```powershell
# Em api/
$staging = "$env:TEMP/dashboard-api-app-staging"
Remove-Item -Recurse -Force $staging -ErrorAction Ignore
$inner = Join-Path $staging 'dashboard-api-app'
New-Item -ItemType Directory -Force -Path $inner | Out-Null
'app','bootstrap','config','database','public','resources','routes','src','storage','vendor','artisan','composer.json','composer.lock' |
  ForEach-Object { Copy-Item -Recurse -Force $_ (Join-Path $inner $_) }
Compress-Archive -Path $inner -DestinationPath '..\api.zip' -Force
Remove-Item -Recurse -Force $staging
```

Subir o zip e (pelo cPanel **File Manager** → home `/home2/savest57/` → marcar `api.zip` → botão **Extract** → destino `/home2/savest57`) extrair:

```bash
sftp -P 22 savest57@162.241.63.57 <<EOF
put api.zip
EOF
```

### 3. Subir o docroot `dashboard-api/`

Os 4 arquivos de `api/public/` (mais o `index.php` patchado pra apontar pra `~/dashboard-api-app/`):

```bash
cd api/public
sftp -P 22 savest57@162.241.63.57 <<EOF
cd public_html/dashboard-api
put .htaccess
put favicon.ico
put robots.txt
EOF
```

E subir um `index.php` modificado (copie o conteúdo de `api/public/index.php` e troque `__DIR__.'/../...'` por `__DIR__.'/../../dashboard-api-app/...'`).

### 4. Permissões pós-extração

O `Compress-Archive` do Windows perde os bits de execução de diretório. Após extrair no servidor, os dirs do Laravel viram `644`, e o Apache não consegue traverse. **Solução:** subir um script PHP one-shot que faz `chmod -R 755 dirs / 644 files` top-down.

Salve em `web/public-tmp/fix-perms.php`:

```php
<?php
if (($_GET['s'] ?? '') !== 'SEU_SECRET_AQUI') { http_response_code(404); exit; }
function walk($dir) {
    @chmod($dir, 0755);
    $es = @scandir($dir);
    if ($es === false) return;
    foreach ($es as $e) {
        if ($e === '.' || $e === '..') continue;
        $p = "$dir/$e";
        if (@is_link($p)) continue;
        if (@is_dir($p)) walk($p);
        elseif (@is_file($p)) @chmod($p, 0644);
    }
}
walk(__DIR__ . '/../../dashboard-api-app');
echo "done\n";
```

Suba pra `public_html/dashboard-api/fix-perms.php`, acesse `https://api.savestate.com.br/fix-perms.php?s=SEU_SECRET_AQUI`, depois **DELETE o arquivo**.

### 5. Criar `.env` no servidor

cPanel File Manager → `/home2/savest57/dashboard-api-app/` → **+ File** → `.env` → cola do `api/.env.production.example` e preenche:

- `APP_KEY=base64:…` — gere com `php -r 'echo "base64:".base64_encode(random_bytes(32))."\n";'`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — vindos do *MySQL Databases*
- `TWITCH_CLIENT_ID`, `TWITCH_CLIENT_SECRET` — opcional, só precisa pra `releases:sync` ([dev.twitch.tv/console](https://dev.twitch.tv/console))
- `REDDIT_CLIENT_*` — opcional, sobe rate limit da Reddit API (sem isso usa endpoint público com rate baixo)

### 6. MySQL strict mode — `config/database.php`

A HostGator usa MySQL com strict mode + `NO_ZERO_DATE`, que rejeita `timestamp NOT NULL` sem default. As migrations do projeto **já estão corrigidas** com `->useCurrent()`, mas o template Laravel default vem com `'strict' => true`. Se você criar tabelas via `Schema::create` sem default e estiver em prod, vai bater no erro `Invalid default value for 'fetched_at'`.

**Patch defensivo:** em `dashboard-api-app/config/database.php`, na conexão `mysql`, trocar `'strict' => true` por `'strict' => false`. Já está aplicado no deploy atual.

### 7. Migrations, seeders e ingest inicial

Sem shell, dá pra rodar via script PHP one-shot (mesmo padrão do `fix-perms.php`):

```php
<?php
if (($_GET['s'] ?? '') !== 'SECRET') { http_response_code(404); exit; }
set_time_limit(300);
header('Content-Type: text/plain');

$root = __DIR__ . '/../../dashboard-api-app';
require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

foreach ([
    ['migrate', ['--seed' => true, '--force' => true]],
    ['news:ingest', []],
    ['news:recluster', []],
] as [$cmd, $args]) {
    echo "=== $cmd ===\n";
    $kernel->call($cmd, $args);
    echo $kernel->output() . "\n";
}
```

Sobe em `public_html/dashboard-api/setup.php`, chama via curl, depois **DELETE**.

### 8. Cron do scheduler

cPanel → **Cron Jobs** → adicionar:

```
* * * * * cd /home2/savest57/dashboard-api-app && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Esse cron tica a cada minuto e despacha o `schedule:run`, que internamente decide o que executar com base em [api/routes/console.php](api/routes/console.php):

| Comando | Frequência | O que faz |
|---|---|---|
| `news:ingest` | `everyFifteenMinutes()` | Baixa RSS das 4 fontes (IGN, GameSpot, Eurogamer, VGC) |
| `reddit:sync` | `everyThirtyMinutes()` | Cruza notícias com posts do Reddit pra o termômetro |
| `thermometer:snapshot` | `hourly()` | Salva snapshot pra desenhar sparkline de tendência |
| `news:prune --days=30` | `dailyAt('03:00')` | Remove notícias com mais de 30 dias |
| `releases:sync` | `dailyAt('04:00')` | Puxa próximos lançamentos da IGDB (requer Twitch creds) |

Se `/usr/local/bin/php` não funcionar, testar `/usr/bin/php` ou só `php`. A HostGator normalmente lista o caminho correto numa nota acima do form de cron.

## Smoke test

```bash
# API responde com dados?
curl -s https://api.savestate.com.br/api/v1/news?limit=3 | head -200

# Frontend serve o Angular?
curl -sI https://dashboard.savestate.com.br/ | head -3

# CORS preflight do front pra API libera?
curl -sI -X OPTIONS \
  -H "Origin: https://dashboard.savestate.com.br" \
  -H "Access-Control-Request-Method: GET" \
  https://api.savestate.com.br/api/v1/news | grep -i access-control
```

Depois abre `https://dashboard.savestate.com.br/`, vai em DevTools → Network e confirma que as chamadas pra `https://api.savestate.com.br/api/v1/...` voltam 200.

Também recarrega `https://dashboard.savestate.com.br/releases` direto (sem voltar pra home) — se der 404 o `.htaccess` do Angular não foi copiado.

## Deploy incremental

### Frontend

```bash
# Em web/, rebuilda
npm run build -- --configuration production

# Sobe pra docroot
cd dist/web/browser
sftp -P 22 savest57@162.241.63.57 <<EOF
cd public_html/dashboard
put -r .
EOF
```

> Os hashes nos nomes dos chunks mudam a cada build. Pra evitar acúmulo, faça `ls` no remoto e remova os antigos via `rm` no sftp.

### Backend (sem migration nova)

Pra mudanças pequenas em `config/`, `app/`, `routes/`, `src/`:

```bash
sftp -P 22 savest57@162.241.63.57 <<EOF
put api/config/database.php dashboard-api-app/config/database.php
put api/routes/api.php dashboard-api-app/routes/api.php
EOF
```

Pra mudanças grandes, refaça o zip e re-extraia substituindo no File Manager.

### Migration nova

Sobe a migration pro `dashboard-api-app/database/migrations/`, depois roda via script one-shot PHP (igual seção 7) chamando `migrate --force`.

## Troubleshooting

- **500 com body vazio** → `APP_DEBUG=false` está escondendo o erro. Cheque `dashboard-api-app/storage/logs/laravel.log` via SFTP.
- **`Invalid default value for 'fetched_at'`** → MySQL strict mode rejeitando timestamp sem default. Setar `'strict' => false` em `config/database.php` (ou usar `->useCurrent()` nas migrations).
- **CORS bloqueado** → confirmar que `https://dashboard.savestate.com.br` está em [api/config/cors.php](api/config/cors.php), em `allowed_origins`.
- **Permissions errors após extração de zip Windows** → bits de execução dos dirs perdidos. Usar o `fix-perms.php` da seção 4.
- **Angular dá 404 ao recarregar rota interna** → `.htaccess` não foi copiado pra `public_html/dashboard/`. Confere com `sftp ls -la public_html/dashboard/` se o `.htaccess` está lá.
- **Cron não dispara** → caminho do PHP. Testar `which php` (se shell estiver disponível) ou começar com `/usr/local/bin/php`.
- **SFTP "Permission denied"** → confirmar que a public key foi *Authorized* (não só *Imported*) em *Manage SSH Keys*.

## Pendências conhecidas

- **Shell SSH desabilitado** — limita várias coisas pra scripts PHP one-shot. Pedir liberação no suporte HostGator (verificação única).
- **Releases não populadas** — depende de credenciais `TWITCH_CLIENT_ID` / `TWITCH_CLIENT_SECRET` no `.env`.
- **Logs do Laravel acumulam** — sem rotação automática em shared hosting. Considerar `storage/logs/*.log` na rotação manual ou adicionar `LOG_DAILY_DAYS=7` no `.env` (driver `daily` em vez de `single`).
