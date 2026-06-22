#!/bin/bash
# One-shot nginx fix for BlinkStudy Laravel (fixes nginx 404 on ad.* admin routes).
# Run as root: bash scripts/nuclear-fix-nginx.sh

set -uo pipefail

PROJECT="/www/wwwroot/blinkstudy.in"
PUBLIC="${PROJECT}/public"
NGINX_DIR="/www/server/panel/vhost/nginx"
REWRITE_DIR="/www/server/panel/vhost/rewrite"

LARAVEL_REWRITE='location / {
    try_files $uri $uri/ /index.php?$query_string;
}'

echo "========== NUCLEAR NGINX FIX =========="

# Kill stray admin folder (nginx serves this instead of Laravel)
if [[ -d "${PUBLIC}/admin" ]]; then
  rm -rf "${PUBLIC}/admin"
  echo "REMOVED ${PUBLIC}/admin (was blocking /admin/* routes)"
fi

# Fix every blinkstudy vhost
shopt -s nullglob
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "${conf}" .conf)
  [[ "${site}" == *files* ]] && continue

  echo ""
  echo ">>> ${site}"
  cp -a "${conf}" "${conf}.bak.$(date +%Y%m%d%H%M%S)"

  # Root = Laravel public
  sed -i "s|^\s*root\s\+[^;]*;|    root ${PUBLIC};|g" "${conf}"

  # Rewrite file (match include path in vhost if set)
  rewrite="${REWRITE_DIR}/${site}.conf"
  if inc=$(grep -oE '/www/server/panel/vhost/rewrite/[^ ;]+\.conf' "${conf}" 2>/dev/null | head -1); then
    rewrite="${inc}"
  fi
  printf '%s\n' "${LARAVEL_REWRITE}" > "${rewrite}"
  echo "  rewrite: ${rewrite}"

  # Ensure rewrite is included in vhost
  if ! grep -q 'vhost/rewrite/' "${conf}"; then
    if grep -q '#PHP-INFO-START' "${conf}"; then
      sed -i "/#PHP-INFO-START/i\\    include ${rewrite};" "${conf}"
    else
      sed -i "/include enable-php/i\\    include ${rewrite};" "${conf}"
    fi
    echo "  added rewrite include to vhost"
  fi

  # Inline try_files (belt + suspenders) before PHP block
  if ! grep -q 'try_files' "${conf}"; then
  if grep -q '#PHP-INFO-START' "${conf}"; then
    sed -i "/#PHP-INFO-START/i\\
    location / {\\
        try_files \$uri \$uri/ /index.php?\$query_string;\\
    }\\
" "${conf}"
    echo "  added inline location / try_files"
  fi
  fi

  # PHP 82 laravel handler
  sed -i 's|include enable-php-82\.conf;|include enable-php-82-laravel.conf;|g' "${conf}"
  sed -i 's|include enable-php-82-wpfastcgi\.conf;|include enable-php-82-laravel.conf;|g' "${conf}"

  # Remove .user.ini locks
  for ini in "${PROJECT}/.user.ini" "${PUBLIC}/.user.ini"; do
    chattr -i "${ini}" 2>/dev/null || true
    rm -f "${ini}"
  done
done

# Ensure enable-php-82-laravel.conf exists
if [[ ! -f /www/server/nginx/conf/enable-php-82-laravel.conf ]]; then
  cp /www/server/nginx/conf/enable-php-82.conf /www/server/nginx/conf/enable-php-82-laravel.conf
  sed -i 's|include fastcgi.conf;|include fastcgi.conf;\n    fastcgi_param PHP_ADMIN_VALUE "open_basedir=/www/wwwroot/blinkstudy.in/:/tmp/:/proc/:/sys/";|' \
    /www/server/nginx/conf/enable-php-82-laravel.conf
fi

echo ""
nginx -t && nginx -s reload
/etc/init.d/php-fpm-82 reload 2>/dev/null || true

echo ""
echo "========== TEST (must NOT say nginx 404) =========="
for path in / /admin/login /admin/ai-settings /admin/homepage-settings /admin/users; do
  code=$(curl -skI -o /dev/null -w "%{http_code}" "https://ad.blinkstudy.in${path}")
  body=$(curl -sk "https://ad.blinkstudy.in${path}" 2>/dev/null | head -c 60 | tr '\n' ' ')
  if [[ "${code}" == "404" ]] && echo "${body}" | grep -qi nginx; then
    echo "FAIL ${path} → nginx 404"
  else
    echo "OK   ${path} → HTTP ${code}"
  fi
done

echo ""
echo "If browser still 404: Ctrl+Shift+R hard refresh, or purge Cloudflare cache."
echo "aaPanel: ad.blinkstudy.in → Directory → Anti-XSS OFF → Save"
