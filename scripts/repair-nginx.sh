#!/bin/bash
# Repair nginx duplicate location / and restore Laravel routing on BlinkStudy.
# Run: bash scripts/repair-nginx.sh

set -uo pipefail

PUBLIC="/www/wwwroot/blinkstudy.in/public"
NGINX_DIR="/www/server/panel/vhost/nginx"
REWRITE_DIR="/www/server/panel/vhost/rewrite"
EXT_DIR="/www/server/panel/vhost/nginx/extension"

REWRITE_BLOCK='location / {
    try_files $uri $uri/ /index.php?$query_string;
}'

echo "========== NGINX REPAIR =========="

remove_location_slash() {
  local f="$1"
  [[ -f "$f" ]] || return
  perl -i -0777 -pe 's/\s*location\s+\/\s*\{[^}]*try_files[^}]*\}\s*//gs' "$f" 2>/dev/null || true
}

write_rewrite_once() {
  local f="$1"
  mkdir -p "$(dirname "$f")"
  printf '%s\n' "$REWRITE_BLOCK" > "$f"
}

shopt -s nullglob
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "$conf" .conf)
  [[ "$site" == *files* ]] && continue

  echo ""
  echo ">>> $site"

  # Backup
  cp -a "$conf" "${conf}.bak.repair"

  # 1) Strip ALL location / from main vhost (rewrite file will own it)
  remove_location_slash "$conf"

  # 2) Remove extension duplicates
  rm -rf "${EXT_DIR}/${site}" 2>/dev/null || true

  # 3) Fix root
  sed -i "s|^\s*root\s\+[^;]*;|    root ${PUBLIC};|g" "$conf"

  # 4) Find rewrite path from vhost (or default)
  rewrite="${REWRITE_DIR}/${site}.conf"
  if inc=$(grep -oE '/www/server/panel/vhost/rewrite/[^ ;]+\.conf' "$conf" 2>/dev/null | head -1); then
    rewrite="$inc"
  fi

  # 5) Clean rewrite file — single location / only
  remove_location_slash "$rewrite"
  write_rewrite_once "$rewrite"
  echo "  rewrite: $rewrite"

  # 6) Ensure exactly ONE include for this rewrite file
  if ! grep -qF "$rewrite" "$conf"; then
    if grep -q '#PHP-INFO-START' "$conf"; then
      sed -i "/#PHP-INFO-START/i\\    include ${rewrite};" "$conf"
    elif grep -q 'include enable-php' "$conf"; then
      sed -i "0,/include enable-php/s//include ${rewrite};\n    include enable-php/" "$conf"
    fi
    echo "  added rewrite include"
  fi

  # 7) PHP laravel handler
  sed -i 's|include enable-php-82\.conf;|include enable-php-82-laravel.conf;|g' "$conf"
  sed -i 's|include enable-php-82-wpfastcgi\.conf;|include enable-php-82-laravel.conf;|g' "$conf"

  # 8) Diagnose
  n=$(grep -c 'location /' "$conf" "$rewrite" 2>/dev/null | awk -F: '{s+=$2} END{print s+0}')
  echo "  location / count in vhost+rewrite: $n (want 1)"
done

# Remove public/admin blocker
[[ -d "${PUBLIC}/admin" ]] && rm -rf "${PUBLIC}/admin" && echo "Removed ${PUBLIC}/admin"

# open_basedir laravel php conf
if [[ -f /www/server/nginx/conf/enable-php-82.conf ]] && [[ ! -f /www/server/nginx/conf/enable-php-82-laravel.conf ]]; then
  cp /www/server/nginx/conf/enable-php-82.conf /www/server/nginx/conf/enable-php-82-laravel.conf
  sed -i 's|include fastcgi.conf;|include fastcgi.conf;\n    fastcgi_param PHP_ADMIN_VALUE "open_basedir=/www/wwwroot/blinkstudy.in/:/tmp/:/proc/:/sys/";|' \
    /www/server/nginx/conf/enable-php-82-laravel.conf
fi

echo ""
echo "==> nginx test"
if nginx -t 2>&1; then
  nginx -s reload
  /etc/init.d/php-fpm-82 reload 2>/dev/null || true
  echo "RELOAD OK"
else
  echo ""
  echo "Fallback: empty rewrite files, single location / in main vhost only..."
  for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
    site=$(basename "$conf" .conf)
    [[ "$site" == *files* ]] && continue
    rewrite="${REWRITE_DIR}/${site}.conf"
    echo -n > "$rewrite"
    remove_location_slash "$conf"
    if ! grep -q 'try_files' "$conf"; then
      sed -i "/#PHP-INFO-START/i\\    location / {\\n        try_files \$uri \$uri/ /index.php?\$query_string;\\n    }\\n" "$conf"
    fi
  done
  nginx -t 2>&1 || {
    echo "FAILED — paste output of:"
    echo "  grep -n 'location /' ${NGINX_DIR}/ad.blinkstudy.in.conf"
    echo "  cat ${REWRITE_DIR}/ad.blinkstudy.in.conf"
    exit 1
  }
  nginx -s reload
  echo "RELOAD OK (fallback mode)"
fi

echo ""
for path in /admin/login /admin/ai-settings /admin/users; do
  c=$(curl -skI -o /dev/null -w "%{http_code}" "https://ad.blinkstudy.in${path}")
  echo "  ${path} → HTTP ${c}"
done
