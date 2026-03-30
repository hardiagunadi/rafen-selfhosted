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
APT_GET_BIN="${APT_GET_BIN:-apt-get}"
SYSTEMCTL_BIN="${SYSTEMCTL_BIN:-systemctl}"
VISUDO_BIN="${VISUDO_BIN:-visudo}"
ALLOW_NON_ROOT="${ALLOW_NON_ROOT:-0}"
DRY_RUN="${DRY_RUN:-0}"
RUN_COMPOSER_INSTALL="${RUN_COMPOSER_INSTALL:-1}"
RUN_NPM_BUILD="${RUN_NPM_BUILD:-1}"
RUN_MIGRATE="${RUN_MIGRATE:-1}"
RUN_SUPER_ADMIN_SETUP="${RUN_SUPER_ADMIN_SETUP:-1}"
RUN_WIREGUARD_SYSTEM_BOOTSTRAP="${RUN_WIREGUARD_SYSTEM_BOOTSTRAP:-0}"
RUN_WIREGUARD_PACKAGE_INSTALL="${RUN_WIREGUARD_PACKAGE_INSTALL:-1}"
APP_URL_OVERRIDE="${APP_URL_OVERRIDE:-}"
APP_DOMAIN="${APP_DOMAIN:-}"
LICENSE_PUBLIC_KEY_VALUE="${LICENSE_PUBLIC_KEY_VALUE:-}"
ADMIN_NAME="${ADMIN_NAME:-}"
ADMIN_EMAIL="${ADMIN_EMAIL:-}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"
RUN_SYSTEM_BOOTSTRAP="${RUN_SYSTEM_BOOTSTRAP:-1}"
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-$APP_DIR/database/database.sqlite}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
WG_SYSTEM_DIR="${WG_SYSTEM_DIR:-/etc/wireguard}"
WG_SYSTEM_INTERFACE="${WG_SYSTEM_INTERFACE:-wg0}"
WG_SYSTEM_SERVICE="${WG_SYSTEM_SERVICE:-wg-quick@${WG_SYSTEM_INTERFACE}}"
WG_SUDOERS_PATH="${WG_SUDOERS_PATH:-/etc/sudoers.d/rafen-wireguard}"
WG_SYNC_HELPER_PATH="${WG_SYNC_HELPER_PATH:-$APP_DIR/scripts/wireguard-apply.sh}"
SYSTEM_PRIMARY_IP="${SYSTEM_PRIMARY_IP:-}"
NGINX_BIN="${NGINX_BIN:-nginx}"
NGINX_SERVICE="${NGINX_SERVICE:-nginx}"
NGINX_SITE_AVAILABLE_PATH="${NGINX_SITE_AVAILABLE_PATH:-/etc/nginx/sites-available/rafen-selfhosted.conf}"
NGINX_SITE_ENABLED_PATH="${NGINX_SITE_ENABLED_PATH:-/etc/nginx/sites-enabled/rafen-selfhosted.conf}"
NGINX_DEFAULT_SITE_PATH="${NGINX_DEFAULT_SITE_PATH:-/etc/nginx/sites-enabled/default}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"
PHP_FPM_SOCK="${PHP_FPM_SOCK:-}"

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
  bash install-selfhosted.sh [install|deploy|status] [options]

Modes:
  install   Prepare .env, runtime directories, dependencies, migrate, and optional super admin
  deploy    Refresh dependencies and rerun runtime deployment steps
  status    Print current deployment summary

Options:
  --app-url <url>           Override APP_URL
  --domain <host>           Domain/host untuk APP_URL dan server_name Nginx
  --license-public-key <key>
                            Public key untuk verifikasi lisensi self-hosted
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
  --skip-system-bootstrap   Skip provisioning package sistem dan konfigurasi Nginx/PHP-FPM
  --wireguard-system        Prepare OS-level WireGuard helper and service bootstrap
  --skip-wireguard-package-install
                            Skip apt-get install for WireGuard packages during bootstrap
  --dry-run                 Print actions without executing commands
  --help                    Show this help

