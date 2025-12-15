# 📬 MESAJ PENTRU ANGAJAT — PHASE 4 TASK 4

**Subject**: TASK 4 — Export canale în Xtream (HLS + TS)  
**Duration**: 2-3 days  
**Difficulty**: ⭐⭐⭐ (Infrastructure work)

---

## 🎯 OBIECTIV FINAL

Pentru **fiecare canal** în panel, trebuie să ai **2 URL-uri care merg în VLC și Xtream Codes:**

```
HLS: http://46.4.20.56:2082/streams/{channel_id}/index.m3u8
TS:  http://46.4.20.56:2082/streams/{channel_id}.ts
```

Ambele URL-uri trebuie sa meargă **live** (când startezi canalul).

---

## 📋 TASK 4 BREAKDOWN (4 Subtasks)

### ✅ TASK 4.1 — Storage pentru HLS (pe disk)

**What to do:**
1. Create folder structure
2. Configure permissions
3. Set output path in channel model

**Step 1: Create folder**
```bash
mkdir -p /var/www/iptv-panel/public/streams
chown -R www-data:www-data /var/www/iptv-panel/public/streams
chmod -R 755 /var/www/iptv-panel/public/streams
```

**Step 2: FFmpeg HLS output**

Per canal, HLS merge la:
```
/var/www/iptv-panel/public/streams/{channel_id}/
├── index.m3u8          (master playlist)
├── seg_00001.ts        (4-second segment)
├── seg_00002.ts
└── seg_00003.ts
```

**Step 3: FFmpeg HLS command**

(Exact command from TASK_4_DETAILED.md, line ~200)

```bash
ffmpeg -re -i INPUT \
  -r 25 -vsync cfr \
  -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p" \
  -c:v libx264 -preset veryfast -profile:v high \
  -b:v 2500k -maxrate 2500k -bufsize 5000k \
  -g 50 -keyint_min 50 -sc_threshold 0 \
  -c:a aac -b:a 128k -ac 2 -ar 48000 \
  -f hls -hls_time 4 -hls_list_size 8 \
  -hls_flags delete_segments+append_list+independent_segments \
  -hls_segment_filename "/var/www/iptv-panel/public/streams/{id}/seg_%05d.ts" \
  "/var/www/iptv-panel/public/streams/{id}/index.m3u8"
```

**Acceptance:**
- [x] Folder created
- [x] www-data owns it
- [x] FFmpeg outputs segments to folder
- [x] index.m3u8 exists and is valid

---

### ✅ TASK 4.2 — TS single URL (per canal) via reverse proxy

**What to do:**
1. FFmpeg listens as HTTP server (local)
2. Nginx proxies to public URL
3. Auto-generate nginx config per channel

**Step 1: Port mapping**

```
Formula: TS_PORT = 9100 + channel_id

Examples:
  channel_id=1 → port 9101
  channel_id=2 → port 9102
  channel_id=3 → port 9103
  channel_id=N → port 9100+N
```

**Step 2: FFmpeg TS as HTTP server (local)**

FFmpeg command (runs locally on 127.0.0.1):

```bash
ffmpeg -re -i INPUT \
  -r 25 -vsync cfr \
  -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p" \
  -c:v libx264 -preset veryfast -profile:v high \
  -b:v 2500k -maxrate 2500k -bufsize 5000k \
  -g 50 -keyint_min 50 -sc_threshold 0 \
  -c:a aac -b:a 128k -ac 2 -ar 48000 \
  -mpegts_flags +resend_headers \
  -pcr_period 0.02 -pat_period 0.1 -pmt_period 0.1 \
  -f mpegts -listen 1 "http://127.0.0.1:9103/stream.ts"
```

Output: `http://127.0.0.1:9103/stream.ts` (localhost only)

**Step 3: Nginx reverse proxy**

Create: `/etc/nginx/snippets/iptv_ts_map.conf`

```nginx
# Auto-generated per channel
location = /streams/1.ts { proxy_pass http://127.0.0.1:9101/stream.ts; }
location = /streams/2.ts { proxy_pass http://127.0.0.1:9102/stream.ts; }
location = /streams/3.ts { proxy_pass http://127.0.0.1:9103/stream.ts; }
location = /streams/4.ts { proxy_pass http://127.0.0.1:9104/stream.ts; }
# ... generate for all channels
```

**In your nginx vhost** (where panel runs):

```nginx
server {
    listen 2082;
    server_name 46.4.20.56;
    
    # Include TS routing
    include /etc/nginx/snippets/iptv_ts_map.conf;
    
    # ... rest of config
}
```

**Reload Nginx:**
```bash
nginx -t
systemctl reload nginx
```

**Result:**
- Internal: `http://127.0.0.1:9103/stream.ts`
- External: `http://46.4.20.56:2082/streams/3.ts` ✅

**Acceptance:**
- [x] Port mapping correct (9100 + id)
- [x] FFmpeg listens on correct port
- [x] Nginx rules generated per channel
- [x] URL works from outside

---

### ✅ TASK 4.3 — UI în panel (Channel Settings)

**What to do:**
1. Add section to channel settings page
2. Display HLS + TS URLs
3. Add copy buttons
4. Show status badges

**Location:**
File: `resources/views/admin/vod_channels/settings.blade.php`

Add this section (new card):

