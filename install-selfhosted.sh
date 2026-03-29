#!/usr/bin/env bash
set -Eeuo pipefail

IFS=$'\n\t'

MODE="install"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$SCRIPT_DIR}"
EXPECTED_APP_DIR="${EXPECTED_APP_DIR:-/var/www/rafen-selfhosted}"
ENV_FILE="${ENV_FILE:-$APP_DIR/.env}"
ENV_EXAMPLE_FILE="${ENV_EXAMPLE_FILE:-$APP_DIR/.env.example}"
APP_USER="${APP_USER:-www-data}"
APP_GROUP="${APP_GROUP:-www-data}"
SYSTEM_TIMEZONE="${SYSTEM_TIMEZONE:-Asia/Jakarta}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
ALLOW_NON_ROOT="${ALLOW_NON_ROOT:-0}"
DRY_RUN="${DRY_RUN:-0}"
RUN_COMPOSER_INSTALL="${RUN_COMPOSER_INSTALL:-1}"
RUN_NPM_BUILD="${RUN_NPM_BUILD:-1}"
RUN_MIGRATE="${RUN_MIGRATE:-1}"
RUN_SUPER_ADMIN_SETUP="${RUN_SUPER_ADMIN_SETUP:-1}"
APP_URL_OVERRIDE="${APP_URL_OVERRIDE:-}"
ADMIN_NAME="${ADMIN_NAME:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-$APP_DIR/database/database.sqlite}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

info() {
    printf '[INFO] %s\n' "$1"
}

warn() {
    printf '[WARN] %s\n' "$1"
}

fail() {
    printf '[ERROR] %s\n' "$1" >&2
    exit 1
}

usage() {
    cat <<'EOF'
Usage:
  sudo bash install-selfhosted.sh [install|deploy|status] [options]

Modes:
  install   Prepare .env, runtime directories, dependencies, migrate, and optional super admin
  deploy    Refresh dependencies and rerun runtime deployment steps
  status    Print current deployment summary

Options:
  --app-url <url>           Override APP_URL
  --admin-name <name>       Name for initial super admin
  --admin-email <email>     Email for initial super admin
  --admin-password <value>  Password for initial super admin
  --db-connection <driver>  Database connection (sqlite or mysql)
  --db-host <host>          Database host for non-sqlite setup
  --db-port <port>          Database port for non-sqlite setup
  --db-name <name|path>     Database name or sqlite file path
  --db-user <user>          Database username for non-sqlite setup
  --db-password <value>     Database password for non-sqlite setup
  --skip-composer-install   Skip composer install
  --skip-npm-build          Skip npm install/build
  --skip-migrate            Skip php artisan migrate --force
  --skip-super-admin        Skip php artisan user:create-super-admin
  --dry-run                 Print actions without executing commands
  --help                    Show this help

Env overrides:
  APP_DIR, EXPECTED_APP_DIR, ENV_FILE, APP_USER, APP_GROUP, SYSTEM_TIMEZONE,
  PHP_BIN, COMPOSER_BIN, NPM_BIN, ALLOW_NON_ROOT, RUN_COMPOSER_INSTALL,
  RUN_NPM_BUILD, RUN_MIGRATE, RUN_SUPER_ADMIN_SETUP, DB_CONNECTION,
  DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.
EOF
}

parse_args() {
    if [ "$#" -gt 0 ]; then
        case "$1" in
            install|deploy|status)
                MODE="$1"
                shift
                ;;
            --help|-h)
                usage
                exit 0
                ;;
        esac
    fi

    while [ "$#" -gt 0 ]; do
        case "$1" in
            --app-url)
                APP_URL_OVERRIDE="$2"
                shift 2
                ;;
            --admin-name)
                ADMIN_NAME="$2"
                shift 2
                ;;
            --admin-email)
                ADMIN_EMAIL="$2"
                shift 2
                ;;
            --admin-password)
                ADMIN_PASSWORD="$2"
                shift 2
                ;;
            --db-connection)
                DB_CONNECTION="$2"
                shift 2
                ;;
            --db-host)
                DB_HOST="$2"
                shift 2
                ;;
            --db-port)
                DB_PORT="$2"
                shift 2
                ;;
            --db-name)
                DB_DATABASE="$2"
                shift 2
                ;;
            --db-user)
                DB_USERNAME="$2"
                shift 2
                ;;
            --db-password)
                DB_PASSWORD="$2"
                shift 2
                ;;
            --skip-composer-install)
                RUN_COMPOSER_INSTALL=0
                shift
                ;;
            --skip-npm-build)
                RUN_NPM_BUILD=0
                shift
                ;;
            --skip-migrate)
                RUN_MIGRATE=0
                shift
                ;;
            --skip-super-admin)
                RUN_SUPER_ADMIN_SETUP=0
                shift
                ;;
            --dry-run)
                DRY_RUN=1
                shift
                ;;
            --help|-h)
                usage
                exit 0
                ;;
            *)
                fail "Argumen tidak dikenal: $1"
                ;;
        esac
    done
}