Env overrides:
  APP_DIR, EXPECTED_APP_DIR, ENV_FILE, APP_USER, APP_GROUP, SYSTEM_TIMEZONE,
  PHP_BIN, COMPOSER_BIN, NPM_BIN, APT_GET_BIN, SYSTEMCTL_BIN, VISUDO_BIN,
  NGINX_BIN, NGINX_SERVICE, ALLOW_NON_ROOT, RUN_COMPOSER_INSTALL, RUN_NPM_BUILD, RUN_MIGRATE,
  RUN_SUPER_ADMIN_SETUP, RUN_SYSTEM_BOOTSTRAP, RUN_WIREGUARD_SYSTEM_BOOTSTRAP, LICENSE_PUBLIC_KEY_VALUE,
  RUN_WIREGUARD_PACKAGE_INSTALL, DB_CONNECTION, DB_HOST, DB_PORT,
  DB_DATABASE, DB_USERNAME, DB_PASSWORD, WG_SYSTEM_DIR, WG_SYSTEM_INTERFACE,
  WG_SYSTEM_SERVICE, WG_SUDOERS_PATH, WG_SYNC_HELPER_PATH, APP_DOMAIN,
  SYSTEM_PRIMARY_IP, NGINX_SITE_AVAILABLE_PATH, NGINX_SITE_ENABLED_PATH,
  NGINX_DEFAULT_SITE_PATH, PHP_FPM_SERVICE, PHP_FPM_SOCK.
EOF
}

elevate_with_sudo() {
    if [ "$ALLOW_NON_ROOT" = "1" ] || [ "$(id -u)" -eq 0 ]; then
        return
    fi

    command_exists sudo || fail "sudo tidak ditemukan. Jalankan script ini sebagai root atau install sudo terlebih dahulu."

    info "Hak akses root diperlukan untuk provisioning fresh server. Silakan masukkan password sudo."

    sudo -v || fail "Autentikasi sudo gagal."

    exec sudo --preserve-env=APP_DIR,EXPECTED_APP_DIR,ENV_FILE,ENV_EXAMPLE_FILE,APP_USER,APP_GROUP,SYSTEM_TIMEZONE,PHP_BIN,COMPOSER_BIN,NPM_BIN,APT_GET_BIN,SYSTEMCTL_BIN,VISUDO_BIN,ALLOW_NON_ROOT,DRY_RUN,RUN_COMPOSER_INSTALL,RUN_NPM_BUILD,RUN_MIGRATE,RUN_SUPER_ADMIN_SETUP,RUN_SYSTEM_BOOTSTRAP,RUN_WIREGUARD_SYSTEM_BOOTSTRAP,RUN_WIREGUARD_PACKAGE_INSTALL,APP_URL_OVERRIDE,APP_DOMAIN,LICENSE_PUBLIC_KEY_VALUE,ADMIN_NAME,ADMIN_EMAIL,ADMIN_PASSWORD,DB_CONNECTION,DB_HOST,DB_PORT,DB_DATABASE,DB_USERNAME,DB_PASSWORD,WG_SYSTEM_DIR,WG_SYSTEM_INTERFACE,WG_SYSTEM_SERVICE,WG_SUDOERS_PATH,WG_SYNC_HELPER_PATH,SYSTEM_PRIMARY_IP,NGINX_BIN,NGINX_SERVICE,NGINX_SITE_AVAILABLE_PATH,NGINX_SITE_ENABLED_PATH,NGINX_DEFAULT_SITE_PATH,PHP_FPM_SERVICE,PHP_FPM_SOCK bash "$0" "$@"
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
            --domain)
                APP_DOMAIN="$2"
                shift 2
                ;;
            --license-public-key)
                LICENSE_PUBLIC_KEY_VALUE="$2"
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
            --skip-system-bootstrap)
                RUN_SYSTEM_BOOTSTRAP=0
                shift
                ;;
            --wireguard-system)
                RUN_WIREGUARD_SYSTEM_BOOTSTRAP=1
                shift
                ;;
            --skip-wireguard-package-install)
                RUN_WIREGUARD_PACKAGE_INSTALL=0
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
        "$APP_DIR/scripts"
        "$APP_DIR/storage/.pm2"
        "$APP_DIR/storage/app/license"
        "$APP_DIR/storage/app/radius"
        "$APP_DIR/storage/app/wireguard"
        "$APP_DIR/storage/framework/cache/data"
        "$APP_DIR/storage/framework/sessions"
        "$APP_DIR/storage/framework/views"
        "$APP_DIR/storage/logs"
        "$APP_DIR/tests/Unit"
        "$APP_DIR/wa-multi-session"
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

