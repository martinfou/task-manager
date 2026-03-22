#!/usr/bin/env bash
#
# Google Tasks app — server backup (US-035).
# Creates a timestamped archive: DB logical dump + storage/ (excluding logs).
# Optionally prunes tiered retention and copies offsite via rclone.
#
# Usage:
#   BACKUP_ROOT=/path/to/backups ./scripts/backup-google-tasks.sh [APP_ROOT]
#
# Required:
#   BACKUP_ROOT  — root directory for backup trees (e.g. /home/user/backups/google-tasks)
#
# Optional env:
#   SKIP_PRUNE=1           — create archive only, do not prune tiers or rclone
#   RCLONE_REMOTE=name     — rclone remote (no colon); copies archive to remote:GoogleTasksBackups/<env>/incoming/
#   BACKUP_LOG=path        — append stdout/stderr (cron-friendly)
#
# Reads database settings from APP_ROOT/.env (DB_CONNECTION, DB_*). Does not archive .env.
#
set -euo pipefail

APP_ROOT="$(cd "${1:-${APP_ROOT:-.}}" && pwd)"

if [[ -n "${BACKUP_LOG:-}" ]]; then
  mkdir -p "$(dirname "$BACKUP_LOG")"
  exec >>"$BACKUP_LOG" 2>&1
fi

log() { printf '%s %s\n' "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" "$*"; }

die() { log "ERROR: $*"; exit 1; }

[[ -n "${BACKUP_ROOT:-}" ]] || die "Set BACKUP_ROOT to the backup parent directory (separate from the live app tree)."

[[ -f "$APP_ROOT/.env" ]] || die "Missing .env at $APP_ROOT"

