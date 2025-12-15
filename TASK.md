# 📌 TASK.md — LIVE VOD → TV CHANNEL ENGINE (FINAL EXECUTION)

**Status**: Ready for Employee Assignment  
**Version**: 1.0 (FINAL, NON-NEGOTIABLE)  
**Date**: 2025-12-15

---

## ⛔ REGULI ABSOLUTE (NU SE DISCUTĂ)

1. **Acesta NU este VOD** - Este un SISTEM DE CANALE TV LIVE din VOD
2. **Per CANAL, nu per VIDEO** - Totul se face pe nivelul canalului
3. **Toți utilizatorii văd ACELAȘI PROGRAM** - Playlist-ul este SHARED
4. **Rulare 24/7 REAL** - Un proces ffmpeg care nu se oprește
5. **Output obligatoriu**: MPEG-TS + HLS + M3U
6. **UI simplu, clar, profesional** - Nu UI "developer"

---

## 🧱 MODEL DE DATE (FINAL)

### Table: `live_channels`
```sql
id                    INT PRIMARY KEY
name                  VARCHAR(255)
category_id           INT (nullable)
resolution            VARCHAR(50)        -- readonly "1280x720"
fps                   INT                -- readonly = 25
encode_profile_id     INT                -- FK to encode_profiles
logo_path             VARCHAR(1024)      -- nullable
overlay_title         BOOLEAN DEFAULT 0
overlay_timer         BOOLEAN DEFAULT 0
logo_position         VARCHAR(10)        -- TL/TR/BL/BR
output_ts_path        VARCHAR(1024)      -- /var/streaming/channel.ts
output_hls_path       VARCHAR(1024)      -- /var/streaming/hls/
status                ENUM('stopped', 'running') DEFAULT 'stopped'
ffmpeg_pid            INT NULL           -- Process ID of ffmpeg
started_at            TIMESTAMP NULL
created_at
updated_at
```

### Table: `playlist_items`
```sql
id                    INT PRIMARY KEY
channel_id            INT                -- FK to live_channels
video_id              INT                -- FK to videos
sort_order            INT
duration_seconds      INT                -- cached from video
created_at
updated_at
```

### Table: `videos`
```sql
id                    INT PRIMARY KEY
file_path             VARCHAR(1024)      -- /home/videos/movie.mp4
title                 VARCHAR(255)
duration              INT                -- seconds
codec                 VARCHAR(50)        -- h264, h265, etc
resolution            VARCHAR(50)        -- 1920x1080, etc
bitrate               INT                -- kbps
created_at
updated_at
```

### Table: `encode_profiles`
```sql
✅ Already exists (11 presets seeded)
```

---

## 🎛️ UI STRUCTURĂ (OBLIGATORIU)

### 1️⃣ CHANNEL SETTINGS PAGE
**Route**: `/vod-channels/{id}/settings`

#### A. Channel Info Card
```
📺 Channel Name
   Input: "Sports 24/7"

📂 Category
   Dropdown: Sports / Movies / News / etc

📐 Resolution
   Readonly: "1280x720" (from profile)

⏱️ FPS
   Readonly: "25" (TV standard)
```

#### B. LIVE Encoding Profile (PRIMARY)
```
🎬 Select Encoding Profile
   Dropdown:
   ├─ LIVE 720p FAST (1500 kbps)
   ├─ LIVE 1080p BALANCED (2500 kbps) ⭐ DEFAULT
   ├─ LIVE 1080p HQ (5000 kbps)
   └─ CUSTOM (Advanced)

💡 Profile shows:
   - Resolution
   - Bitrate
   - Encoder preset
   - Estimated CPU usage
```

#### C. Manual Override (Hidden by Default)
```
❌ NOT VISIBLE unless "CUSTOM" profile selected

When selected, show:
├─ Video Bitrate (number input)
├─ Audio Bitrate (number input)
├─ Audio Codec (dropdown: AAC / MP3 / AC3)
└─ Encoder Preset (dropdown: superfast / veryfast / fast / medium)
```

#### D. Logo & Overlay (TV Style)
```
Card: "📺 Channel Branding"

🖼️ Channel Logo
   ├─ Upload button
   └─ Preview

✅ Show Logo on Stream
   Checkbox (default: checked)

📍 Logo Position
   Buttons: TL | TR | BL | BR

🎥 Show Movie Title
   Checkbox (default: checked)
   └─ Size: small / medium / large

⏱️ Show Timer
   Checkbox (default: checked)
   └─ Format: elapsed / remaining
```