normalize_host() {
    local value="$1"

    value="${value#http://}"
    value="${value#https://}"
    value="${value%%/*}"
    value="${value%%:*}"

    printf '%s' "$value"
}

detect_primary_ip() {
    local detected_ip=""

    if [ -n "$SYSTEM_PRIMARY_IP" ]; then
        printf '%s' "$SYSTEM_PRIMARY_IP"
        return
    fi

    if command_exists hostname; then
        detected_ip="$(hostname -I 2>/dev/null | awk '{print $1}' || true)"
    fi

    if [ -z "$detected_ip" ] && command_exists ip; then
        detected_ip="$(ip route get 1.1.1.1 2>/dev/null | awk '/src/ {for (i = 1; i <= NF; i++) if ($i == "src") { print $(i + 1); exit }}' || true)"
    fi

    if [ -z "$detected_ip" ]; then
        detected_ip="127.0.0.1"
    fi

    printf '%s' "$detected_ip"
}

resolve_public_host() {
    local current_app_url
    local current_host

    if [ -n "$APP_URL_OVERRIDE" ]; then
        normalize_host "$APP_URL_OVERRIDE"
        return
    fi

    if [ -n "$APP_DOMAIN" ]; then
        normalize_host "$APP_DOMAIN"
        return
    fi

    current_app_url="$(read_env APP_URL)"
    current_host="$(normalize_host "$current_app_url")"

    if [ -n "$current_host" ] && [ "$current_host" != "localhost" ] && [ "$current_host" != "127.0.0.1" ]; then
        printf '%s' "$current_host"
        return
    fi

    detect_primary_ip
}

resolve_app_url() {
    local host

    if [ -n "$APP_URL_OVERRIDE" ]; then
        printf '%s' "$APP_URL_OVERRIDE"
        return
    fi

    host="$(resolve_public_host)"

    printf 'http://%s' "$host"
}

