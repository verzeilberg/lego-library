#!/usr/bin/env bash
# Production deployment script for lego-library-api (Symfony 6.4)
# Usage: ./deploy.sh [--skip-pull] [--skip-migrations]

set -euo pipefail

# ─────────────────────────────────────────────
# Config
# ─────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
GIT_BRANCH="${GIT_BRANCH:-master}"
WEB_USER="${WEB_USER:-www-data}"
SKIP_PULL=false
SKIP_MIGRATIONS=false

for arg in "$@"; do
  case $arg in
    --skip-pull)       SKIP_PULL=true ;;
    --skip-migrations) SKIP_MIGRATIONS=true ;;
  esac
done

# ─────────────────────────────────────────────
# Helpers
# ─────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }

console() { APP_ENV=prod APP_DEBUG=0 "$PHP_BIN" "$APP_DIR/bin/console" "$@"; }

# ─────────────────────────────────────────────
# 1. Pre-flight checks
# ─────────────────────────────────────────────
info "Running pre-flight checks..."

command -v "$PHP_BIN"      >/dev/null 2>&1 || error "PHP not found. Set PHP_BIN or install PHP 8.2+."
command -v "$COMPOSER_BIN" >/dev/null 2>&1 || error "Composer not found. Set COMPOSER_BIN or install Composer."
command -v git             >/dev/null 2>&1 || error "Git not found."

PHP_VERSION=$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
if [[ "$(echo "$PHP_VERSION < 8.2" | bc -l)" == "1" ]]; then
  error "PHP 8.2+ required, found $PHP_VERSION."
fi
success "PHP $PHP_VERSION"

# .env.local must exist with real production values
[[ -f "$APP_DIR/.env.local" ]] || error ".env.local not found. Create it with production values before deploying."

# Check required env vars are set in .env.local
for var in \
    APP_SECRET DATABASE_URL JWT_PASSPHRASE \
    EMAIL_ENCRYPTION_KEY EMAIL_HMAC_KEY \
    REBRICKABLE_API_KEY REBRICKABLE_API_URL \
    SIGHTENGINE_USER SIGHTENGINE_SECRET \
    APP_TITLE FRONTEND_URL MAILER_DSN; do
  grep -q "^${var}=" "$APP_DIR/.env.local" || \
    error "${var} is not set in .env.local."
done
success "Environment file OK"

# JWT keys
[[ -f "$APP_DIR/config/jwt/private.pem" ]] || error "JWT private key missing: config/jwt/private.pem"
[[ -f "$APP_DIR/config/jwt/public.pem"  ]] || error "JWT public key missing:  config/jwt/public.pem"
success "JWT keys present"

# ─────────────────────────────────────────────
# 2. Pull latest code
# ─────────────────────────────────────────────
if [[ "$SKIP_PULL" == false ]]; then
  info "Pulling latest code from origin/$GIT_BRANCH..."
  cd "$APP_DIR"
  git fetch origin
  git checkout "$GIT_BRANCH"
  git pull origin "$GIT_BRANCH"
  success "Code updated"
else
  warn "Skipping git pull (--skip-pull)"
fi

cd "$APP_DIR"

# ─────────────────────────────────────────────
# 3. Ensure APP_ENV=prod in .env.local
# ─────────────────────────────────────────────
info "Enforcing APP_ENV=prod..."
if grep -q "^APP_ENV=" "$APP_DIR/.env.local"; then
  sed -i 's/^APP_ENV=.*/APP_ENV=prod/' "$APP_DIR/.env.local"
else
  echo "APP_ENV=prod" >> "$APP_DIR/.env.local"
fi
# Also make sure APP_DEBUG is off
if grep -q "^APP_DEBUG=" "$APP_DIR/.env.local"; then
  sed -i 's/^APP_DEBUG=.*/APP_DEBUG=0/' "$APP_DIR/.env.local"
else
  echo "APP_DEBUG=0" >> "$APP_DIR/.env.local"
fi
success "APP_ENV=prod, APP_DEBUG=0"