require_root() {
    if [ "$ALLOW_NON_ROOT" = "1" ]; then
        return
    fi

    if [ "$(id -u)" -ne 0 ]; then
        fail "Script ini harus dijalankan sebagai root. Gunakan ALLOW_NON_ROOT=1 hanya untuk pengujian."
    fi
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

run_command() {
    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] %s\n' "$*"
        return 0
    fi

    "$@"
}

run_in_app() {
    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] (cd %s && %s)\n' "$APP_DIR" "$*"
        return 0
    fi

    (
        cd "$APP_DIR"
        "$@"
    )
}

ensure_expected_app_dir() {
    if [ "$APP_DIR" != "$EXPECTED_APP_DIR" ]; then
        warn "APP_DIR saat ini adalah $APP_DIR, bukan $EXPECTED_APP_DIR."
    fi
}

ensure_app_layout() {
    [ -d "$APP_DIR" ] || fail "APP_DIR tidak ditemukan: $APP_DIR"
    [ -f "$APP_DIR/artisan" ] || fail "File artisan tidak ditemukan di $APP_DIR"
    [ -f "$APP_DIR/composer.json" ] || fail "File composer.json tidak ditemukan di $APP_DIR"
    [ -f "$ENV_EXAMPLE_FILE" ] || fail "File .env.example tidak ditemukan di $ENV_EXAMPLE_FILE"
}

install_dir() {
    local path="$1"

    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] mkdir -p %s\n' "$path"
        return 0
    fi

    mkdir -p "$path"
}

ensure_runtime_directories() {
    local directories=(
        "$APP_DIR/bootstrap/cache"
        "$APP_DIR/database"
        "$APP_DIR/storage/app/license"
        "$APP_DIR/storage/framework/cache/data"
        "$APP_DIR/storage/framework/sessions"
        "$APP_DIR/storage/framework/views"
        "$APP_DIR/storage/logs"
        "$APP_DIR/tests/Unit"
    )

    for directory in "${directories[@]}"; do
        install_dir "$directory"
    done
}

copy_env_file_if_missing() {
    if [ -f "$ENV_FILE" ]; then
        return
    fi

    info "Menyalin .env dari template."
    run_command cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
}

read_env() {
    local key="$1"
    local value=""

    if [ -f "$ENV_FILE" ]; then
        value="$(grep -E "^${key}=" "$ENV_FILE" | tail -n1 | cut -d= -f2- || true)"
    fi

    value="${value%\"}"
    value="${value#\"}"
    printf '%s' "$value"
}

set_env() {
    local key="$1"
    local value="$2"
    local escaped
    local formatted
    local tmp_file

    escaped="$(printf '%s' "$value" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')"

    if printf '%s' "$value" | grep -q '[[:space:]]'; then
        formatted="\"${escaped}\""
    else
        formatted="${escaped}"
    fi

    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] set %s=%s in %s\n' "$key" "$formatted" "$ENV_FILE"
        return 0
    fi

    tmp_file="$(mktemp)"

    if [ -f "$ENV_FILE" ]; then
        awk -v key="$key" -v val="$formatted" '
            BEGIN { found=0 }
            $0 ~ "^" key "=" {
                print key "=" val
                found=1
                next
            }
            { print }
            END {
                if (!found) {
                    print key "=" val
                }
            }
        ' "$ENV_FILE" >"$tmp_file"
    else
        printf '%s=%s\n' "$key" "$formatted" >"$tmp_file"
    fi

    mv "$tmp_file" "$ENV_FILE"
}

