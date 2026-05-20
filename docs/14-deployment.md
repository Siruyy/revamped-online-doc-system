# 14 — Deployment

## Target Environment

- **Provider:** DigitalOcean Droplet
- **Spec recommended:** 2 vCPU, 4 GB RAM, 80 GB SSD (the $24/mo "Basic" tier)
- **OS:** Ubuntu 22.04 LTS
- **Orchestrator:** Dokploy (Docker-based, free, self-hosted)
- **Current VPS:** `100.109.80.94`
- **Temporary domain plan:** `docs.siruyy.cloud` for the Laravel app and `docs-ws.siruyy.cloud` for Reverb until the permanent domain is available.

> Dokploy must be installed on the droplet first. See [Dokploy docs](https://dokploy.com/docs).

## Architecture

```
┌────────────────────── Droplet ──────────────────────┐
│                                                      │
│  ┌─────────── Dokploy Network ────────────────────┐  │
│  │                                                │  │
│  │  ┌───────────────┐   ┌────────────┐            │  │
│  │  │ app (PHP-FPM  │   │  reverb    │            │  │
│  │  │  + Nginx)     │   │ (websocket)│            │  │
│  │  └───────┬───────┘   └────────────┘            │  │
│  │          │                                     │  │
│  │  ┌───────▼───────┐   ┌────────────┐            │  │
│  │  │  queue worker │   │   mysql    │            │  │
│  │  └───────────────┘   └────────────┘            │  │
│  │                                                │  │
│  │  Persistent volumes: app-storage, mysql-data   │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Dokploy reverse proxy → :80 / :443                  │
└──────────────────────────────────────────────────────┘
```

## Containers

| Service | Image base | Purpose |
|---------|-----------|---------|
| `app` | `php:8.4-fpm-alpine` (custom build) | Laravel app + Nginx in one container |
| `mysql` | `mysql:8.0` | Database |
| `reverb` | Same as `app`, different command | WebSocket server |
| `queue` | Same as `app`, different command | Async job processor |

> Optional: Redis container if we move to Redis cache/queue later.

## Dockerfile (app)

```dockerfile
# Dockerfile
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    nginx supervisor git curl libpng-dev libjpeg-turbo-dev \
    freetype-dev libzip-dev oniguruma-dev icu-dev mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
```

## Supervisor Config

```ini
# docker/supervisord.conf
[supervisord]
nodaemon=true

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
```

For the **queue** and **reverb** containers, use a different supervisord config:

```ini
# docker/supervisord-queue.conf
[supervisord]
nodaemon=true

[program:queue]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stderr_logfile=/dev/stderr
```

```ini
# docker/supervisord-reverb.conf
[supervisord]
nodaemon=true

[program:reverb]
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stderr_logfile=/dev/stderr
```

## Entrypoint Script

```bash
#!/bin/sh
# docker/entrypoint.sh
set -e

# Wait for database
until mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    echo "Waiting for database..."
    sleep 2
done

# Run migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Start supervisor
exec "$@"
```

## Nginx Config

```nginx
# docker/nginx.conf
worker_processes auto;
events { worker_connections 1024; }

http {
    include /etc/nginx/mime.types;
    sendfile on;
    keepalive_timeout 65;
    client_max_body_size 10M;

    server {
        listen 80;
        root /var/www/html/public;
        index index.php;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.(?!well-known) {
            deny all;
        }

        gzip on;
        gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    }
}
```

## Dokploy Setup Steps

Use `docker-compose.dokploy.yml` for the first deployment. The detailed operational runbook is in [`docs/operations/dokploy-runbook.md`](operations/dokploy-runbook.md).

1. **Create DNS records** pointing the temporary domains `docs.siruyy.cloud` and `docs-ws.siruyy.cloud` to `100.109.80.94`.
2. **Create new Project** named `svci-document-system`.
3. **Add Compose Application** → connect GitHub repo.
4. **Set Compose file path** to `docker-compose.dokploy.yml`.
5. **Configure environment variables** in Dokploy UI.
6. **Configure domains**:
    - `docs.siruyy.cloud` → `app` service port `80`
    - `docs-ws.siruyy.cloud` → `reverb` service port `8080`
7. **Enable HTTPS** for both domains.
8. **Configure health check** → `GET /up`.
9. **Deploy** — Dokploy builds the image, starts MySQL, runs migrations through the app container, and starts queue/Reverb.
10. **Run seeder** for initial data: `php artisan db:seed --class=ProductionSeeder --force`.
11. **Create initial SuperAdmin**: `php artisan svci:make-superadmin admin@example.com`.

## Environment Variables

```env
APP_NAME="SVCI Document System"
APP_ENV=production
APP_KEY=                     # generate with php artisan key:generate
APP_DEBUG=false
APP_URL=https://docs.siruyy.cloud

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=svci
DB_USERNAME=svci_app
DB_PASSWORD=<strong-random>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=svci
REVERB_APP_KEY=<random>
REVERB_APP_SECRET=<random>
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=docs-ws.siruyy.cloud
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@svci.example
MAIL_FROM_NAME="SVCI Document System"
```

## SSL

Dokploy supports automatic Let's Encrypt SSL via Traefik. After pointing the domain records to the VPS:

1. In Dokploy → Application → Domains → add `docs.siruyy.cloud`.
2. In Dokploy → Reverb service → Domains → add `docs-ws.siruyy.cloud`.
2. Enable "HTTPS" → Dokploy provisions cert via Let's Encrypt.
3. Re-deploy after certificates are ready.

## Permanent Domain Cutover

When the permanent domain is ready:

1. Point the new app and Reverb/WebSocket DNS records to `100.109.80.94`.
2. Update Dokploy domains from the temporary `siruyy.cloud` subdomains to the permanent domains.
3. Update Dokploy environment variables:
    - `APP_URL=https://<permanent-app-domain>`
    - `REVERB_ALLOWED_ORIGINS=https://<permanent-app-domain>`
    - `VITE_REVERB_HOST=<permanent-reverb-domain>`
4. Redeploy so Vite rebuilds the browser bundle with the new `VITE_REVERB_HOST`.
5. Re-run the `/up`, login, queue, and Reverb smoke checks.

## Backups

Dokploy supports scheduled backups for MySQL volumes. Configure:
- Daily MySQL backup → DigitalOcean Spaces
- Weekly storage volume snapshot

## Rollback Strategy

- Dokploy keeps the last N image builds. Rollback = redeploy a previous build.
- DB migrations should be **reversible** (always provide a `down()` method) for the rare manual rollback. In practice, prefer forward-only migrations.

## Health Checks

Laravel's built-in health route is configured at `/up` in `bootstrap/app.php`.

Configure Dokploy healthcheck → `GET /up` every 30s.

## Monitoring (Initial)

- Dokploy built-in container metrics (CPU/RAM/disk).
- Laravel daily logs (`storage/logs/`) — rotate weekly.
- For v2 consider Sentry or Bugsnag.

## Local-to-Production Workflow

1. Develop on local Docker Compose (mirror of production).
2. Push to GitHub `main` branch.
3. Dokploy auto-pulls and deploys (configured webhook).
4. Migrations run automatically via entrypoint.
5. Smoke test on production after each deploy.
