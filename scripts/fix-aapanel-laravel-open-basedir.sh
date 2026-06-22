#!/bin/bash
# Fix Laravel open_basedir on aaPanel/BT Panel.
# Problem: enable-php-*.conf sets open_basedir=$document_root/ (public only).
# Laravel needs vendor/, storage/, bootstrap/ above public/.
#
# Usage (as root on server):
#   cd /www/wwwroot/blinkstudy.in && bash scripts/fix-aapanel-laravel-open-basedir.sh

set -euo pipefail

PROJECT="/www/wwwroot/blinkstudy.in"
PUBLIC="${PROJECT}/public"
BASEDIR="${PROJECT}/:/tmp/:/proc/:/sys/"

PHP_VER="${PHP_VER:-}"
VHOST_DIR="/www/server/panel/vhost/nginx"

# Auto-detect PHP version from blinkstudy vhosts (default 82)
if [[ -z "${PHP_VER}" ]]; then
  PHP_VER=$(grep -ohE 'enable-php-([0-9]+)\.conf' "${VHOST_DIR}"/*blinkstudy*.conf 2>/dev/null | grep -oE '[0-9]+' | sort -u | tail -1)
  PHP_VER="${PHP_VER:-82}"
fi

ENABLE_PHP="/www/server/nginx/conf/enable-php-${PHP_VER}.conf"
LARAVEL_PHP="/www/server/nginx/conf/enable-php-${PHP_VER}-laravel.conf"

echo "==> Project: ${PROJECT}"
echo "==> PHP version: ${PHP_VER}"
echo "==> open_basedir: ${BASEDIR}"
  echo "ERROR: ${ENABLE_PHP} not found. Set PHP_VER manually (e.g. PHP_VER=82)."
  exit 1
fi

# 1) .user.ini (backup; nginx fastcgi usually overrides this)
chattr -i "${PUBLIC}/.user.ini" 2>/dev/null || true
cat > "${PUBLIC}/.user.ini" <<EOF
open_basedir=${BASEDIR}
EOF
chattr +i "${PUBLIC}/.user.ini" 2>/dev/null || true
echo "==> Updated ${PUBLIC}/.user.ini"

# 2) Laravel-specific PHP handler: override PHP_ADMIN_VALUE after fastcgi.conf
cp -f "${ENABLE_PHP}" "${LARAVEL_PHP}"
if ! grep -q 'enable-php-.*-laravel' "${LARAVEL_PHP}"; then
  sed -i "s|include fastcgi.conf;|include fastcgi.conf;\n    fastcgi_param PHP_ADMIN_VALUE \"open_basedir=${BASEDIR}\";|" "${LARAVEL_PHP}"
fi
echo "==> Created ${LARAVEL_PHP}"

# 3) Point all blinkstudy vhosts at the Laravel PHP config
shopt -s nullglob
for conf in "${VHOST_DIR}"/*blinkstudy*.conf; do
  if grep -q "include enable-php-${PHP_VER}.conf;" "${conf}"; then
    sed -i "s|include enable-php-${PHP_VER}.conf;|include enable-php-${PHP_VER}-laravel.conf;|g" "${conf}"
    echo "==> Patched vhost: $(basename "${conf}")"
  elif grep -q "include enable-php-${PHP_VER}-laravel.conf;" "${conf}"; then
    echo "==> Already patched: $(basename "${conf}")"
  else
    echo "==> SKIP (no enable-php-${PHP_VER}.conf): $(basename "${conf}")"
  fi
done

# 4) Reload
nginx -t
nginx -s reload
if [[ -x "/etc/init.d/php-fpm-${PHP_VER}" ]]; then
  /etc/init.d/php-fpm-${PHP_VER} reload
fi

echo ""
echo "Done. Test:"
echo "  curl -sI https://ad.blinkstudy.in/admin/homepage-settings | head -3"
echo "  curl -sI https://blinkstudy.in/ | head -3"
echo ""
echo "If still broken: aaPanel → each site → Site directory → UNCHECK 'Anti-XSS attack (open_basedir)'"