```blade
<!-- Export URLs Card -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">
        📤 Export URLs (Xtream Codes)
    </h2>

    <div class="space-y-4">
        <!-- HLS URL -->
        <div class="flex items-center justify-between p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg">
            <div class="flex-1">
                <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">HLS Stream</div>
                <code class="text-sm text-green-300 font-mono break-all">
                    http://46.4.20.56:2082/streams/{{ $channel->id }}/index.m3u8
                </code>
                <div class="text-xs text-green-400 mt-2">🟢 Ready (if channel running)</div>
            </div>
            <button 
                type="button"
                class="copy-url-btn ml-3 px-3 py-2 bg-blue-500/20 text-blue-300 rounded hover:bg-blue-500/30 transition text-sm"
                data-url="http://46.4.20.56:2082/streams/{{ $channel->id }}/index.m3u8">
                📋 Copy
            </button>
        </div>

        <!-- TS URL -->
        <div class="flex items-center justify-between p-4 bg-slate-800/30 border border-slate-600/30 rounded-lg">
            <div class="flex-1">
                <div class="text-xs text-slate-400 uppercase tracking-wide mb-1">TS Stream (Single)</div>
                <code class="text-sm text-green-300 font-mono break-all">
                    http://46.4.20.56:2082/streams/{{ $channel->id }}.ts
                </code>
                <div class="text-xs text-green-400 mt-2">🟢 Ready (if channel running)</div>
            </div>
            <button 
                type="button"
                class="copy-url-btn ml-3 px-3 py-2 bg-blue-500/20 text-blue-300 rounded hover:bg-blue-500/30 transition text-sm"
                data-url="http://46.4.20.56:2082/streams/{{ $channel->id }}.ts">
                📋 Copy
            </button>
        </div>
    </div>

    <div class="mt-4 p-3 bg-blue-500/10 border border-blue-400/30 rounded-lg text-xs text-blue-300">
        💡 Both URLs work in VLC and Xtream Codes. Use these to add the channel to your IPTV player.
    </div>
</div>
```

**JavaScript (for copy button):**

```javascript
document.querySelectorAll('.copy-url-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('data-url');
        navigator.clipboard.writeText(url).then(() => {
            const oldText = this.textContent;
            this.textContent = '✅ Copied!';
            setTimeout(() => {
                this.textContent = oldText;
            }, 2000);
        });
    });
});
```

**Acceptance:**
- [x] HLS URL displays correctly
- [x] TS URL displays correctly
- [x] Copy buttons work
- [x] Status badges show (🟢 OK)
- [x] URLs are readable (formatted nicely)

---

### ✅ TASK 4.4 — Test obligatoriu (Acceptance)

**What to test:**

1. **HLS exists and responds:**
```bash
curl -I http://46.4.20.56:2082/streams/3/index.m3u8
# Expected: 200 OK
# Content-Type: application/vnd.apple.mpegurl
```

2. **TS exists and responds:**
```bash
curl -I http://46.4.20.56:2082/streams/3.ts
# Expected: 200 OK
# Content-Type: video/mp2t
```

3. **VLC plays HLS:**
```
VLC → Media → Open Network Stream
URL: http://46.4.20.56:2082/streams/3/index.m3u8
Expected: Video plays smoothly
```

4. **VLC plays TS:**
```
VLC → Media → Open Network Stream
URL: http://46.4.20.56:2082/streams/3.ts
Expected: Video plays smoothly
```

5. **Xtream Codes accepts both:**
```
Xtream Codes → Add Channel
Name: "Test Channel 3"
URL (HLS): http://46.4.20.56:2082/streams/3/index.m3u8
OR
URL (TS):  http://46.4.20.56:2082/streams/3.ts
Expected: Channel appears in EPG
```

**Documentation to provide:**

```markdown
## Test Results — TASK 4

### HLS Test
- curl response: 200 OK ✅
- VLC playback: [PASS/FAIL]
- Xtream integration: [PASS/FAIL]

### TS Test
- curl response: 200 OK ✅
- VLC playback: [PASS/FAIL]
- Xtream integration: [PASS/FAIL]

### Notes
[Any issues or observations]
```

**Acceptance Checklist:**
- [x] Folder structure created
- [x] HLS segments generate real-time
- [x] TS port maps correctly
- [x] Nginx rules generated
- [x] UI shows both URLs
- [x] Copy buttons work
- [x] curl -I returns 200 (both)
- [x] VLC plays both streams
- [x] Xtream Codes accepts both URLs

---

## ⚠️ IMPORTANT NOTES

### ✅ DO:
- Create folder with correct permissions
- Generate Nginx config per channel (automated)
- Test both streams before finishing
- Document any issues

### ❌ DON'T:
- Modify database schema without asking
- Delete/break the current system
- Hardcode port numbers (use formula)
- Skip testing phase

---

## 📊 SUBTASK ORDER

```
1. TASK 4.1 — HLS storage (30 min setup)
2. TASK 4.2 — TS reverse proxy (1 hour setup + nginx)
3. TASK 4.3 — UI display (1 hour coding)
4. TASK 4.4 — Full testing (1 hour)

Total: ~4 hours hands-on + troubleshooting
```

---

## 🚀 WHEN DONE, YOU PROVIDE:

1. ✅ Folder structure created
2. ✅ FFmpeg commands tested
3. ✅ Nginx config working
4. ✅ UI displaying URLs
5. ✅ Test results (curl + VLC screenshots)
6. ✅ Xtream Codes integration verified

---

## 💬 IF YOU GET STUCK

**Check these first:**
- TASK_4_DETAILED.md (step-by-step guide)
- TASK.md (full spec)
- FFmpeg log output (check -v debug flag)
- Nginx error log: `tail -f /var/log/nginx/error.log`

**Then ask** (but docs answer 90% of questions)

---

**Questions?** → Check TASK_4_DETAILED.md  
**Ready to start?** → Create folder, then TASK 4.1  
**Good luck!** 🚀