configure_environment() {
    normalize_sqlite_path
    local resolved_app_url
    local resolved_public_host

    resolved_app_url="$(resolve_app_url)"
    resolved_public_host="$(resolve_public_host)"

    set_env APP_URL "$resolved_app_url"

    set_env APP_NAME "Rafen Self-Hosted"
    set_env APP_ENV "production"
    set_env APP_DEBUG "false"
    set_env DB_CONNECTION "$DB_CONNECTION"
    set_env SESSION_DRIVER "file"
    set_env QUEUE_CONNECTION "sync"
    set_env CACHE_STORE "file"
    set_env LICENSE_SELF_HOSTED_ENABLED "true"
    set_env LICENSE_ENFORCE "true"
    if [ -n "$LICENSE_PUBLIC_KEY_VALUE" ]; then
        set_env LICENSE_PUBLIC_KEY "$LICENSE_PUBLIC_KEY_VALUE"
    fi
    set_env LICENSE_FILE_PATH "$APP_DIR/storage/app/license/rafen.lic"
    set_env LICENSE_MACHINE_ID_PATH "/etc/machine-id"
    set_env LICENSE_DEFAULT_GRACE_DAYS "21"
    set_env GENIEACS_NBI_URL "http://127.0.0.1:7557"
    set_env GENIEACS_UI_URL "http://127.0.0.1:3000"
    set_env GENIEACS_NBI_TIMEOUT "10"
    set_env GENIEACS_LOG_PATH "$APP_DIR/storage/logs/genieacs.log"
    set_env GENIEACS_CWMP_STATUS_COMMAND "systemctl is-active genieacs-cwmp"
    set_env GENIEACS_CWMP_RESTART_COMMAND "systemctl restart genieacs-cwmp"
    set_env GENIEACS_NBI_STATUS_COMMAND "systemctl is-active genieacs-nbi"
    set_env GENIEACS_NBI_RESTART_COMMAND "systemctl restart genieacs-nbi"
    set_env GENIEACS_FS_STATUS_COMMAND "systemctl is-active genieacs-fs"
    set_env GENIEACS_FS_RESTART_COMMAND "systemctl restart genieacs-fs"
    set_env WG_HOST "$resolved_public_host"
    set_env WG_SERVER_IP "10.0.0.1"
    set_env WG_SERVER_ADDRESS "10.0.0.1/24"
    set_env WG_LISTEN_PORT "51820"
    set_env WG_INTERFACE "wg0"
    set_env WG_CONFIG_PATH "$APP_DIR/storage/app/wireguard/wg0.conf"
    set_env WG_SERVER_PRIVATE_KEY_PATH "$APP_DIR/storage/app/wireguard/server_private.key"
    set_env WG_SERVER_PUBLIC_KEY_PATH "$APP_DIR/storage/app/wireguard/server_public.key"
    set_env WG_POOL_START "10.0.0.2"
    set_env WG_POOL_END "10.0.0.254"
    set_env WA_MULTI_SESSION_PATH "$APP_DIR/wa-multi-session"
    set_env WA_MULTI_SESSION_HOST "127.0.0.1"
    set_env WA_MULTI_SESSION_PORT "3100"
    set_env WA_MULTI_SESSION_PM2_HOME "$APP_DIR/storage/.pm2"
    set_env WA_MULTI_SESSION_LOG_FILE "$APP_DIR/storage/logs/wa-multi-session-pm2.log"
    set_env RADIUS_CLIENTS_PATH "$APP_DIR/storage/app/radius/clients-selfhosted.conf"
    set_env RADIUS_LOG_PATH "$APP_DIR/storage/logs/freeradius.log"

    if [ "$RUN_WIREGUARD_SYSTEM_BOOTSTRAP" = "1" ]; then
        if [ "$ALLOW_NON_ROOT" = "1" ]; then
            set_env WG_APPLY_COMMAND "$WG_SYNC_HELPER_PATH"
        else
            set_env WG_APPLY_COMMAND "sudo $WG_SYNC_HELPER_PATH"
        fi
    fi

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

require_license_public_key() {
    local public_key

    public_key="$(read_env LICENSE_PUBLIC_KEY)"

    if [ -n "$public_key" ]; then
        return
    fi

    fail "LICENSE_PUBLIC_KEY belum diisi. Jalankan installer dengan --license-public-key atau set env LICENSE_PUBLIC_KEY_VALUE agar verifikasi lisensi self-hosted bisa berjalan."
}

install_system_packages() {
    if [ "$RUN_SYSTEM_BOOTSTRAP" != "1" ]; then
        return
    fi

    command_exists "$APT_GET_BIN" || fail "apt-get tidak ditemukan: $APT_GET_BIN"

    run_command "$APT_GET_BIN" update
    run_command "$APT_GET_BIN" install -y \
        nginx \
        git \
        unzip \
        curl \
        composer \
        nodejs \
        npm \
        php \
        php-cli \
        php-fpm \
        php-sqlite3 \
        php-mysql \
        php-curl \
        php-mbstring \
        php-xml \
        php-zip \
        php-bcmath \
        php-intl \
        php-gd
}

detect_php_fpm_service() {
    if [ -n "$PHP_FPM_SERVICE" ]; then
        printf '%s' "$PHP_FPM_SERVICE"
        return
    fi

    local latest_dir
    latest_dir="$(find /etc/php -maxdepth 2 -type d -name fpm 2>/dev/null | sort -V | tail -1 || true)"

    if [ -n "$latest_dir" ]; then
        basename "$(dirname "$latest_dir")" | awk '{printf "php%s-fpm", $0}'
        return
    fi

    printf 'php-fpm'
}

detect_php_fpm_socket() {
    if [ -n "$PHP_FPM_SOCK" ]; then
        printf '%s' "$PHP_FPM_SOCK"
        return
    fi

    local latest_sock
    latest_sock="$(find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' 2>/dev/null | sort -V | tail -1 || true)"

    if [ -n "$latest_sock" ]; then
        printf '%s' "$latest_sock"
        return
    fi

    printf '/run/php/php-fpm.sock'
}

write_nginx_site_config() {
    if [ "$RUN_SYSTEM_BOOTSTRAP" != "1" ]; then
        return
    fi

    local host
    local php_fpm_socket
    local site_dir
    local enabled_dir

    host="$(resolve_public_host)"
    php_fpm_socket="$(detect_php_fpm_socket)"
    site_dir="$(dirname "$NGINX_SITE_AVAILABLE_PATH")"
    enabled_dir="$(dirname "$NGINX_SITE_ENABLED_PATH")"

    install_dir "$site_dir"
    install_dir "$enabled_dir"

    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] write nginx site %s for host %s\n' "$NGINX_SITE_AVAILABLE_PATH" "$host"
        return 0
    fi

    cat >"$NGINX_SITE_AVAILABLE_PATH" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${host} _;
    root ${APP_DIR}/public;
    index index.php index.html;

    client_max_body_size 32m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${php_fpm_socket};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
}

