#!/bin/bash
# Fix aaPanel nginx 404 + open_basedir for BlinkStudy Laravel (all subdomains).
#
#   cd /www/wwwroot/blinkstudy.in && git pull && bash scripts/fix-aapanel-blinkstudy-all.sh

set -euo pipefail

PROJECT="/www/wwwroot/blinkstudy.in"
PUBLIC="${PROJECT}/public"
NGINX_DIR="/www/server/panel/vhost/nginx"
REWRITE_DIR="/www/server/panel/vhost/rewrite"
EXT_DIR="/www/server/panel/vhost/nginx/extension"

LARAVEL_LOCATION='location / {
    try_files $uri $uri/ /index.php?$query_string;
}'

echo "=============================================="
echo " BlinkStudy aaPanel fix v2"
echo "=============================================="

if [[ ! -f "${PUBLIC}/index.php" ]]; then
  echo "ERROR: Missing ${PUBLIC}/index.php"
  exit 1
fi

is_files_site() {
  local site="$1"
  [[ "${site}" == *files* ]]
}

write_laravel_rewrite() {
  local file="$1"
  mkdir -p "$(dirname "${file}")"
  printf '%s\n' "${LARAVEL_LOCATION}" > "${file}"
}

strip_inline_location() {
  local conf="$1"
  perl -i -0777 -pe 's/\s*location\s+\/\s*\{\s*try_files\s+\$uri\s+\$uri\/\s+\/index\.php\?\$query_string;\s*\}\s*//gs' "${conf}" 2>/dev/null || true
}

fix_vhost() {
  local conf="$1"
  local site
  site=$(basename "${conf}" .conf)

  if is_files_site "${site}"; then
    echo "  SKIP files site: ${site}"
    return
  fi

  echo ""
  echo ">>> Fixing: ${site}"

  # 1) Document root must be Laravel public/
  if grep -q "root ${PUBLIC};" "${conf}"; then
    echo "  root: already ${PUBLIC}"
  else
    sed -i "s|^\(\s*root\s\+\)[^;]*;|\1${PUBLIC};|g" "${conf}"
    echo "  root: set to ${PUBLIC}"
  fi

  # 2) Rewrite file — use path from include line if present, else default name
  local rewrite_file="${REWRITE_DIR}/${site}.conf"
  if grep -q 'include.*vhost/rewrite/' "${conf}"; then
    rewrite_file=$(grep -oE '/www/server/panel/vhost/rewrite/[^ ;]+\.conf' "${conf}" | head -1)
  fi
  write_laravel_rewrite "${rewrite_file}"
  echo "  rewrite file: ${rewrite_file}"

  # 3) Ensure vhost includes rewrite (add before PHP block if missing)
  if grep -q 'include.*vhost/rewrite/' "${conf}"; then
    echo "  rewrite include: present"
  else
    if grep -q '#PHP-INFO-START' "${conf}"; then
      sed -i "/#PHP-INFO-START/i\\    include ${rewrite_file};" "${conf}"
    else
      sed -i "/include enable-php/i\\    include ${rewrite_file};" "${conf}"
    fi
    echo "  rewrite include: ADDED to vhost"
  fi

  # 4) Extension fallback — only if rewrite include missing (avoid duplicate location /)
  local ext_site_dir="${EXT_DIR}/${site}"
  if ! grep -q 'vhost/rewrite/' "${conf}"; then
    mkdir -p "${ext_site_dir}"
    write_laravel_rewrite "${ext_site_dir}/00-laravel.conf"
    echo "  extension: ${ext_site_dir}/00-laravel.conf"
    if grep -q '#REWRITE-END' "${conf}"; then
      sed -i "/#REWRITE-END/a\\    include ${ext_site_dir}/*.conf;" "${conf}"
      echo "  extension include: ADDED"
    fi
  else
    rm -rf "${ext_site_dir}" 2>/dev/null || true
    echo "  extension: skipped (using rewrite file)"
  fi

  # Remove inline location / if rewrite file is used (prevents duplicate location /)
  strip_inline_location "${conf}"

  # 5) Inline try_files ONLY when no rewrite include and no try_files anywhere
  if ! grep -q 'vhost/rewrite/' "${conf}" && ! grep -q 'try_files' "${conf}" && ! grep -q 'try_files' "${rewrite_file}" 2>/dev/null; then
    if grep -q '#PHP-INFO-START' "${conf}"; then
      sed -i "/#PHP-INFO-START/i\\    location / {\\n        try_files \$uri \$uri/ /index.php?\$query_string;\\n    }\\n" "${conf}"
      echo "  inline location /: ADDED"
    fi
  fi

  # 6) Remove .user.ini open_basedir lock (aaPanel Anti-XSS) — also turn OFF in panel UI
  for ini in "${PROJECT}/.user.ini" "${PUBLIC}/.user.ini"; do
    if [[ -f "${ini}" ]]; then
      chattr -i "${ini}" 2>/dev/null || true
      rm -f "${ini}"
      echo "  removed: ${ini}"
    fi
  done
}

