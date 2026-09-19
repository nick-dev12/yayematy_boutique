#!/usr/bin/env bash
set -euo pipefail

STAMP="${1:-$(date +%Y%m%d_%H%M%S)}"
PROJECT_DIR="/home/colobanes/yayematy.com"
BACKUP_DIR="/home/colobanes/backups_yayematy_${STAMP}"
mkdir -p "$BACKUP_DIR"

read_db_var() {
  local key="$1"
  php -r "
    require '${PROJECT_DIR}/conn/conn.php';
    echo isset(\$${key}) ? \$${key} : '';
  "
}

DB_HOST="$(read_db_var db_host)"
DB_NAME="$(read_db_var db_name)"
DB_USER="$(read_db_var db_user)"
DB_PASS="$(read_db_var db_pass)"

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
  echo "Impossible de lire conn/conn.php" >&2
  exit 1
fi

SQL_FILE="${BACKUP_DIR}/colobanes_yaye_${STAMP}.sql"
ZIP_FILE="${BACKUP_DIR}/yayematy.com_full_${STAMP}.zip"

export MYSQL_PWD="${DB_PASS}"
mysqldump -h "${DB_HOST}" -u "${DB_USER}" --single-transaction --routines --triggers --events "${DB_NAME}" > "${SQL_FILE}"
unset MYSQL_PWD

cd /home/colobanes
zip -r -q "${ZIP_FILE}" yayematy.com

chmod 644 "${SQL_FILE}" "${ZIP_FILE}"
ls -lh "${SQL_FILE}" "${ZIP_FILE}"
echo "BACKUP_DIR=${BACKUP_DIR}"
echo "SQL_FILE=${SQL_FILE}"
echo "ZIP_FILE=${ZIP_FILE}"
