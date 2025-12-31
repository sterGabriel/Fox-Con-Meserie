#!/usr/bin/env bash
set -euo pipefail

# IPTV Panel: GitHub backup (commit + push) + optional local full-backup
# - no-op if there are no changes to commit
# - commits with a timestamp
# - pushes current branch to origin
# - (optional) runs: php artisan backup:full (creates a local archive on the server)

PROJECT_DIR="${PROJECT_DIR:-/var/www/iptv-panel}"
RUN_AS_USER="${RUN_AS_USER:-}"
LOG_FILE="${LOG_FILE:-$PROJECT_DIR/storage/logs/github-backup.log}"
LOCK_FILE="${LOCK_FILE:-$PROJECT_DIR/storage/app/github-backup/backup.lock}"

# Local backup (server-side archive). Stored under storage/app/full-backups by the artisan command.
ENABLE_LOCAL_FULL_BACKUP="${ENABLE_LOCAL_FULL_BACKUP:-1}"
FULL_BACKUP_REASON="${FULL_BACKUP_REASON:-scheduled_github}"

# Progress/status file for UI/ops (simple “progress bar” status).
STATUS_FILE="${STATUS_FILE:-$PROJECT_DIR/storage/app/github-backup/status.json}"

# PHP binary (auto)
PHP_BIN="${PHP_BIN:-}"

mkdir -p "$(dirname "$LOG_FILE")"

mkdir -p "$(dirname "$LOCK_FILE")"

mkdir -p "$(dirname "$STATUS_FILE")"

# If we can't write the log file (common when permissions are wrong), degrade gracefully.
if ! touch "$LOG_FILE" >/dev/null 2>&1; then
  LOG_FILE=""
fi

log() {
  if [[ -n "${LOG_FILE:-}" ]]; then
    printf '%s %s\n' "$(date -Is)" "$*" | tee -a "$LOG_FILE" >&2
  else
    printf '%s %s\n' "$(date -Is)" "$*" >&2
  fi
}

write_status() {
  local percent="$1"
  local message="$2"
  local ts
  ts="$(date -Is)"

  # Write valid JSON (no jq). Use PHP which exists in this project.
  TS="$ts" PCT="$percent" MSG="$message" FILE="$STATUS_FILE" \
    php -r '$ts=getenv("TS"); $pct=(int)getenv("PCT"); $msg=getenv("MSG"); $file=getenv("FILE"); @file_put_contents($file, json_encode(["ts"=>$ts,"percent"=>$pct,"message"=>$msg], JSON_UNESCAPED_SLASHES)."\n");' \
    >/dev/null 2>&1 || true
}

step() {
  local percent="$1"
  shift
  local message="$*"
  log "[$percent%] $message"
  write_status "$percent" "$message"
}

die() {
  log "ERROR: $*"
  exit 1
}

if [[ ! -d "$PROJECT_DIR/.git" ]]; then
  die "Not a git repository: $PROJECT_DIR"
fi

if ! command -v git >/dev/null 2>&1; then
  die "git not found"
fi

# Mark project dir as safe (needed when running as system user)
# (git may refuse to operate on repos owned by another user)
if ! git config --global --get-all safe.directory 2>/dev/null | grep -qx "$PROJECT_DIR"; then
  git config --global --add safe.directory "$PROJECT_DIR" >/dev/null 2>&1 || true
fi

# Simple lock (prevents overlapping runs)
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  log "Another backup is already running; exiting."
  exit 0
fi

cd "$PROJECT_DIR"

if [[ -z "$PHP_BIN" ]]; then
  if command -v php8.4 >/dev/null 2>&1; then
    PHP_BIN="php8.4"
  else
    PHP_BIN="php"
  fi
fi

# Ensure origin exists
if ! git remote get-url origin >/dev/null 2>&1; then
  die "git remote 'origin' is not configured"
fi

# Optional: avoid failing if no upstream set; we push explicitly.
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$BRANCH" == "HEAD" || "$BRANCH" == "" ]]; then
  die "Detached HEAD; cannot determine branch"
fi

step 5 "Starting backup on branch: $BRANCH"

# Fetch & rebase to reduce push failures
# If this fails (no network), we still try to commit locally.
if git fetch --prune origin "$BRANCH" >/dev/null 2>&1; then
  step 12 "Fetched origin/$BRANCH"
  git rebase "origin/$BRANCH" >/dev/null 2>&1 || true
  step 18 "Rebased (best-effort)"
else
  step 12 "Fetch skipped/failed (offline?)"
fi

# Stage everything (respects .gitignore)
step 25 "Staging changes (git add -A)"
git add -A

if git diff --cached --quiet; then
  step 100 "No changes to commit."
  exit 0
fi

# Optional local full-backup on the server (stored in storage/app/full-backups).
# This is NOT pushed to GitHub unless you explicitly track that folder.
if [[ "$ENABLE_LOCAL_FULL_BACKUP" == "1" || "$ENABLE_LOCAL_FULL_BACKUP" == "true" || "$ENABLE_LOCAL_FULL_BACKUP" == "TRUE" ]]; then
  step 35 "Creating local full backup archive (php artisan backup:full)"

  if command -v "$PHP_BIN" >/dev/null 2>&1; then
    if "$PHP_BIN" artisan backup:full --reason="$FULL_BACKUP_REASON" >/dev/null 2>&1; then
      step 55 "Local full backup created (see storage/app/full-backups)"
    else
      # Don't block GitHub backup if local backup fails.
      step 55 "WARNING: Local full backup failed (continuing with git backup)"
    fi
  else
    step 55 "WARNING: PHP binary not found ($PHP_BIN). Skipping local backup."
  fi
else
  step 55 "Local full backup disabled (ENABLE_LOCAL_FULL_BACKUP=0)"
fi

step 70 "Creating git commit"
TS="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
HOST="$(hostname -s 2>/dev/null || hostname)"
MSG="chore(backup): auto backup $TS ($HOST)"

# Make commits deterministic and avoid interactive editor
export GIT_AUTHOR_NAME="${GIT_AUTHOR_NAME:-iptv-backup}"
export GIT_AUTHOR_EMAIL="${GIT_AUTHOR_EMAIL:-iptv-backup@local}"
export GIT_COMMITTER_NAME="$GIT_AUTHOR_NAME"
export GIT_COMMITTER_EMAIL="$GIT_AUTHOR_EMAIL"

if ! git commit -m "$MSG" --no-gpg-sign >/dev/null 2>&1; then
  die "git commit failed"
fi

step 85 "Committed. Pushing to origin/$BRANCH"
if ! git push origin "$BRANCH" >/dev/null 2>&1; then
  die "git push failed (check auth: SSH key or token)"
fi

step 100 "Backup complete"