enable_nginx_site() {
    if [ "$RUN_SYSTEM_BOOTSTRAP" != "1" ]; then
        return
    fi

    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] enable nginx site %s -> %s\n' "$NGINX_SITE_AVAILABLE_PATH" "$NGINX_SITE_ENABLED_PATH"
        return 0
    fi

    ln -sfn "$NGINX_SITE_AVAILABLE_PATH" "$NGINX_SITE_ENABLED_PATH"

    if [ -n "$NGINX_DEFAULT_SITE_PATH" ] && [ -e "$NGINX_DEFAULT_SITE_PATH" ]; then
        rm -f "$NGINX_DEFAULT_SITE_PATH"
    fi
}

restart_web_services() {
    if [ "$RUN_SYSTEM_BOOTSTRAP" != "1" ]; then
        return
    fi

    local php_fpm_service

    php_fpm_service="$(detect_php_fpm_service)"

    if command_exists "$NGINX_BIN"; then
        run_command "$NGINX_BIN" -t
    fi

    run_command "$SYSTEMCTL_BIN" enable --now "$php_fpm_service"
    run_command "$SYSTEMCTL_BIN" restart "$php_fpm_service"
    run_command "$SYSTEMCTL_BIN" enable --now "$NGINX_SERVICE"
    run_command "$SYSTEMCTL_BIN" restart "$NGINX_SERVICE"
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
        run_command chown -R "$APP_USER:$APP_GROUP" \
            "$APP_DIR/storage" \
            "$APP_DIR/bootstrap/cache" \
            "$APP_DIR/database" \
            "$APP_DIR/scripts" \
            "$APP_DIR/wa-multi-session"

        if [ -f "$ENV_FILE" ]; then
            run_command chown "$APP_USER:$APP_GROUP" "$ENV_FILE"
        fi

        if [ -L "$APP_DIR/public/storage" ]; then
            run_command chown -h "$APP_USER:$APP_GROUP" "$APP_DIR/public/storage"
        fi
    fi

    if command_exists chmod; then
        run_command chmod -R ug+rwX \
            "$APP_DIR/storage" \
            "$APP_DIR/bootstrap/cache" \
            "$APP_DIR/database" \
            "$APP_DIR/scripts" \
            "$APP_DIR/wa-multi-session"

        if [ -f "$ENV_FILE" ]; then
            run_command chmod 640 "$ENV_FILE"
        fi
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

install_wireguard_packages() {
    if [ "$RUN_WIREGUARD_SYSTEM_BOOTSTRAP" != "1" ] || [ "$RUN_WIREGUARD_PACKAGE_INSTALL" != "1" ]; then
        return
    fi

    command_exists "$APT_GET_BIN" || fail "apt-get tidak ditemukan: $APT_GET_BIN"
    run_command "$APT_GET_BIN" update
    run_command "$APT_GET_BIN" install -y wireguard-tools
}

write_wireguard_sync_helper() {
    if [ "$RUN_WIREGUARD_SYSTEM_BOOTSTRAP" != "1" ]; then
        return
    fi

    local helper_dir
    helper_dir="$(dirname "$WG_SYNC_HELPER_PATH")"

    install_dir "$helper_dir"

    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] write helper %s\n' "$WG_SYNC_HELPER_PATH"
        return 0
    fi

    cat >"$WG_SYNC_HELPER_PATH" <<EOF
#!/usr/bin/env bash
set -Eeuo pipefail

APP_CONFIG_PATH="${APP_DIR}/storage/app/wireguard/${WG_SYSTEM_INTERFACE}.conf"
SYSTEM_CONFIG_PATH="${WG_SYSTEM_DIR}/${WG_SYSTEM_INTERFACE}.conf"
SYSTEM_SERVICE="${WG_SYSTEM_SERVICE}"

mkdir -p "${WG_SYSTEM_DIR}"
install -m 600 "\$APP_CONFIG_PATH" "\$SYSTEM_CONFIG_PATH"
"${SYSTEMCTL_BIN}" daemon-reload
"${SYSTEMCTL_BIN}" enable --now "\$SYSTEM_SERVICE"
"${SYSTEMCTL_BIN}" restart "\$SYSTEM_SERVICE"
EOF

    chmod 0755 "$WG_SYNC_HELPER_PATH"
}

