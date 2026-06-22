#!/bin/bash
# Fix aaPanel nginx 404 for BlinkStudy Laravel — no duplicate location / blocks.
# Run as root: bash scripts/nuclear-fix-nginx.sh

set -uo pipefail

PROJECT="/www/wwwroot/blinkstudy.in"
PUBLIC="${PROJECT}/public"
NGINX_DIR="/www/server/panel/vhost/nginx"
REWRITE_DIR="/www/server/panel/vhost/rewrite"
EXT_DIR="/www/server/panel/vhost/nginx/extension"

LARAVEL_REWRITE='location / {
    try_files $uri $uri/ /index.php?$query_string;
}'

echo "========== NUCLEAR NGINX FIX v2 =========="

# Stray public/admin folder blocks all /admin/* routes
if [[ -d "${PUBLIC}/admin" ]]; then
  rm -rf "${PUBLIC}/admin"
  echo "REMOVED ${PUBLIC}/admin"
fi

strip_inline_location() {
  local conf="$1"
  # Remove inline location / { try_files ... } blocks added by earlier script runs
  perl -i -0777 -pe 's/\s*location\s+\/\s*\{\s*try_files\s+\$uri\s+\$uri\/\s+\/index\.php\?\$query_string;\s*\}\s*//gs' "${conf}" 2>/dev/null || true
}

shopt -s nullglob
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "${conf}" .conf)
  [[ "${site}" == *files* ]] && continue

  echo ""
  echo ">>> ${site}"

  strip_inline_location "${conf}"

  # Remove extension duplicates (second location /)
  rm -rf "${EXT_DIR}/${site}" 2>/dev/null || true

  sed -i "s|^\s*root\s\+[^;]*;|    root ${PUBLIC};|g" "${conf}"

  rewrite="${REWRITE_DIR}/${site}.conf"
  if inc=$(grep -oE '/www/server/panel/vhost/rewrite/[^ ;]+\.conf' "${conf}" 2>/dev/null | head -1); then
    rewrite="${inc}"
  fi

  # ONLY rewrite file gets location / — never duplicate in main vhost
  printf '%s\n' "${LARAVEL_REWRITE}" > "${rewrite}"
  echo "  rewrite: ${rewrite}"

  if ! grep -q 'vhost/rewrite/' "${conf}"; then
    if grep -q '#PHP-INFO-START' "${conf}"; then
      sed -i "/#PHP-INFO-START/i\\    include ${rewrite};" "${conf}"
    elif grep -q 'include enable-php' "${conf}"; then
      sed -i "/include enable-php/i\\    include ${rewrite};" "${conf}"
    elif grep -q '#REWRITE-END' "${conf}"; then
      sed -i "/#REWRITE-END/i\\    include ${rewrite};" "${conf}"
    fi
    echo "  added rewrite include"
  fi

  sed -i 's|include enable-php-82\.conf;|include enable-php-82-laravel.conf;|g' "${conf}"
  sed -i 's|include enable-php-82-wpfastcgi\.conf;|include enable-php-82-laravel.conf;|g' "${conf}"

  for ini in "${PROJECT}/.user.ini" "${PUBLIC}/.user.ini"; do
    chattr -i "${ini}" 2>/dev/null || true
    rm -f "${ini}"
  done
done

# enable-php-82-laravel.conf
if [[ ! -f /www/server/nginx/conf/enable-php-82-laravel.conf ]]; then
  cp /www/server/nginx/conf/enable-php-82.conf /www/server/nginx/conf/enable-php-82-laravel.conf
  sed -i 's|include fastcgi.conf;|include fastcgi.conf;\n    fastcgi_param PHP_ADMIN_VALUE "open_basedir=/www/wwwroot/blinkstudy.in/:/tmp/:/proc/:/sys/";|' \
    /www/server/nginx/conf/enable-php-82-laravel.conf
fi

echo ""
if nginx -t; then
  nginx -s reload
  /etc/init.d/php-fpm-82 reload 2>/dev/null || true
  echo "nginx reloaded OK"
else
  echo ""
  echo "ERROR: nginx test failed. Restore backup:"
  echo "  ls -t ${NGINX_DIR}/ad.blinkstudy.in.conf.bak.* | head -1"
  echo "  cp <backup> ${NGINX_DIR}/ad.blinkstudy.in.conf"
  exit 1
fi

echo ""
echo "========== TEST =========="
for path in / /admin/login /admin/ai-settings /admin/homepage-settings /admin/users; do
  code=$(curl -skI -o /dev/null -w "%{http_code}" "https://ad.blinkstudy.in${path}" 2>/dev/null || echo ERR)
  echo "  ${path} → HTTP ${code}"
done
echo ""
echo "302 = OK (login redirect). 200 = OK. 404 = still broken."
