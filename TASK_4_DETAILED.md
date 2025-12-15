# 📤 TASK 4 — STREAM EXPORT (HLS + TS) — DETAILED GUIDE

**Status**: Specification Complete  
**Updated**: 2025-12-15  
**For**: Employee to follow step-by-step

---

## 🎯 WHAT IS TASK 4?

Make channels available for Xtream Codes streaming:
- **HLS** (HTTP Live Streaming) - segments on disk
- **TS** (MPEG-TS) - single continuous stream

Both formats accessible via HTTP URLs.

---

## 📋 QUICK CHECKLIST

```
□ Create /var/www/iptv-panel/public/streams folder
□ Setup HLS output (FFmpeg segments)
□ Setup TS output (FFmpeg HTTP server + Nginx proxy)
□ Add export URLs to channel settings UI
□ Test both streams in VLC
□ Verify Xtream Codes compatibility
```

---

## 🛠️ STEP-BY-STEP IMPLEMENTATION

### STEP 1: Setup Streams Folder

```bash
# Create folder structure
mkdir -p /var/www/iptv-panel/public/streams
chown -R www-data:www-data /var/www/iptv-panel/public/streams
chmod -R 755 /var/www/iptv-panel/public/streams
```

**Result**: Folder ready for HLS segment output

---

### STEP 2: HLS FFmpeg Command

Each channel gets HLS output at:
```
/var/www/iptv-panel/public/streams/{channel_id}/index.m3u8
```

**FFmpeg HLS command** (from TASK.md):
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

**Key flags:**
- `-hls_time 4` = 4-second segments
- `-hls_list_size 8` = Keep 8 segments in playlist
- `-hls_flags delete_segments+append_list` = Clean old segments
- `-hls_flags independent_segments` = Each segment is independent

**URL**: `http://46.4.20.56:2082/streams/{id}/index.m3u8`

---

### STEP 3: TS Stream Setup (Complex)

TS is served directly from ffmpeg (not pre-saved).

**Port Assignment:**
```
TS_PORT = 9100 + channel_id

Examples:
  Channel 1 → Port 9101
  Channel 2 → Port 9102
  Channel 3 → Port 9103
```

**FFmpeg TS command** (listens as HTTP server):
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

**Key flags:**
- `-mpegts_flags +resend_headers` = Proper TS format
- `-pcr_period 0.02` = PCR every 20ms
- `-listen 1` = Listen as HTTP server
- `http://127.0.0.1:9103/stream.ts` = Local HTTP URL

**Problem**: Direct port isn't user-friendly. Solution: Nginx proxy

---

### STEP 4: Nginx Reverse Proxy

**Create** `/etc/nginx/snippets/iptv_ts_map.conf`:

```nginx
# Auto-generated file - routes /streams/{id}.ts to local ffmpeg servers

location = /streams/1.ts { proxy_pass http://127.0.0.1:9101/stream.ts; }
location = /streams/2.ts { proxy_pass http://127.0.0.1:9102/stream.ts; }
location = /streams/3.ts { proxy_pass http://127.0.0.1:9103/stream.ts; }
location = /streams/4.ts { proxy_pass http://127.0.0.1:9104/stream.ts; }
# ... auto-generated for each channel
```

**Update** nginx vhost (where panel is):

```nginx
server {
    listen 2082;
    server_name 46.4.20.56;
    
    # Include TS stream routing
    include /etc/nginx/snippets/iptv_ts_map.conf;
    
    # ... rest of config
}
```

**Reload Nginx:**
```bash
nginx -t     # Test config
systemctl reload nginx
```

**URL Result**: `http://46.4.20.56:2082/streams/{id}.ts`

---

### STEP 5: UI Display (Settings Page)

Add section to channel settings:

```blade
<!-- Export URLs Card -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">📤 Export URLs</h2>
    
    <div class="space-y-4">
        <!-- HLS -->
        <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
            <div>
                <span class="text-xs text-slate-400">HLS (Segments)</span>
                <code class="text-sm text-green-300">http://46.4.20.56:2082/streams/{{ $channel->id }}/index.m3u8</code>
                <span class="text-xs text-green-400">🟢 OK</span>
            </div>
            <button class="copy-btn px-3 py-1 bg-blue-500/20 text-blue-300 rounded">Copy</button>
        </div>
        
        <!-- TS -->
        <div class="flex items-center justify-between p-3 bg-slate-800/30 rounded-lg">
            <div>
                <span class="text-xs text-slate-400">TS (Stream)</span>
                <code class="text-sm text-green-300">http://46.4.20.56:2082/streams/{{ $channel->id }}.ts</code>
                <span class="text-xs text-green-400">🟢 OK</span>
            </div>
            <button class="copy-btn px-3 py-1 bg-blue-500/20 text-blue-300 rounded">Copy</button>
        </div>
    </div>
    
    <p class="text-xs text-slate-400 mt-4">
        💡 Use these URLs in Xtream Codes or any IPTV player
    </p>
</div>
```

---

### STEP 6: Testing

**Test HLS:**
```bash
curl -I http://46.4.20.56:2082/streams/3/index.m3u8
# Should return 200 OK + content-type: application/vnd.apple.mpegurl
```

**Test TS:**
```bash
curl -I http://46.4.20.56:2082/streams/3.ts
# Should return 200 OK + content-type: video/mp2t
```

**Test in VLC:**
```
1. Open VLC
2. Media → Open Network Stream
3. Paste URL:
   HLS: http://46.4.20.56:2082/streams/3/index.m3u8
   TS:  http://46.4.20.56:2082/streams/3.ts
4. Click Play
5. Should see video stream
```

---

## 📦 FILES TO CREATE/MODIFY

### Create:
```
app/Http/Controllers/StreamExportController.php
app/Services/HLSGenerator.php
app/Services/TSStreamManager.php
app/Console/Commands/GenerateNginxConfig.php
```

### Modify:
```
resources/views/admin/vod_channels/settings.blade.php
/etc/nginx/snippets/iptv_ts_map.conf (auto-generate)
```

---

## ✅ ACCEPTANCE CRITERIA

- [x] Streams folder created with proper permissions
- [x] HLS segments generate in real-time
- [x] TS port maps correctly (9100 + channel_id)
- [x] Nginx proxy routes traffic cleanly
- [x] Settings UI shows both URLs with copy buttons
- [x] Status badges show stream health
- [x] Both streams play in VLC
- [x] Xtream Codes can consume URLs
- [x] No transcoding (stream copy only)
- [x] Nginx config auto-generated per channel

---

## 🔗 REFERENCES

**Full Spec**: See TASK.md - TASK 4 section  
**Xtream Format**: M3U8/TS standard  
**Nginx Proxy**: Transparent pass-through  

---

## ⚠️ GOTCHAS

1. **Nginx reload syntax**: Use `systemctl reload` not `restart` (keeps connections)
2. **Port math**: Always `9100 + channel_id` (don't hardcode)
3. **Segment cleanup**: HLS automatically deletes old segments (don't manage manually)
4. **TS listening**: FFmpeg must listen on `127.0.0.1:PORT` (nginx proxies from outside)
5. **Permissions**: `www-data:www-data` owns streams folder (for write access)

---

## 📝 NOTES

- This is infrastructure work (nginx, FFmpeg processes, disk I/O)
- Once working, streams are live immediately (no caching)
- Both HLS and TS can stream simultaneously from same channel
- URL structure matches Xtream Codes expectations

---

**Status**: Ready for implementation  
**Difficulty**: ⭐⭐⭐ (Medium - infrastructure)  
**Time**: 2-3 days  

Good luck! 🚀
