#!/usr/bin/env bash
#
# Αντίγραφο ασφαλείας βάσης DriveJob (Πακέτο 9)
#
# Χρήση:   bash scripts/backup-database.sh [φάκελος_προορισμού]
# Cron:    0 3 * * *  cd /path/to/drivejob && bash scripts/backup-database.sh >> logs/backup.log 2>&1
#
# Διαβάζει τα credentials από το .env — κανένα μυστικό στο script.
# Κρατά τα αντίγραφα των τελευταίων RETENTION_DAYS ημερών.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST="${1:-$ROOT_DIR/storage/backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"

# --- Ανάγνωση .env ---
if [[ ! -f "$ROOT_DIR/.env" ]]; then
    echo "❌ Δεν βρέθηκε .env στο $ROOT_DIR" >&2
    exit 1
fi
# shellcheck disable=SC1090
set -a; source <(grep -E '^(DB_|BACKUP_)' "$ROOT_DIR/.env" | sed 's/\r$//'); set +a

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-${DB_NAME:-drivejob}}"
DB_USER="${DB_USERNAME:-${DB_USER:-root}}"
DB_PASS="${DB_PASSWORD:-${DB_PASS:-}}"

mkdir -p "$DEST"
STAMP="$(date +%Y%m%d-%H%M)"
FILE="$DEST/drivejob-$STAMP.sql.gz"

echo "🗄️  [$(date '+%F %T')] Αντίγραφο βάσης «$DB_NAME» → $FILE"

# --defaults-file αποφεύγει το password στη γραμμή εντολών (ορατό σε ps)
CNF="$(mktemp)"
trap 'rm -f "$CNF"' EXIT
cat > "$CNF" <<EOF
[client]
host=$DB_HOST
port=$DB_PORT
user=$DB_USER
password=$DB_PASS
EOF

mysqldump --defaults-file="$CNF" \
    --single-transaction --quick --routines --triggers --events \
    --default-character-set=utf8mb4 \
    "$DB_NAME" | gzip -9 > "$FILE"

SIZE="$(du -h "$FILE" | cut -f1)"
echo "✅ Ολοκληρώθηκε — $SIZE"

# --- Καθαρισμός παλαιότερων ---
DELETED="$(find "$DEST" -name 'drivejob-*.sql.gz' -mtime "+$RETENTION_DAYS" -print -delete | wc -l | tr -d ' ')"
[[ "$DELETED" -gt 0 ]] && echo "🧹 Διαγράφηκαν $DELETED αντίγραφα παλαιότερα των $RETENTION_DAYS ημερών."

# --- Έλεγχος ακεραιότητας: το gzip πρέπει να ανοίγει και να έχει περιεχόμενο ---
if ! gzip -t "$FILE" 2>/dev/null; then
    echo "❌ ΠΡΟΣΟΧΗ: το αρχείο είναι κατεστραμμένο!" >&2
    exit 1
fi
TABLES="$(zcat "$FILE" | grep -c '^CREATE TABLE' || true)"
echo "🔎 Επαλήθευση: $TABLES πίνακες στο αντίγραφο."
[[ "$TABLES" -lt 10 ]] && { echo "❌ Πολύ λίγοι πίνακες — ελέγξτε το αντίγραφο!" >&2; exit 1; }

exit 0