# Load KEY=value lines from .env (no export of comments / blanks).
while IFS= read -r line || [[ -n "$line" ]]; do
  [[ "$line" =~ ^[[:space:]]*# ]] && continue
  [[ "$line" =~ ^[[:space:]]*$ ]] && continue
  [[ "$line" =~ ^[A-Za-z_][A-Za-z0-9_]*= ]] || continue
  export "$line"
done <"$APP_ROOT/.env"

: "${DB_CONNECTION:=sqlite}"
APP_ENV_NAME="${APP_ENV:-production}"
TS="$(date -u +"%Y%m%d_%H%M%S")"
HOST_SHORT="$(hostname -s 2>/dev/null || hostname || echo unknown)"

WORK="$(mktemp -d "${TMPDIR:-/tmp}/gt-backup.XXXXXX")"
cleanup() { rm -rf "$WORK"; }
trap cleanup EXIT

mkdir -p "$WORK/backup"

# --- Database ---
case "$DB_CONNECTION" in
  sqlite)
    SQLITE_PATH="${DB_DATABASE:-database/database.sqlite}"
    [[ "$SQLITE_PATH" = /* ]] || SQLITE_PATH="$APP_ROOT/$SQLITE_PATH"
    [[ -f "$SQLITE_PATH" ]] || die "SQLite file not found: $SQLITE_PATH"
    if command -v sqlite3 >/dev/null 2>&1; then
      sqlite3 "$SQLITE_PATH" ".backup '$WORK/backup/database.sqlite'"
    else
      die "sqlite3 CLI not found"
    fi
    DB_ARTIFACT="backup/database.sqlite"
    ;;
  mysql|mariadb)
    command -v mysqldump >/dev/null 2>&1 || die "mysqldump not found"
    : "${DB_HOST:=127.0.0.1}"
    : "${DB_PORT:=3306}"
    : "${DB_USERNAME:?DB_USERNAME must be set in .env for mysql/mariadb}"
    : "${DB_DATABASE:?DB_DATABASE must be set in .env for mysql/mariadb}"
    MYSQLDUMP=(mysqldump --single-transaction --routines --events)
    [[ -n "${DB_SOCKET:-}" ]] && MYSQLDUMP+=(--socket="$DB_SOCKET")
    MYSQLDUMP+=(-h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME")
    if [[ -n "${DB_PASSWORD:-}" ]]; then
      export MYSQL_PWD="$DB_PASSWORD"
    fi
    MYSQLDUMP+=("$DB_DATABASE")
    "${MYSQLDUMP[@]}" >"$WORK/backup/db.sql"
    unset MYSQL_PWD 2>/dev/null || true
    DB_ARTIFACT="backup/db.sql"
    ;;
  pgsql)
    command -v pg_dump >/dev/null 2>&1 || die "pg_dump not found"
    : "${DB_HOST:=127.0.0.1}"
    : "${DB_PORT:=5432}"
    : "${DB_USERNAME:?DB_USERNAME must be set for pgsql}"
    : "${DB_DATABASE:?DB_DATABASE must be set for pgsql}"
    export PGPASSWORD="${DB_PASSWORD:-}"
    pg_dump -h"$DB_HOST" -p"$DB_PORT" -U"$DB_USERNAME" -Fc -f"$WORK/backup/db.dump" "$DB_DATABASE"
    unset PGPASSWORD
    DB_ARTIFACT="backup/db.dump"
    ;;
  *)
    die "Unsupported DB_CONNECTION=$DB_CONNECTION (use sqlite, mysql, mariadb, or pgsql)"
    ;;
esac

# --- storage/ (exclude logs) ---
if [[ -d "$APP_ROOT/storage" ]]; then
  mkdir -p "$WORK/backup/storage"
  # rsync preferred; tar fallback
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --exclude='logs/' "$APP_ROOT/storage/" "$WORK/backup/storage/"
  else
    (cd "$APP_ROOT" && tar -cf - --exclude='storage/logs' storage) | (cd "$WORK/backup" && tar -xf -)
  fi
fi

# --- Manifest (no secrets) ---
{
  echo "backup_format=1"
  echo "created_utc=$TS"
  echo "hostname=$HOST_SHORT"
  echo "app_env=$APP_ENV_NAME"
  echo "db_connection=$DB_CONNECTION"
  echo "db_artifact=$DB_ARTIFACT"
  echo "app_root_label=$(basename "$APP_ROOT")"
} >"$WORK/backup/MANIFEST.txt"

ARCHIVE_NAME="google-tasks_${APP_ENV_NAME}_${TS}.tar.gz"
STAGING="$WORK/$ARCHIVE_NAME"
(
  cd "$WORK"
  tar -czf "$STAGING" backup
)

DEST_ENV="$BACKUP_ROOT/$APP_ENV_NAME"
mkdir -p "$DEST_ENV/daily" "$DEST_ENV/weekly" "$DEST_ENV/monthly" "$DEST_ENV/incoming"

cp "$STAGING" "$DEST_ENV/incoming/$ARCHIVE_NAME"
cp "$STAGING" "$DEST_ENV/daily/$ARCHIVE_NAME"
log "Wrote $DEST_ENV/daily/$ARCHIVE_NAME"

prune_dir_keep() {
  local dir="$1"
  local keep="$2"
  local pattern="${3:-*.tar.gz}"
  [[ -d "$dir" ]] || return 0
  # POSIX-friendly: drop all but the newest `keep` files (mtime order).
  # shellcheck disable=SC2012
  ls -t "$dir"/$pattern 2>/dev/null | tail -n +"$((keep + 1))" | while IFS= read -r f; do
    [[ -f "$f" ]] || continue
    rm -f "$f"
    log "Pruned old backup: $f"
  done
}

if [[ -z "${SKIP_PRUNE:-}" ]]; then
  prune_dir_keep "$DEST_ENV/daily" 14 '*.tar.gz'

  # Weekly anchor: Sunday (UTC) — copy today's archive into weekly/; keep 4.
  DOW="$(date -u +%u)"
  if [[ "$DOW" == "7" ]]; then
    cp "$STAGING" "$DEST_ENV/weekly/week_${TS}.tar.gz"
    log "Weekly snapshot: $DEST_ENV/weekly/week_${TS}.tar.gz"
    prune_dir_keep "$DEST_ENV/weekly" 4 '*.tar.gz'
  fi

  # Monthly anchor: 1st of month (UTC) — keep 3.
  DOM="$(date -u +%d)"
  if [[ "$DOM" == "01" ]]; then
    MONTH_TAG="$(date -u +%Y-%m)"
    cp "$STAGING" "$DEST_ENV/monthly/month_${MONTH_TAG}.tar.gz"
    log "Monthly snapshot: $DEST_ENV/monthly/month_${MONTH_TAG}.tar.gz"
    prune_dir_keep "$DEST_ENV/monthly" 3 '*.tar.gz'
  fi
fi

if [[ -n "${RCLONE_REMOTE:-}" ]]; then
  REMOTE_PATH="GoogleTasksBackups/${APP_ENV_NAME}/incoming"
  if rclone copyto "$STAGING" "${RCLONE_REMOTE}:${REMOTE_PATH}/${ARCHIVE_NAME}" -v; then
    log "rclone: uploaded to ${RCLONE_REMOTE}:${REMOTE_PATH}/${ARCHIVE_NAME}"
  else
    log "ERROR: rclone failed — archive remains on disk at $DEST_ENV/daily/$ARCHIVE_NAME"
    exit 2
  fi
fi

log "Backup OK: $ARCHIVE_NAME"
exit 0