#### E. Output & Control
```
Card: "⚙️ Output & Control"

📤 TS Output Path
   Input: "/var/streaming/channel_sports.ts"

📤 HLS Output Path
   Input: "/var/streaming/hls/channel_sports/"

🎮 Control Buttons (Large, Prominent)
   ├─ ▶️ START CHANNEL (green)
   ├─ ⏹ STOP CHANNEL (red)
   └─ 🔁 RESTART CHANNEL (blue)

📊 Live Status
   • 🟢 RUNNING (12h 34m)
   • 🔴 STOPPED (since 2 hours ago)
```

---

### 2️⃣ PLAYLIST PAGE (PROGRAM TV)
**Route**: `/vod-channels/{id}/playlist`

#### Left Side: Current Playlist
```
🎬 Current Playlist
────────────────────────

Total Duration: 847 hours (35 days loop)
Loop: ♾️ Infinite (videos repeat 1000x)

[Drag & Drop Area] SortableJS
┌─────────────────────────────────┐
│ 1. Movie 1 (02:14:32)        ↑↓ │  ← drag handle
│ 2. Movie 2 (01:58:45)        ↑↓ │
│ 3. Movie 3 (01:45:12)        ↑↓ │
│ 4. Movie 4 (02:05:00)        ↑↓ │
│ 5. [repeats 996 more times...]   │
└─────────────────────────────────┘

🔘 Save Order Button
   (After dragging, user must save)

🔄 Rebuild Playlist
   (Regenerates playlist.txt)

⚠️ Warnings:
   • Minimum 3 videos to loop
   • Total duration < 90 hours recommended
```

#### Right Side: Available Videos
```
📹 Video Library (20 total)
──────────────────────────

🔍 Search: [Search box]

Filter: [All] [< 2h] [2-4h] [> 4h]

☑️ Select All    [V]

Videos:
┌──────────────────────────────┐
│ □ Movie A (02:14)            │
│ □ Movie B (01:58)      📊 Info
│ □ Movie C (01:45)            │
│ □ Movie D (02:05)            │
└──────────────────────────────┘

➕ Add Selected Videos Button
   (Bulk add to playlist)

Note:
   ❌ No individual select per video
   ❌ No per-video encoding settings
```

#### Bottom: Channel Control
```
▶️ START CHANNEL
   (same as in Settings)
```

---

### 3️⃣ CHANNEL MONITORING PAGE (NEW)
**Route**: `/channels/{id}/monitor`

```
📡 CHANNEL: Sports 24/7
════════════════════════════════════════

Status: 🟢 RUNNING (Started 12h 34m ago)

NOW PLAYING
───────────
🎬 Movie Title: "The Matrix Reloaded"
⏱️ Current Time: 01:45:32 / 02:14:32
📊 Next Video: "Inception" (starts in 28m 58s)

STREAM STATS
────────────
📊 Bitrate: 2500 kbps
🎬 Resolution: 1280x720
⏱️ FPS: 25.00
🔄 CPU Usage: 45%
💾 Memory: 256 MB

UPTIME
──────
Start Time: 15 Dec 2025, 10:30:00
Current Time: 15 Dec 2025, 23:04:32
⏱️ Running: 12 hours 34 minutes

CONTROL
────────
⏹ STOP CHANNEL
🔁 RESTART CHANNEL

NOTE: Auto-refreshes every 5 seconds
```

---

## ⚙️ ENCODING ENGINE (FINAL, NU SE SCHIMBĂ)

### STEP 1 — PRE-ENCODE (One-time, optional)

**Purpose**: Convert all videos to TS format with embedded logo/title

```bash
for video in videos/*.mp4; do
  ffmpeg -i "$video" \
    -c:v libx264 -preset veryfast \
    -vf "scale=1280:720,format=yuv420p,
         drawimage=filename=logo.png:x=W-w-10:y=H-h-10:alpha=0.7,
         drawtext=text='%{pts\:hms}':fontsize=30:x=W-tw-10:y=10" \
    -c:a aac -b:a 128k \
    -f mpegts "output/${video%.mp4}.ts"
done
```

✅ Output: Folder of `.ts` files (pre-processed)

---

### STEP 2 — LIVE CHANNEL (24/7 Loop)

**Purpose**: Stream channel infinitely with concat demuxer

```bash
ffmpeg \
  -stream_loop -1 \
  -f concat -safe 0 \
  -i playlist.txt \
  -c:v copy \
  -c:a copy \
  -f mpegts \
  pipe:1 | \
  tee /var/streaming/channel_sports.ts | \
  ffmpeg -i pipe:0 -f hls -hls_time 10 -hls_list_size 3 /var/streaming/hls/channel_sports/
```

**Where `playlist.txt`:**
```
file '/home/videos/video1.mp4'
file '/home/videos/video2.mp4'
file '/home/videos/video3.mp4'
file '/home/videos/video1.mp4'
file '/home/videos/video2.mp4'
file '/home/videos/video3.mp4'
... (repeated 1000 times)
```