# ─────────────────────────────────────────────
# 4. Composer install (no dev dependencies)
# ─────────────────────────────────────────────
info "Installing Composer dependencies..."
APP_ENV=prod "$COMPOSER_BIN" install \
  --prefer-dist \
  --no-dev \
  --no-interaction \
  --optimize-autoloader \
  --classmap-authoritative
success "Composer install done"

# ─────────────────────────────────────────────
# 5. Create required upload directories
# ─────────────────────────────────────────────
info "Creating upload directories..."
mkdir -p \
  "$APP_DIR/public/media/lego/parts" \
  "$APP_DIR/public/media/profiel" \
  "$APP_DIR/public/media/profile" \
  "$APP_DIR/public/uploads/images" \
  "$APP_DIR/var/cache" \
  "$APP_DIR/var/log"
success "Directories ready"

# ─────────────────────────────────────────────
# 6. Clear and warm up the cache
# ─────────────────────────────────────────────
info "Clearing cache..."
console cache:clear --no-warmup
info "Warming up cache..."
console cache:warmup
success "Cache warmed up"

# ─────────────────────────────────────────────
# 7. Install assets
# ─────────────────────────────────────────────
info "Installing assets..."
console assets:install public --symlink --relative 2>/dev/null || \
  console assets:install public
success "Assets installed"

# ─────────────────────────────────────────────
# 8. Database migrations
# ─────────────────────────────────────────────
if [[ "$SKIP_MIGRATIONS" == false ]]; then
  info "Running database migrations..."
  console doctrine:migrations:migrate --no-interaction --allow-no-migration
  success "Migrations done"
else
  warn "Skipping migrations (--skip-migrations)"
fi

# ─────────────────────────────────────────────
# 9. Restart Messenger consumer
# ─────────────────────────────────────────────
info "Restarting Messenger consumer..."
if systemctl is-active --quiet lego-messenger 2>/dev/null; then
  systemctl restart lego-messenger && success "lego-messenger (systemd) restarted"
elif command -v supervisorctl >/dev/null 2>&1 && supervisorctl status lego-messenger 2>/dev/null | grep -q RUNNING; then
  supervisorctl restart lego-messenger && success "lego-messenger (supervisor) restarted"
else
  warn "Messenger consumer service not found — start manually: php bin/console messenger:consume async --time-limit=3600"
fi

# ─────────────────────────────────────────────
# 10. File permissions
# ─────────────────────────────────────────────
info "Setting file permissions..."
# Use setfacl if available (preferred), otherwise fall back to chmod
if command -v setfacl >/dev/null 2>&1; then
  setfacl -R  -m u:"$WEB_USER":rwX -m u:"$(whoami)":rwX \
    "$APP_DIR/var" \
    "$APP_DIR/public/media" \
    "$APP_DIR/public/uploads"
  setfacl -dR -m u:"$WEB_USER":rwX -m u:"$(whoami)":rwX \
    "$APP_DIR/var" \
    "$APP_DIR/public/media" \
    "$APP_DIR/public/uploads"
  success "Permissions set via setfacl"
else
  chmod -R 775 "$APP_DIR/var" "$APP_DIR/public/media" "$APP_DIR/public/uploads"
  chown -R "$(whoami)":"$WEB_USER" "$APP_DIR/var" "$APP_DIR/public/media" "$APP_DIR/public/uploads" 2>/dev/null || \
    warn "Could not chown (run as root or add to $WEB_USER group)"
  success "Permissions set via chmod"
fi

# ─────────────────────────────────────────────
# 11. Reload web server & PHP-FPM
# ─────────────────────────────────────────────
info "Reloading services..."
if systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
  systemctl reload php8.2-fpm && success "php8.2-fpm reloaded"
elif systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
  systemctl reload php8.3-fpm && success "php8.3-fpm reloaded"
else
  warn "PHP-FPM service not found or not running — reload manually if needed"
fi

if systemctl is-active --quiet nginx 2>/dev/null; then
  nginx -t && systemctl reload nginx && success "nginx reloaded"
elif systemctl is-active --quiet apache2 2>/dev/null; then
  systemctl reload apache2 && success "apache2 reloaded"
else
  warn "No web server service found — reload manually if needed"
fi

# ─────────────────────────────────────────────
# Done
# ─────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Deployment completed successfully  ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════╝${NC}"
