# VELORA Backend

Laravel 13 API backend using MongoDB as the only database engine and Redis for cache, queues, rate limiting, and sessions.

## Requirements

- PHP 8.3+
- Composer
- PHP extensions: `mongodb`, `redis`, `zip`
- MongoDB replica set
- Redis

## Environment

Copy `.env.example` to `.env` and keep these persistence settings:

```dotenv
DB_CONNECTION=mongodb
DB_DSN=mongodb://127.0.0.1:27017/?replicaSet=rs0
DB_DATABASE=velora
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

SQLite and SQL database drivers are intentionally unsupported.

For production, set `APP_ENV=production`, `APP_DEBUG=false`, and keep `SEED_DEMO_DATA=0`.

## Commands

```bash
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
composer test
```

`php artisan migrate` creates MongoDB collections and required indexes. Demo data is disposable and can be recreated with `php artisan migrate:fresh --seed`.

Run a queue worker wherever the API is deployed:

```bash
php artisan queue:work redis --tries=3 --timeout=120
```

Published-event notifications are fanned out by the queue worker, not by the HTTP request.

## Health

The API health endpoint is available at:

```text
GET /api/health
```

In production, failed dependency details are sanitized when `APP_DEBUG=false`.

## Hardening

See `docs/operations/hardening.md` for the current hardening status, production checklist, and known technical debt.