✅ Outputs:
- `channel_sports.ts` (MPEGTS stream)
- `hls/channel_sports/index.m3u8` + segments

---

### STEP 3 — EXPORT & DELIVERY

**Endpoints:**

```
GET /channels/{id}/live.ts
    → MPEGTS stream
    → Content-Type: video/mp2t
    → Direct ffmpeg pipe

GET /channels/{id}/index.m3u8
    → HLS Master Playlist
    → Content-Type: application/vnd.apple.mpegurl

GET /channels/{id}/index.m3u
    → M3U Playlist (for players)
    → Returns: #EXTM3U with channel info

GET /api/channels/{id}/info
    → JSON with: name, logo, status, bitrate, fps, etc
    → Xtream Codes compatible
```

---

## 🛠️ TASKURI DE EXECUȚIE (ORDINE FIXĂ)

### ✅ TASK 1 — UI CLEANUP (PRIORITATE MAXIMA)

**Duration**: 1 day  
**Difficulty**: ⭐⭐ (Easy)

**What to do:**
1. Refactor `settings.blade.php` - 3 cards layout
2. Hide manual override fields by default
3. Remove unused VOD fields (resolution, fps, etc)
4. Add overlay section (logo position, title, timer)
5. Add output section (TS path, HLS path)
6. Add control buttons (Start/Stop/Restart)

**Acceptance Criteria:**
- ✅ Clean 3-section layout
- ✅ Manual override only visible if "CUSTOM" profile
- ✅ Form saves cleanly
- ✅ No unnecessary fields
- ✅ Responsive on mobile

**Files to modify:**
- `resources/views/admin/vod_channels/settings.blade.php`
- `app/Http/Controllers/Admin/LiveChannelController.php` → `updateSettings()`

---

### ✅ TASK 2 — CHANNEL ENGINE

**Duration**: 2 days  
**Difficulty**: ⭐⭐⭐⭐ (Hard - process management)

**What to do:**
1. Add columns to `live_channels`: `ffmpeg_pid`, `started_at`, `status`
2. Create `ChannelEngineController` with methods:
   - `start(Channel)` → Start ffmpeg process
   - `stop(Channel)` → Kill process + update DB
   - `restart(Channel)` → Stop then Start
   - `getStatus(Channel)` → Return JSON status
3. Create `ProcessManager` service to handle ffmpeg execution
4. Save PID to DB when process starts
5. Dashboard shows live status (🟢 Running / 🔴 Stopped)

**Acceptance Criteria:**
- ✅ Start button spawns ffmpeg process
- ✅ PID saved correctly
- ✅ Stop kills process cleanly
- ✅ Status endpoint shows real state
- ✅ Multiple channels independent
- ✅ Handles process crashes gracefully

**Files to create:**
- `app/Http/Controllers/ChannelEngineController.php`
- `app/Services/ProcessManager.php`
- Database migration: Add columns

**Routes:**
```
POST   /channels/{id}/start
POST   /channels/{id}/stop
POST   /channels/{id}/restart
GET    /channels/{id}/status
```

---

### ✅ TASK 3 — PLAYLIST LOOP

**Duration**: 1 day  
**Difficulty**: ⭐⭐⭐ (Medium)

**What to do:**
1. Create method `generatePlaylistFile(Channel)` in `EncodingProfileBuilder`
   - Reads playlist items
   - Generates `playlist_{channel_slug}.txt`
   - Repeats videos 1000 times
   - Saves to `/var/streaming/temp/`
2. Integrate into `start()` method
   - Generate playlist.txt first
   - Then start ffmpeg with concat demuxer
3. Add "Rebuild Playlist" button in UI
4. Add auto-restart if ffmpeg dies

**Acceptance Criteria:**
- ✅ Playlist file auto-generated
- ✅ Videos loop infinitely (no gaps)
- ✅ Process restarts if crashes
- ✅ Total duration shows in UI
- ✅ No manual playlist editing needed

**Files to modify:**
- `app/Services/EncodingProfileBuilder.php` → new method
- `app/Services/ProcessManager.php` → integration
- `resources/views/admin/vod_channels/playlist.blade.php` → show duration + rebuild button

---

### ✅ TASK 4 — STREAM EXPORT

**Duration**: 1-2 days  
**Difficulty**: ⭐⭐ (Easy - mostly routing)

**What to do:**
1. Create `StreamExportController`
2. Add routes:
   - `GET /channels/{id}/live.ts` → Pipe ffmpeg output
   - `GET /channels/{id}/index.m3u8` → HLS playlist
   - `GET /channels/{id}/index.m3u` → M3U format
   - `GET /api/channels/{id}/info` → JSON metadata
3. TS endpoint streams directly from ffmpeg
4. HLS endpoint generates `.m3u8` + segments
5. M3U endpoint returns channel info for player

