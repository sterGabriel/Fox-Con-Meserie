#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/iptv-panel}"
STREAMS_DIR="${STREAMS_DIR:-$PROJECT_DIR/storage/app/streams}"
DRY_RUN="${DRY_RUN:-0}"

if [[ ! -d "$STREAMS_DIR" ]]; then
  echo "Streams dir not found: $STREAMS_DIR" >&2
  exit 0
fi

echo "Target: $STREAMS_DIR"

echo "Scanning files to delete (.ts/.m3u8/.fifo/.txt) ..."
mapfile -d '' files < <(
  find "$STREAMS_DIR" -type f \( -name '*.ts' -o -name '*.m3u8' -o -name '*.fifo' -o -name '*.txt' \) -print0 2>/dev/null
)

count="${#files[@]}"
bytes=0
for f in "${files[@]}"; do
  if [[ -f "$f" ]]; then
    sz=$(stat -c '%s' "$f" 2>/dev/null || echo 0)
    bytes=$((bytes + sz))
  fi
done

gib=$(awk -v b="$bytes" 'BEGIN{printf "%.2f", b/1024/1024/1024}')
echo "Will delete: $count files (~${gib} GiB)"

if [[ "$DRY_RUN" == "1" ]]; then
  echo "DRY_RUN=1 -> not deleting. Showing first 50 paths:"
  printf '%s\n' "${files[@]}" | head -n 50
  exit 0
fi

echo "Deleting..."
# Delete in chunks to avoid arg limits
printf '%s\0' "${files[@]}" | xargs -0 -r -n 200 rm -f

# Remove empty directories (best-effort)
find "$STREAMS_DIR" -type d -empty -print0 2>/dev/null | xargs -0 -r rmdir 2>/dev/null || true

echo "Done. Current size:"
du -sh "$STREAMS_DIR" 2>/dev/null || true
