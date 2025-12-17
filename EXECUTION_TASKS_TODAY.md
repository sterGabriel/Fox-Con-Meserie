════════════════════════════════════════════════════════════════════════════════
🔴 EXECUTION TASKS - TODAY (DEADLINE: 2 HOURS)
════════════════════════════════════════════════════════════════════════════════

NO STORIES. NO STATUSES. ONLY EXECUTION.

════════════════════════════════════════════════════════════════════════════════
🔴 TASK A: IMPORT SELECTED BUTTON (60 MIN)
════════════════════════════════════════════════════════════════════════════════

OBJECTIVE:
Make "📥 Import Selected" button work in real browser.

STEPS:

1. Open browser:
   http://localhost/admin/video-categories/5/browse?path=/media

2. Press F12 → Network tab → Preserve log (checkbox)

3. Click Clear (X icon) to reset logs

4. Select 2-3 video files (checkboxes)

5. Click "📥 Import Selected" button (at bottom of page)

6. In Network tab, look for POST request

EXPECTED RESULT:

✅ POST request appears
✅ URL: /video-categories/5/import
✅ Status: 200 or 302 (GREEN, not red)
✅ Payload shows: files[] = ["/media/videos/..."]

❌ IF NO request appears:
   → JavaScript broken
   → Hard refresh: Ctrl+F5
   → Try again

✅ IF POST appears with 200/302:
   → PAGE RELOADS
   → Videos have green badge "✓ Imported"
   → Checkboxes disabled for imported videos
   → Green message at top: "✅ Imported: X videos"

DELIVERABLE:
Screenshot: NETWORK_IMPORT.png
(Show Network tab with POST request visible, status 200/302, payload with files[])

════════════════════════════════════════════════════════════════════════════════
🔴 TASK B: ENCODE BUTTON (60 MIN)
════════════════════════════════════════════════════════════════════════════════

OBJECTIVE:
Make Encode / "Encode All" button trigger job.

STEPS:

1. Open VOD Channel → Encoding tab
   (any channel, e.g., /admin/vod-channels/1/settings)

2. Press F12 → Network tab → Preserve log

3. Click Clear (X icon)

4. Click "Encode" or "Encode All" button

5. In Network tab, look for POST request

EXPECTED RESULT:

✅ POST request appears
✅ Status: 200 or 302 (GREEN)
✅ After response, page shows:
   - progress bar starting
   - status changing from "Pending" to "Encoding"
   - file .ts being created on disk (check storage/)

❌ IF NO request appears:
   → Button not wired to backend
   → Hard refresh: Ctrl+F5
   → Try again

DELIVERABLE:
Screenshot: NETWORK_ENCODE.png
(Show Network tab with POST request, status 200/302)

════════════════════════════════════════════════════════════════════════════════
🔴 TASK C: CONSOLE CHECK (10 MIN)
════════════════════════════════════════════════════════════════════════════════

OBJECTIVE:
Verify no JavaScript errors during button clicks.

STEPS:

1. F12 → Console tab

2. Click "Import Selected" button (from TASK A)

3. Click "Encode" button (from TASK B)

4. Look for RED errors in console

EXPECTED RESULT:

✅ No red errors
✅ No warnings about undefined functions
✅ Clean console (only info/logs, no red)

❌ IF red errors appear:
   → Screenshot console with errors
   → Report which button triggered error

DELIVERABLE:
Screenshot: CONSOLE.png
(Show Console tab - should be clean, no red)

════════════════════════════════════════════════════════════════════════════════
📋 FINAL DELIVERABLES (3 SCREENSHOTS ONLY)
════════════════════════════════════════════════════════════════════════════════

Deliver ONLY:

□ NETWORK_IMPORT.png
  (Network tab showing Import Selected POST request)

□ NETWORK_ENCODE.png
  (Network tab showing Encode POST request)

□ CONSOLE.png
  (Console tab - clean, no red errors)

────────────────────────────────────────────────────────────────────────────────

SEND NOTHING ELSE.

NO status messages.
NO explanations.
NO "it works" or "it's broken".

ONLY the 3 screenshots.

════════════════════════════════════════════════════════════════════════════════
⏰ DEADLINE: TODAY (2 HOURS MAX)
════════════════════════════════════════════════════════════════════════════════

If after 2 hours:
- Import Selected ≠ POST + 200/302 → FAIL
- Encode ≠ POST + 200/302 → FAIL
- Console has red errors → FAIL

Task remains NEFINALIZAT.

════════════════════════════════════════════════════════════════════════════════