normalize_sqlite_path() {
    if [ "$DB_CONNECTION" != "sqlite" ]; then
        return
    fi

    case "$DB_DATABASE" in
        /*) ;;
        *)
            DB_DATABASE="$APP_DIR/$DB_DATABASE"
            ;;
    esac
}

configure_environment() {
    normalize_sqlite_path

    if [ -n "$APP_URL_OVERRIDE" ]; then
        set_env APP_URL "$APP_URL_OVERRIDE"
    fi

    set_env APP_NAME "Rafen Self-Hosted"
    set_env APP_ENV "production"
    set_env APP_DEBUG "false"
    set_env DB_CONNECTION "$DB_CONNECTION"
    set_env SESSION_DRIVER "file"
    set_env QUEUE_CONNECTION "sync"
    set_env CACHE_STORE "file"
    set_env LICENSE_SELF_HOSTED_ENABLED "true"
    set_env LICENSE_ENFORCE "true"
    set_env LICENSE_FILE_PATH "$APP_DIR/storage/app/license/rafen.lic"
    set_env LICENSE_MACHINE_ID_PATH "/etc/machine-id"
    set_env LICENSE_DEFAULT_GRACE_DAYS "21"

    if [ "$DB_CONNECTION" = "sqlite" ]; then
        set_env DB_DATABASE "$DB_DATABASE"
    else
        set_env DB_HOST "$DB_HOST"
        set_env DB_PORT "$DB_PORT"
        set_env DB_DATABASE "$DB_DATABASE"
        set_env DB_USERNAME "$DB_USERNAME"
        set_env DB_PASSWORD "$DB_PASSWORD"
    fi
}

prepare_sqlite_database() {
    if [ "$DB_CONNECTION" != "sqlite" ]; then
        return
    fi

    install_dir "$(dirname "$DB_DATABASE")"

    if [ ! -f "$DB_DATABASE" ]; then
        info "Membuat file database sqlite: $DB_DATABASE"
        run_command touch "$DB_DATABASE"
    fi
}

apply_basic_permissions() {
    if [ "$ALLOW_NON_ROOT" = "1" ] || [ "$DRY_RUN" = "1" ]; then
        return
    fi

    if command_exists chown; then
        run_command chown -R "$APP_USER:$APP_GROUP" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    fi

    if command_exists chmod; then
        run_command chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    fi
}

configure_timezone() {
    if [ "$ALLOW_NON_ROOT" = "1" ] || [ "$DRY_RUN" = "1" ]; then
        return
    fi

    if command_exists timedatectl; then
        run_command timedatectl set-timezone "$SYSTEM_TIMEZONE"
    fi
}

composer_install() {
    if [ "$RUN_COMPOSER_INSTALL" != "1" ]; then
        return
    fi

    command_exists "$COMPOSER_BIN" || fail "Composer tidak ditemukan: $COMPOSER_BIN"
    run_in_app "$COMPOSER_BIN" install --no-interaction --prefer-dist --optimize-autoloader
}

npm_build() {
    if [ "$RUN_NPM_BUILD" != "1" ]; then
        return
    fi

    [ -f "$APP_DIR/package.json" ] || return

    command_exists "$NPM_BIN" || fail "npm tidak ditemukan: $NPM_BIN"
    run_in_app "$NPM_BIN" install
    run_in_app "$NPM_BIN" run build
}

ensure_app_key() {
    local app_key

    app_key="$(read_env APP_KEY)"

    if [ -n "$app_key" ]; then
        return
    fi

    run_in_app "$PHP_BIN" artisan key:generate --force
}

run_artisan_runtime_setup() {
    command_exists "$PHP_BIN" || fail "PHP binary tidak ditemukan: $PHP_BIN"

    run_in_app "$PHP_BIN" artisan config:clear --ansi
    ensure_app_key

    if [ "$RUN_MIGRATE" = "1" ]; then
        run_in_app "$PHP_BIN" artisan migrate --force --ansi
    fi

    if [ "$RUN_SUPER_ADMIN_SETUP" != "1" ]; then
        return
    fi

    if [ -z "$ADMIN_NAME" ] || [ -z "$ADMIN_EMAIL" ] || [ -z "$ADMIN_PASSWORD" ]; then
        warn "Data super admin belum lengkap, melewati pembuatan user awal."
        return
    fi

    run_in_app "$PHP_BIN" artisan user:create-super-admin "$ADMIN_NAME" "$ADMIN_EMAIL" --password="$ADMIN_PASSWORD" --ansi
}

show_status() {
    normalize_sqlite_path

    printf 'Mode                 : %s\n' "$MODE"
    printf 'App Directory        : %s\n' "$APP_DIR"
    printf 'Env File             : %s\n' "$ENV_FILE"
    printf 'Env Exists           : %s\n' "$([ -f "$ENV_FILE" ] && printf yes || printf no)"
    printf 'Vendor Directory     : %s\n' "$([ -d "$APP_DIR/vendor" ] && printf yes || printf no)"
    printf 'Bootstrap Cache Dir  : %s\n' "$([ -d "$APP_DIR/bootstrap/cache" ] && printf yes || printf no)"
    printf 'License Directory    : %s\n' "$([ -d "$APP_DIR/storage/app/license" ] && printf yes || printf no)"
    printf 'DB Connection        : %s\n' "$DB_CONNECTION"

    if [ "$DB_CONNECTION" = "sqlite" ]; then
        printf 'SQLite Database      : %s\n' "$DB_DATABASE"
        printf 'SQLite Exists        : %s\n' "$([ -f "$DB_DATABASE" ] && printf yes || printf no)"
    else
        printf 'Database Name        : %s\n' "$DB_DATABASE"
        printf 'Database Host        : %s\n' "$DB_HOST"
    fi

    if [ -f "$ENV_FILE" ]; then
        printf 'App URL              : %s\n' "$(read_env APP_URL)"
        printf 'License Enabled      : %s\n' "$(read_env LICENSE_SELF_HOSTED_ENABLED)"
        printf 'License Enforced     : %s\n' "$(read_env LICENSE_ENFORCE)"
    fi
}

run_install_or_deploy() {
    require_root
    ensure_expected_app_dir
    ensure_app_layout
    ensure_runtime_directories
    copy_env_file_if_missing
    configure_environment
    prepare_sqlite_database
    configure_timezone
    composer_install
    npm_build
    run_artisan_runtime_setup
    apply_basic_permissions

    info "Installer/deployment self-hosted selesai."
    show_status
}

main() {
    parse_args "$@"

    case "$MODE" in
        install|deploy)
            run_install_or_deploy
            ;;
        status)
            ensure_app_layout
            show_status
            ;;
        *)
            fail "Mode tidak didukung: $MODE"
            ;;
    esac
}

main "$@"