**Acceptance Criteria:**
- ✅ TS stream plays in VLC
- ✅ HLS playlist valid + segments created
- ✅ M3U readable in all players
- ✅ Xtream Codes compatible API
- ✅ No transcoding (stream copy only)

**Files to create:**
- `app/Http/Controllers/StreamExportController.php`

**Routes:**
```
GET    /channels/{id}/live.ts
GET    /channels/{id}/index.m3u8
GET    /channels/{id}/index.m3u
GET    /api/channels/{id}/info
```

---

### ✅ TASK 5 — MONITORING

**Duration**: 1-2 days  
**Difficulty**: ⭐⭐⭐ (Medium - real-time updates)

**What to do:**
1. Create `StreamMonitor` service
   - Read ffmpeg stats
   - Get current video from playlist position
   - Calculate uptime
   - Return bitrate, fps, resolution
2. Create monitoring page `/channels/{id}/monitor`
   - Shows all stats
   - Updates every 5 seconds (fetch)
3. Dashboard widget showing all channels + status
4. Optional: WebSocket for real-time (nice-to-have)

**Acceptance Criteria:**
- ✅ "Now Playing" shows correct video
- ✅ Uptime counts correctly
- ✅ Bitrate/FPS/Resolution accurate
- ✅ Status icons (🟢/🔴) correct
- ✅ Auto-refreshes without full page reload

**Files to create:**
- `app/Services/StreamMonitor.php`
- `app/Http/Controllers/MonitoringController.php`
- `resources/views/channels/monitor.blade.php`

**Routes:**
```
GET    /channels/{id}/monitor
GET    /api/channels/{id}/monitor (JSON)
GET    /api/channels/all/monitor (all channels)
```

---

## ⛔ CE ESTE INTERZIS (STRICT)

```
❌ Encoding per video
   → Profile applies to ENTIRE channel
   → No per-video settings

❌ Profile pe video
   → Videos are just material
   → Profile is on CHANNEL only

❌ UI complicat
   → Keep it simple
   → Max 3 sections per page
   → Clear buttons, no hidden menus

❌ VOD logic
   → This is LIVE TV, not VOD
   → No "on-demand" thinking
   → 24/7 continuous loop only

❌ Manual fields by default
   → Hide them unless "CUSTOM" profile
   → 95% users use presets

❌ Per-video overlays
   → Logo/title/timer is CHANNEL level
   → Not per video
```

---

## ✅ DEFINIȚIA „DONE"

```
1. Crezi canal
   → Fill name, profile, select videos

2. Alegi profile
   → LIVE 720p BALANCED (default)
   → Or CUSTOM if needed

3. Adaugi filme
   → Drag & drop in playlist
   → Save order
   → Shows total duration

4. Apeși START
   → Green button
   → ffmpeg process starts
   → PID saved

5. Canalul merge 24/7
   → Videos loop infinitely
   → No restarts visible
   → Status shows "🟢 RUNNING"

6. Stream apare în Xtream
   → /channels/{id}/live.ts
   → /channels/{id}/index.m3u8
   → /channels/{id}/index.m3u

7. Toți userii văd același conținut
   → Shared playlist
   → Same video at same time
   → Not on-demand
```

---

## 📅 EXECUTION TIMELINE

| Task | Duration | Effort | Start |
|------|----------|--------|-------|
| **1. UI Cleanup** | 1 day | 3h | Day 1 |
| **2. Channel Engine** | 2 days | 8h | Day 2-3 |
| **3. Playlist Loop** | 1 day | 4h | Day 4 |
| **4. Stream Export** | 1-2 days | 6h | Day 5 |
| **5. Monitoring** | 1-2 days | 5h | Day 6-7 |

**Total**: ~1 week (assuming full-time)

---

## 📦 WHAT EMPLOYEE GETS

✅ Database: 11 encoding profiles pre-seeded  
✅ Code: TASK 3B foundation (UI components, EncodingProfileBuilder)  
✅ Documentation: LIVE_STREAMING_GUIDE.md + this file  
✅ Routes: Basic structure in place  
✅ Models: LiveChannel, EncodeProfile, Video, PlaylistItem  

---

## 🔒 APPROVAL CHECKLIST

- [x] Requirements clear & non-ambiguous
- [x] Data model defined
- [x] UI mockups provided
- [x] Task order specified
- [x] Acceptance criteria listed
- [x] Rules specified (what NOT to do)
- [x] Timeline estimated
- [x] Foundation code exists
- [x] Database ready
- [x] Documentation complete

**Status**: ✅ **READY FOR ASSIGNMENT**

---

**Created**: 2025-12-15  
**Last Updated**: 2025-12-15  
**Version**: 1.0 (FINAL)  
**Status**: Approved for Execution
