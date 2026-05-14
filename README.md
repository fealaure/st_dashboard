# Save State Dashboard

Dashboard interno do [Save State](https://savestate.com.br/) para acompanhar o cenário de games em tempo real: feed agregado de notícias dos principais sites internacionais, termômetro de relevância por notícia e calendário de próximos lançamentos.

> Trabalho em andamento. Veja [Roadmap](#roadmap) abaixo pro status atual.

## Estrutura

```
st-dashboard/
├── api/    # Backend Laravel 11 (PHP 8.3) — agrega RSS, Reddit, IGDB e expõe JSON
└── web/    # Frontend Angular 21 — consome a API e renderiza o dashboard
```

Os dois projetos são independentes e podem ser desenvolvidos e deployados separadamente.

## Stack

- **Backend**: PHP 8.3 + Laravel 11, MySQL 8, arquitetura DDD em `api/src/` (namespace `SaveState\`)
- **Frontend**: Angular 21 standalone + signals, SCSS, tema dark com azul Save State `#00c2ff`
- **Fontes de dados**: RSS via [SimplePie](https://www.simplepie.org/) (IGN, GameSpot, Eurogamer, VGC); Reddit API e [IGDB](https://api.igdb.com/) virão nas próximas fases
- **Hospedagem alvo**: compartilhada cPanel/Plesk — cron do cPanel dispara `php artisan schedule:run` a cada minuto

## Como rodar localmente

Pré-requisitos: PHP **8.2+**, Composer, MySQL **8+**, Node **20+** e Angular CLI.
No Windows, recomendamos o [Laragon](https://laragon.org/) para PHP + MySQL + Composer.

### 1. Backend

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

Crie o banco no MySQL e ajuste as credenciais em `api/.env`:

```sql
CREATE DATABASE st_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Aplique migrations + seeders (popula as 4 fontes RSS) e suba o servidor:

```bash
php artisan migrate --seed
php artisan news:ingest      # primeira ingestão de notícias
php artisan serve            # http://127.0.0.1:8000
```

### 2. Frontend

```bash
cd web
npm install
npm start                    # http://localhost:4200 (proxy /api → backend)
```

## Comandos Artisan disponíveis

| Comando | O que faz |
|---|---|
| `php artisan news:ingest` | Baixa as notícias dos feeds RSS configurados. |
| `php artisan news:recluster` | Atribui um cluster a `news_items` que ainda não têm. |
| `php artisan news:prune --days=30` | Remove notícias com `published_at` mais antigo que N dias. |
| `php artisan schedule:list` | Mostra os jobs agendados (ingest a cada 15min, prune diário às 03:00). |

Em produção, o cron do cPanel deve chamar a cada minuto:

```
* * * * * cd /home/USER/api && php artisan schedule:run >> /dev/null 2>&1
```

## Arquitetura DDD

O backend separa bounded contexts em `api/src/`:

```
api/src/
├── News/
│   ├── Domain/          # entidades, value objects, interfaces de repo (PHP puro)
│   ├── Application/     # use cases (IngestNewsUseCase, ClusterizeNewsItemUseCase, …)
│   └── Infrastructure/  # implementações Eloquent + SimplePie
├── Releases/            # bounded context de lançamentos (Fase 4)
└── Shared/              # kernel compartilhado (Clock, etc.)
```

`Domain/` não conhece Laravel, Eloquent ou HTTP — só PHP puro. As implementações ficam em `Infrastructure/` e são injetadas via interface através do `AppServiceProvider`.

No frontend, cada feature segue a mesma divisão:

```
web/src/app/features/news-feed/
├── domain/           # tipos TS (NewsCluster, NewsSource)
├── application/      # facade com signals
├── infrastructure/   # NewsApi (HttpClient)
└── ui/               # componentes
```

## Roadmap

| Fase | Status | Entrega |
|---|---|---|
| 0 | ✅ | Scaffolding monorepo + DDD + MySQL + tema dark |
| 1 | ✅ | Ingestão RSS ponta a ponta + listagem |
| 2 | ✅ | Clustering por simhash + termômetro de cobertura |
| 3 | ⏭️ | Reddit API + score híbrido + snapshots históricos |
| 4 | 📋 | IGDB + tela de lançamentos |
| 5 | 📋 | Polish visual + filtros + busca |

## Licença

Projeto interno da Save State. Sem licença pública por enquanto.