write_wireguard_sudoers() {
    if [ "$RUN_WIREGUARD_SYSTEM_BOOTSTRAP" != "1" ] || [ "$ALLOW_NON_ROOT" = "1" ]; then
        return
    fi

    local sudoers_content
    sudoers_content="$APP_USER ALL=(root) NOPASSWD: $WG_SYNC_HELPER_PATH"

    if [ "$DRY_RUN" = "1" ]; then
        printf '[DRY-RUN] write sudoers %s => %s\n' "$WG_SUDOERS_PATH" "$sudoers_content"
        return 0
    fi

    printf '%s\n' "$sudoers_content" >"$WG_SUDOERS_PATH"
    chmod 0440 "$WG_SUDOERS_PATH"

    if command_exists "$VISUDO_BIN"; then
        run_command "$VISUDO_BIN" -cf "$WG_SUDOERS_PATH"
    fi
}

bootstrap_wireguard_system_service() {
    if [ "$RUN_WIREGUARD_SYSTEM_BOOTSTRAP" != "1" ]; then
        return
    fi

    install_dir "$WG_SYSTEM_DIR"
    install_wireguard_packages
    write_wireguard_sync_helper
    write_wireguard_sudoers
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
    require_license_public_key

    if [ "$RUN_MIGRATE" = "1" ]; then
        run_in_app "$PHP_BIN" artisan migrate --force --ansi
    fi

    run_in_app "$PHP_BIN" artisan storage:link --force --ansi

    run_in_app "$PHP_BIN" artisan wireguard:sync --ansi

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
    printf 'Public Host          : %s\n' "$(resolve_public_host)"
    printf 'Access URL           : %s\n' "$(resolve_app_url)"
    printf 'Vendor Directory     : %s\n' "$([ -d "$APP_DIR/vendor" ] && printf yes || printf no)"
    printf 'Bootstrap Cache Dir  : %s\n' "$([ -d "$APP_DIR/bootstrap/cache" ] && printf yes || printf no)"
    printf 'License Directory    : %s\n' "$([ -d "$APP_DIR/storage/app/license" ] && printf yes || printf no)"
    printf 'WireGuard Directory  : %s\n' "$([ -d "$APP_DIR/storage/app/wireguard" ] && printf yes || printf no)"
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
        printf 'License Public Key   : %s\n' "$([ -n "$(read_env LICENSE_PUBLIC_KEY)" ] && printf set || printf missing)"
        printf 'WG Apply Command     : %s\n' "$(read_env WG_APPLY_COMMAND)"
    fi

    printf 'WG System Bootstrap  : %s\n' "$RUN_WIREGUARD_SYSTEM_BOOTSTRAP"
    printf 'System Bootstrap     : %s\n' "$RUN_SYSTEM_BOOTSTRAP"
    printf 'Nginx Site           : %s\n' "$NGINX_SITE_AVAILABLE_PATH"
    printf 'PHP-FPM Service      : %s\n' "$(detect_php_fpm_service)"
    printf 'WG Helper Path       : %s\n' "$WG_SYNC_HELPER_PATH"
    printf 'WG System Service    : %s\n' "$WG_SYSTEM_SERVICE"
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
    install_system_packages
    composer_install
    npm_build
    write_nginx_site_config
    enable_nginx_site
    bootstrap_wireguard_system_service
    run_artisan_runtime_setup
    apply_basic_permissions
    restart_web_services

    info "Installer/deployment self-hosted selesai."
    show_status
}

main() {
    elevate_with_sudo "$@"
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