echo ""
echo "==> STEP 1: Diagnose"
shopt -s nullglob
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "${conf}" .conf)
  echo "--- ${site} ---"
  grep -E '^\s*root ' "${conf}" || true
  grep -E 'rewrite/|extension/' "${conf}" || echo "  NO rewrite/extension include"
  if grep -q try_files "${conf}" 2>/dev/null; then
    echo "  try_files: in vhost"
  elif [[ -f "${REWRITE_DIR}/${site}.conf" ]] && grep -q try_files "${REWRITE_DIR}/${site}.conf" 2>/dev/null; then
    echo "  try_files: in rewrite file"
  else
    echo "  NO try_files found"
  fi
done

echo ""
echo "==> STEP 2: Patch all blinkstudy vhosts"
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  fix_vhost "${conf}"
done

echo ""
echo "==> STEP 3: Remove stray public/admin directories"
for dir in "${PUBLIC}/admin" /www/wwwroot/ad.blinkstudy.in/public/admin /www/wwwroot/ad.blinkstudy.in/admin; do
  [[ -d "${dir}" ]] && rm -rf "${dir}" && echo "  removed ${dir}"
done

echo ""
echo "==> STEP 4: open_basedir (vendor/storage)"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "${SCRIPT_DIR}/fix-aapanel-laravel-open-basedir.sh" ]]; then
  bash "${SCRIPT_DIR}/fix-aapanel-laravel-open-basedir.sh" || echo "  (open_basedir script warning — continue)"
fi

echo ""
echo "==> STEP 5: nginx test + reload"
nginx -t
nginx -s reload
PHP_VER=$(ls /www/server/nginx/conf/enable-php-*.conf 2>/dev/null | grep -oE '[0-9]+' | tail -1 || echo 82)
[[ -x "/etc/init.d/php-fpm-${PHP_VER}" ]] && /etc/init.d/php-fpm-${PHP_VER} reload

echo ""
echo "==> STEP 6: Tests"
test_url() {
  local url="$1"
  local code body
  code=$(curl -skI -o /dev/null -w "%{http_code}" "${url}" || echo ERR)
  body=$(curl -sk "${url}" 2>/dev/null | head -c 80)
  if echo "${body}" | grep -qi 'open_basedir\|Fatal error'; then
    echo "  FAIL ${url} → PHP error (turn OFF Anti-XSS in aaPanel)"
  elif [[ "${code}" == "404" ]] && echo "${body}" | grep -qi 'nginx'; then
    echo "  FAIL ${url} → nginx 404 (rewrite still broken)"
  else
    echo "  OK   ${url} → HTTP ${code}"
  fi
}
test_url "https://blinkstudy.in/"
test_url "https://blinkstudy.in/admin/users"
test_url "https://ad.blinkstudy.in/admin/login"
test_url "https://ad.blinkstudy.in/admin/homepage-settings"
test_url "https://ad.blinkstudy.in/admin/ai-settings"

echo ""
echo "=============================================="
echo " MANUAL (required if still FAIL):"
echo " aaPanel → ad.blinkstudy.in + blinkstudy.in"
echo "   Directory → Anti-XSS OFF → Save"
echo "   URL rewrite → Laravel → Save"
echo "=============================================="
