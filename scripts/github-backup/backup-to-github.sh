#!/usr/bin/env bash
set -euo pipefail

# IPTV Panel: GitHub backup (commit + push)
# - no-op if there are no changes
# - commits with a timestamp
# - pushes current branch to origin

PROJECT_DIR="${PROJECT_DIR:-/var/www/iptv-panel}"
RUN_AS_USER="${RUN_AS_USER:-}"
LOG_FILE="${LOG_FILE:-$PROJECT_DIR/storage/logs/github-backup.log}"
LOCK_FILE="${LOCK_FILE:-/tmp/iptv-panel-github-backup.lock}"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
  printf '%s %s\n' "$(date -Is)" "$*" | tee -a "$LOG_FILE" >&2
}

die() {
  log "ERROR: $*"
  exit 1
}

if [[ ! -d "$PROJECT_DIR/.git" ]]; then
  die "Not a git repository: $PROJECT_DIR"
fi

# Simple lock (prevents overlapping runs)
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  log "Another backup is already running; exiting."
  exit 0
fi

cd "$PROJECT_DIR"

if ! command -v git >/dev/null 2>&1; then
  die "git not found"
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

# Mark project dir as safe (needed when running as system user)
# (git may refuse to operate on repos owned by another user)
if ! git config --global --get safe.directory | grep -qx "$PROJECT_DIR" 2>/dev/null; then
  git config --global --add safe.directory "$PROJECT_DIR" >/dev/null 2>&1 || true
fi

log "Starting GitHub backup on branch: $BRANCH"

# Fetch & rebase to reduce push failures
# If this fails (no network), we still try to commit locally.
if git fetch --prune origin "$BRANCH" >/dev/null 2>&1; then
  git rebase "origin/$BRANCH" >/dev/null 2>&1 || true
fi

# Stage everything (respects .gitignore)
git add -A

if git diff --cached --quiet; then
  log "No changes to commit."
  exit 0
fi

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

log "Committed. Pushing to origin/$BRANCH ..."
if ! git push origin "$BRANCH" >/dev/null 2>&1; then
  die "git push failed (check auth: SSH key or token)"
fi

log "Backup complete."
