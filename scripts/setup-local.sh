#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$APP_DIR/.env"
RESET_DATABASE=false

usage() {
    cat <<'EOF'
Usage: scripts/setup-local.sh [--reset]

Builds and starts the Docker development stack, prepares the database,
creates the storage link, and builds the frontend assets.

Options:
  --reset    Delete local Docker volumes and recreate the demo database.
  -h, --help Show this help.

The default path preserves an existing local database. Use --reset only when
you intentionally want to erase the local MySQL volume and reseed demo data.
EOF
}

for arg in "$@"; do
    case "$arg" in
        --reset)
            RESET_DATABASE=true
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Unknown option: $arg" >&2
            usage >&2
            exit 1
            ;;
    esac
done

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is not installed or not on PATH. Install Docker Desktop, then run this script again." >&2
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose is not available. Use a current Docker Desktop or Docker Engine installation." >&2
    exit 1
fi

cd "$APP_DIR"

if [[ ! -f "$ENV_FILE" ]]; then
    cp .env.example .env
    echo "Created .env from .env.example."
fi

ensure_env_default() {
    local key="$1"
    local value="$2"

    if ! grep -q "^${key}=" "$ENV_FILE"; then
        printf '\n%s=%s\n' "$key" "$value" >> "$ENV_FILE"
    fi
}

# DatabaseSeeder only creates a local SuperAdmin when these values exist.
# Keep existing values if the developer has already configured them.
ensure_env_default SUPERADMIN_EMAIL "superadmin@svci.test"
ensure_env_default SUPERADMIN_PASSWORD "password"
ensure_env_default SUPERADMIN_FULLNAME "SVCI Local SuperAdmin"
ensure_env_default SUPERADMIN_USERNAME "local_superadmin"

if [[ "$RESET_DATABASE" == true ]]; then
    echo "Removing local Docker volumes..."
    docker compose down -v
fi

echo "Starting MySQL, MailHog, and the Laravel app..."
docker compose up -d --build mysql mailhog app

artisan() {
    docker compose exec -T app php artisan "$@"
}

echo "Installing backend dependencies..."
docker compose exec -T app composer install --no-interaction

echo "Installing frontend dependencies..."
docker compose exec -T app npm ci

if grep -Eq '^APP_KEY=.+$' "$ENV_FILE"; then
    echo "Using the existing application key."
else
    echo "Generating the local application key..."
    artisan key:generate
fi

if [[ "$RESET_DATABASE" == true ]]; then
    echo "Recreating and seeding the local database..."
    artisan migrate:fresh --seed --force
else
    echo "Applying database migrations..."
    artisan migrate --force

    user_count="$(docker compose exec -T mysql mysql -usvci_dev -psvci_secret -Nse 'SELECT COUNT(*) FROM svci_dev.users' 2>/dev/null | tr -d '[:space:]')"
    if [[ "$user_count" == "0" ]]; then
        echo "Seeding local demo data..."
        artisan db:seed --force
    fi
fi

echo "Creating the storage link..."
artisan storage:link --force

echo "Building frontend assets..."
docker compose exec -T app npm run build

echo "Starting Reverb..."
docker compose up -d --build reverb

docker compose ps

cat <<'EOF'

Local setup is ready.

App:       http://localhost:8000
Requests:  http://localhost:8000/request-document
Tracking:  http://localhost:8000/track-document
MailHog:   http://localhost:8025
Reverb:    ws://localhost:8080

Useful commands:
  docker compose logs -f app
  docker compose exec app php artisan queue:work
  docker compose down

Run scripts/setup-local.sh --reset only when you want to erase and reseed the
local database.
EOF
