#!/bin/bash
# Fix aaPanel nginx 404 + open_basedir for all BlinkStudy Laravel subdomains.
#
# Run as root on server:
#   cd /www/wwwroot/blinkstudy.in
#   git pull origin main
#   bash scripts/fix-aapanel-blinkstudy-all.sh

set -euo pipefail

PROJECT="/www/wwwroot/blinkstudy.in"
PUBLIC="${PROJECT}/public"
NGINX_DIR="/www/server/panel/vhost/nginx"
REWRITE_DIR="/www/server/panel/vhost/rewrite"

LARAVEL_REWRITE='location / {
    try_files $uri $uri/ /index.php?$query_string;
}'

echo "=============================================="
echo " BlinkStudy aaPanel fix (nginx 404 + PHP paths)"
echo "=============================================="
echo ""

if [[ ! -f "${PUBLIC}/index.php" ]]; then
  echo "ERROR: Laravel public not found at ${PUBLIC}/index.php"
  exit 1
fi

echo "==> STEP 1: Diagnose current vhosts"
echo ""
shopt -s nullglob
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "${conf}" .conf)
  echo "--- ${site} ---"
  grep -E '^\s*root ' "${conf}" || echo "  (no root line)"
  grep 'rewrite/' "${conf}" || echo "  (no rewrite include)"
  rew="${REWRITE_DIR}/${site}.conf"
  if [[ -f "${rew}" ]] && [[ -s "${rew}" ]]; then
    if grep -q try_files "${rew}"; then
      echo "  rewrite file: OK (has try_files)"
    else
      echo "  rewrite file: EXISTS but missing try_files"
    fi
  else
    echo "  rewrite file: MISSING or EMPTY"
  fi
  echo ""
done

echo "==> STEP 2: Set document root to ${PUBLIC} for all blinkstudy sites"
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "${conf}" .conf)
  # files.* serves storage — skip
  if [[ "${site}" == files.blinkstudy.in ]] || [[ "${site}" == files_blinkstudy_in ]]; then
    echo "  SKIP root (files CDN): ${site}"
    continue
  fi
  if grep -q "root ${PUBLIC};" "${conf}"; then
    echo "  root already OK: ${site}"
  else
    sed -i "s|root [^;]*;|root ${PUBLIC};|g" "${conf}"
    echo "  FIXED root: ${site}"
  fi
done
echo ""

echo "==> STEP 3: Install Laravel URL rewrite (try_files -> index.php)"
for conf in "${NGINX_DIR}"/*blinkstudy*.conf; do
  site=$(basename "${conf}" .conf)
  if [[ "${site}" == files.blinkstudy.in ]] || [[ "${site}" == files_blinkstudy_in ]]; then
    continue
  fi
  rew="${REWRITE_DIR}/${site}.conf"
  if [[ -f "${rew}" ]] && grep -q try_files "${rew}"; then
    echo "  rewrite OK: ${site}"
  else
    printf '%s\n' "${LARAVEL_REWRITE}" > "${rew}"
    echo "  WROTE rewrite: ${rew}"
  fi
  # Ensure vhost includes rewrite file
  if ! grep -q "rewrite/${site}.conf" "${conf}"; then
    echo "  WARNING: ${conf} may not include rewrite/${site}.conf — fix in aaPanel → URL rewrite"
  fi
done
echo ""

echo "==> STEP 4: Remove stray public/admin folders (cause nginx 404)"
for dir in \
  "${PUBLIC}/admin" \
  "/www/wwwroot/ad.blinkstudy.in/public/admin" \
  "/www/wwwroot/ad.blinkstudy.in/admin"; do
  if [[ -d "${dir}" ]]; then
    rm -rf "${dir}"
    echo "  REMOVED: ${dir}"
  fi
done
echo ""

echo "==> STEP 5: Fix open_basedir (vendor/storage access)"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
bash "${SCRIPT_DIR}/fix-aapanel-laravel-open-basedir.sh"
echo ""

echo "==> STEP 6: Test URLs"
test_url() {
  local url="$1"
  local code
  code=$(curl -sI -o /dev/null -w "%{http_code}" "${url}" 2>/dev/null || echo "ERR")
  local body
  body=$(curl -s "${url}" 2>/dev/null | head -c 120 | tr '\n' ' ')
  if echo "${body}" | grep -qi "open_basedir\|Fatal error"; then
    echo "  FAIL ${url} → HTTP ${code} (PHP error in body)"
  elif [[ "${code}" == "404" ]] && echo "${body}" | grep -qi "nginx"; then
    echo "  FAIL ${url} → HTTP 404 (nginx — not Laravel)"
  else
    echo "  OK   ${url} → HTTP ${code}"
  fi
}

test_url "https://blinkstudy.in/"
test_url "https://ad.blinkstudy.in/admin/login"
test_url "https://ad.blinkstudy.in/admin/homepage-settings"
test_url "https://ad.blinkstudy.in/admin/users"
echo ""
echo "Done. Expected: 200 or 302 on admin URLs (not nginx 404, not PHP errors)."
echo ""
echo "If ad.* still 404: aaPanel → ad.blinkstudy.in → Settings"
echo "  Document root: ${PUBLIC}"
echo "  URL rewrite:   Laravel"
echo "  Site directory → UNCHECK Anti-XSS (open_basedir)"
