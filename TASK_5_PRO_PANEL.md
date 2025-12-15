# 💼 MESAJ PENTRU ANGAJAT — PRO PANEL + LIVE CHANNEL BUILDER

**Scop**: Panelul devine **profesional** (ca în poze)  
**Durata**: ~2-3 săptămâni  
**Dificultate**: ⭐⭐⭐⭐ (Full UI refactor + Engine)

---

## 🎯 OBIECTIV FINAL

Panel-ul trebuie să arate și să funcționeze **PROFESIONAL**:

✅ Profile system (preseturi 480/720/1080 + manual)  
✅ Channel Builder cu overlay (logo/text/timer)  
✅ Playlist management (deja drag&drop OK)  
✅ Engine clar: Start/Stop, status, logs  
✅ Dual output: TS (Xtream) + HLS (compatibilitate)

---

## 📋 TASK A — Pagina "Encode Profiles" (NOUĂ)

### Obiectiv
Pagină dedicată pentru a crea și gestiona profiluri de encoding.

### UI (design detaliat)

```
┌─────────────────────────────────────────┐
│ Encode Profiles                         │
├─────────────────────────────────────────┤
│ [+ Create Profile]                      │
├─────────────────────────────────────────┤
│                                         │
│  [Profile Card]  [Profile Card]         │
│  480p / VOD      576p / VOD             │
│  30fps, 1200k    25fps, 1500k           │
│  AAC 128k        AAC 128k               │
│  [Edit][Dup][Del] [Edit][Dup][Del]    │
│                                         │
│  [Profile Card]  [Profile Card]         │
│  720p FAST/LIVE  720p BALANCED/LIVE     │
│  60fps, 2500k    60fps, 3500k           │
│  AAC 160k        AAC 192k               │
│  [Edit][Dup][Del] [Edit][Dup][Del]    │
│                                         │
│  [Profile Card]  [Profile Card]         │
│  1080p BALANCED  1080p HQ + H265        │
│  60fps, 5000k    60fps, 6000k           │
│  AAC 192k        AAC 256k               │
│  [Edit][Dup][Del] [Edit][Dup][Del]    │
│                                         │
└─────────────────────────────────────────┘
```

### Fiecare profil card (click → edit modal)

```
┌────────────────────────────────┐
│ Profile: 720p FAST / LIVE      │
├────────────────────────────────┤
│ Resolution:    1280 × 720 px   │
│ FPS:           60              │
│ Codec:         libx264         │
│ Preset:        veryfast        │
│ GOP/Keyint:    50 frames       │
│ Video Bitrate: 2500 kbps       │
│ Max Rate:      2500 kbps       │
│ Buffer Size:   5000 kbps       │
│ Audio Codec:   aac             │
│ Audio Bitrate: 160 kbps        │
│ Audio Channels: 2 (stereo)     │
│ Sample Rate:   48000 Hz        │
│                                │
│ Mode: ◉ LIVE  ○ VOD            │
│                                │
│ [Save] [Cancel]                │
└────────────────────────────────┘
```

### Funcționalități

- **Create Profile**: form cu toți parametrii
- **Edit**: modal cu tabel formular
- **Duplicate**: copiere profil + rename
- **Delete**: confirm dialog
- **Toggle LIVE/VOD**: 
  - LIVE = CBR + CFR + mpegts headers (PCR/PAT/PMT)
  - VOD = normal h264/h265

### Database (update EncodeProfile model)

Coloane necesare:
```
- id
- name (ex: "720p FAST LIVE")
- width (1280)
- height (720)
- fps (60)
- video_codec (libx264, libx265)
- video_bitrate (2500)
- video_preset (veryfast, fast, medium, slow)
- gop_keyint (50)
- max_rate (2500)
- buffer_size (5000)
- audio_codec (aac)
- audio_bitrate (160)
- audio_channels (2)
- audio_sample_rate (48000)
- mode (LIVE, VOD)
- created_at, updated_at
```

**Preseturi seeded (acum hai să ai și UI pentru ele):**
```
VOD profiles:
- 480p VOD (720×480, 30fps, h264, 1200k)
- 576p VOD (720×576, 25fps, h264, 1500k)

LIVE profiles:
- 720p FAST LIVE (1280×720, 60fps, h264, 2500k, veryfast)
- 720p BALANCED LIVE (1280×720, 60fps, h264, 3500k, medium)
- 1080p BALANCED LIVE (1920×1080, 60fps, h264, 5000k, medium)
- 1080p HQ LIVE (1920×1080, 60fps, h265, 6000k, medium)
```

### Acceptance

- [x] Pagina /admin/encode-profiles deschide
- [x] Liste toate profilurile cu carduri
- [x] Click card = edit modal
- [x] Create btn = form gol
- [x] Edit, Duplicate, Delete lucreaza
- [x] Mode toggle (LIVE/VOD)
- [x] Database persistent (salveaza în EncodeProfile)
- [x] No 404 errors

---

## 📋 TASK B — Pagina "Channel Settings" (REFĂCUTĂ pe TAB-uri)

### Obiectiv

Refactor `/admin/vod-channels/{id}/settings` cu layout **CLEAN pe TAB-uri**.

### UI (6 tab-uri)

```
┌──────────────────────────────────────────────────┐
│ Channel Settings: "TV Romania"                   │
├──────────────────────────────────────────────────┤
│ [General] [Playlist] [Encoding] [Overlay] [Info] [Outputs]
├──────────────────────────────────────────────────┤
│  [TAB CONTENT HERE]                              │
└──────────────────────────────────────────────────┘
```

### TAB 1: General

```
┌─────────────────────────────────┐
│ Channel Name                    │
│ [Input: TV Romania]             │
│                                 │
│ Category                        │
│ [Dropdown: Entertainment]        │
│                                 │
│ Mode                            │
│ ☑ 24/7 Channel (from VOD)      │
│                                 │
│ Description (optional)          │
│ [Textarea: ...]                 │
│                                 │
│ [Save Changes]                  │
└─────────────────────────────────┘
```

**Funcții:**
- Update `name`, `category_id`, `description`
- Toggle `is_24_7_channel` (default TRUE)

---

### TAB 2: Playlist / Source

```
┌────────────────────────────────────────────┐
│ Playlist Videos (deja ai drag&drop OK)     │
├────────────────────────────────────────────┤
│ [+ Add Video]                              │
│                                            │
│ # │ Title      │ Duration │ Status │ Acts │
│───┼────────────┼──────────┼────────┼──────┤
│ 1 │ Movie A    │ 1:45:32  │ ✅ ENC │ ⋮⋮  │
│ 2 │ Trailer    │ 0:02:15  │ ⏳ ENC │ ⋮⋮  │
│ 3 │ Movie B    │ 2:10:00  │ ✅ ENC │ ⋮⋮  │
│   │ (drag-drop reorder)                    │
│                                            │
│ [Queue Encode (All)]                       │
│                                            │
│ Status: Ready / Encoding / Complete        │
│ Progress: 3 / 3 files encoded              │
└────────────────────────────────────────────┘
```

**Funcții:**
- List videos cu drag-drop (deja ai)
- Add/Remove video
- "Queue Encode (All)" btn = queue jobs pentru fiecare video neencodat

---

### TAB 3: Encoding

```
┌──────────────────────────────────────────┐
│ Profile Selection                        │
│                                          │
│ [Dropdown: Select Profile]               │
│  └─ 720p FAST LIVE (2500k, 60fps)       │
│  └─ 1080p BALANCED LIVE (5000k, 60fps)  │
│  └─ Custom Manual                        │
│                                          │
│ ☐ Manual Override (Advanced)             │
│                                          │
│ [Wenn Manual ON, arată form de parametri]
│                                          │
│ Resolution: [1280 × 720]                │
│ FPS: [60]                                │
│ Video Codec: [libx264]                   │
│ Preset: [veryfast]                       │
│ Video Bitrate: [2500 kbps]               │
│ Audio Bitrate: [160 kbps]                │
│ Audio Codec: [aac]                       │
│                                          │
│ [Preview FFmpeg Command]                 │
│                                          │
│ ┌─────────────────────────────────────┐  │
│ │ ffmpeg -re -i input.mp4 ... (read) │  │
│ │ [Copy to clipboard]                 │  │
│ └─────────────────────────────────────┘  │
└──────────────────────────────────────────┘
```

**Funcții:**
- Dropdown profile (populated din TASK A)
- Toggle manual override
- Form apare condiționat
- Preview FFmpeg (read-only, generated from selected profile)
- Copy btn

---

### TAB 4: Overlay (Logo + Text + Timer)

```
┌─────────────────────────────────────────────┐
│ Overlay Configuration                       │
├─────────────────────────────────────────────┤
│                                             │
│ [LOGO SECTION]                              │
│ ☐ Enable Logo                              │
│                                             │
│ Upload: [Choose file] (PNG/SVG)             │
│ Position: [Dropdown: TL / TR / BL / BR]    │
│ X Offset: [20] px  Y Offset: [20] px        │
│ Size: [Width: 150] px  [Height: 100] px     │
│ Opacity: [━━━━━━━○─────] 80%                │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ [TEXT SECTION]                              │
│ ☐ Enable Text Overlay                      │
│ Content: [Dropdown: Channel Name / Movie Title / Custom]
│ Custom Text: [TV Romania Live]              │
│ Font Size: [24] px                          │
│ Box BG Opacity: [━━━○─────────────] 50%    │
│ Box BG Color: [#000000]                     │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ [TIMER / CLOCK SECTION]                     │
│ ☐ Enable Timer                              │
│ Format: [Dropdown: HH:mm / HH:mm:ss / HH:mm:ss.mmm]
│ Position: [Dropdown: TL / TR / BL / BR]    │
│ X Offset: [20] px  Y Offset: [20] px        │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ [SAFE MARGINS]                              │
│ Margin: [━━━━○──────────────] 20 px        │
│                                             │
│ ─────────────────────────────────────────   │
│                                             │
│ [Preview Filter Graph]                      │
│ ┌───────────────────────────────────────┐  │
│ │ -filter_complex "[0:v]...[v]" (read)  │  │
│ │ [Copy]                                │  │
│ └───────────────────────────────────────┘  │
│                                             │
│ [Save Overlay]                              │
└─────────────────────────────────────────────┘
```

**Funcții:**
- 3 overlay types: Logo, Text, Timer (independent toggles)
- Logo: upload + position + size + opacity
- Text: content type (dynamic/custom) + font size + bg opacity
- Timer: format + position
- Safe margins: slider 0-50px
- Auto-generate `-filter_complex` ffmpeg command
- Preview command (read-only)
- Copy btn

**Database (update LiveChannel model):**
```
- overlay_enabled (bool)
- overlay_logo_path (string)
- overlay_logo_position (TL/TR/BL/BR)
- overlay_logo_x (int)
- overlay_logo_y (int)
- overlay_logo_width (int)
- overlay_logo_height (int)
- overlay_logo_opacity (float 0-1)
- overlay_text_enabled (bool)
- overlay_text_content (channel_name / title / custom)
- overlay_text_custom (string)
- overlay_text_font_size (int)
- overlay_text_bg_opacity (float 0-1)
- overlay_text_bg_color (string #RRGGBB)
- overlay_timer_enabled (bool)
- overlay_timer_format (string)
- overlay_timer_position (TL/TR/BL/BR)
- overlay_timer_x (int)
- overlay_timer_y (int)
- overlay_safe_margin (int px)
```

---

### TAB 5: Stream Info (ffprobe)

```
┌────────────────────────────────────────────┐
│ Playlist Media Analysis                    │
├────────────────────────────────────────────┤
│ Scanning files with ffprobe...             │
│                                            │
│ File │ Codec│ FPS │ Bitrate│ Resolution  │
│──────┼──────┼─────┼────────┼─────────────┤
│ Vid1 │ h264 │ 25  │ 1500k  │ 720x576 ✅  │
│ Vid2 │ h264 │ 60  │ 2500k  │ 1280x720 ⚠️ │
│ Vid3 │ h265 │ 25  │ 1800k  │ 720x576 ⚠️  │
│
│ Legend:
│ ✅ = MATCH PROFILE
│ ⚠️  = NEEDS SCALE (mismatch FPS or resolution)
│ ❌ = WEIRD FPS (fractional, non-standard)
│
│ Hover badge = tooltip cu detalii
└────────────────────────────────────────────┘
```

**Funcții:**
- ffprobe cada video
- Extrage: codec, fps, bitrate, resolution, audio channels, sample rate
- Compare cu selected profile
- Badge: MATCH / NEEDS SCALE / WEIRD FPS

---

### TAB 6: Outputs

```
┌───────────────────────────────────────────┐
│ Stream Export URLs                        │
├───────────────────────────────────────────┤
│                                           │
│ HLS Stream (m3u8)                         │
│ ┌─────────────────────────────────────┐  │
│ │ http://46.4.20.56:2082/streams/     │  │
│ │ 3/index.m3u8                        │  │
│ │ [Copy]                              │  │
│ └─────────────────────────────────────┘  │
│ Status: ✅ Ready (Running) / ⚫ Idle      │
│                                           │
│ ─────────────────────────────────────     │
│                                           │
│ TS Stream (single file)                   │
│ ┌─────────────────────────────────────┐  │
│ │ http://46.4.20.56:2082/streams/     │  │
│ │ 3.ts                                │  │
│ │ [Copy]                              │  │
│ └─────────────────────────────────────┘  │
│ Status: ✅ Ready (Running) / ⚫ Idle      │
│                                           │
│ ─────────────────────────────────────     │
│                                           │
│ Status: ✅ BOTH OUTPUTS ACTIVE            │
│ Bandwidth Used: 7500 kbps                 │
│                                           │
│ [Copy All URLs] [Test with VLC]           │
└───────────────────────────────────────────┘
```

**Funcții:**
- Arată 2 URL-uri (HLS + TS)
- Copy button per URL
- Status badge (Ready/Idle)
- Combined status
- Total bandwidth estimate

---

## 📋 TASK C — Engine: Start/Stop per canal + Job runner

### UI în Channel Settings

```
┌────────────────────────────────────┐
│ Channel Engine Control             │
├────────────────────────────────────┤
│                                    │
│ Status: 🟢 LIVE STREAMING          │
│        (or ⚫ IDLE)                 │
│                                    │
│ [❚❚ STOP CHANNEL]  [▶ START]       │
│                                    │
│ Encoding Progress: 2/3 files       │
│ Current: Movie A (encoding...)     │
│                                    │
│ ────────────────────────────────   │
│ Log Viewer (last 100 lines)        │
│ ┌────────────────────────────────┐ │
│ │ [14:22] ffmpeg started         │ │
│ │ [14:22] Input: file.mp4        │ │
│ │ [14:25] Output: s=1280x720     │ │
│ │ [14:28] Stream started         │ │
│ │ ...                            │ │
│ │ [15:12] All files encoded      │ │
│ │                                │ │
│ │ [Auto-scroll] [Clear Log]      │ │
│ └────────────────────────────────┘ │
│                                    │
│ [Download Log]                     │
└────────────────────────────────────┘
```

### Funcționalități

**START CHANNEL:**
- Check: Are alle fișierele encode? (status din playlist)
- Dacă NU: start cu ce e gata, queue encoding pentru restul în fundal
- Pornește FFmpeg master process (TS + HLS dual output)
- Status = 🟢 LIVE
- Log real-time output

**STOP CHANNEL:**
- Kill FFmpeg process
- Status = ⚫ IDLE
- Păstrează HLS segments și TS cache

**Engine Status:**
- Idle (nu rulează)
- Encoding (background jobs)
- Live (streaming active)
- Error (issue log)

**Job Runner:**
- Background queue (Laravel jobs)
- EncodeVideoJob (per file)
- UploadAssetJob (dacă configured)
- Retry logic (3x)

---

## 📋 TASK D — Dual Output: TS + HLS (AMBELE)

### Implementare

Un singur **master FFmpeg process** per canal cu **2 outputs**:

```bash
ffmpeg -re -i PLAYLIST_CONCAT \
  -c:v libx264 \
  -c:a aac \
  \
  # OUTPUT 1: TS (HTTP server local)
  -f mpegts -listen 1 "http://127.0.0.1:9100+{id}/stream.ts" \
  \
  # OUTPUT 2: HLS (segments pe disk)
  -f hls -hls_time 4 -hls_list_size 8 \
  -hls_flags delete_segments+append_list \
  "/var/www/iptv-panel/public/streams/{id}/index.m3u8"
```

### Nginx Proxy (TS)

```nginx
# /etc/nginx/snippets/iptv_ts.conf
location = /streams/1.ts { proxy_pass http://127.0.0.1:9101/stream.ts; proxy_read_timeout 1d; }
location = /streams/2.ts { proxy_pass http://127.0.0.1:9102/stream.ts; proxy_read_timeout 1d; }
location = /streams/3.ts { proxy_pass http://127.0.0.1:9103/stream.ts; proxy_read_timeout 1d; }
# ... auto-generated per channel
```

### Accepte

- [x] TS stream disponibil live la http://46.4.20.56:2082/streams/{id}.ts
- [x] HLS stream disponibil live la http://46.4.20.56:2082/streams/{id}/index.m3u8
- [x] Ambele URL-uri afișate în Outputs TAB
- [x] Nginx proxy functional
- [x] VLC playback pe ambele
- [x] Xtream Codes import pe ambele

---

## 📋 TASK E — Auto Upload (after encoding)

### Funcționalitate

După ce FFmpeg termină encoding un fișier VOD:

1. **Local storage** (default):
   - Mută automat în `/storage/app/private/vod_ts/{channel_id}/{filename}.ts`

2. **Remote upload** (optional):
   - UI: Settings → Storage targets
   - Suporta: SFTP, HTTP (POST), S3
   - Queue: `UploadEncodedAssetJob`
   - Status tracking: Pending / Done / Failed

### UI

```
┌─────────────────────────────────┐
│ Upload Configuration            │
├─────────────────────────────────┤
│ Default Storage:                │
│ ◉ Local (/storage/app/private)  │
│ ○ Remote                        │
│                                 │
│ [+ Add Upload Target]           │
│                                 │
│ Target: "SFTP Server"           │
│ Type: SFTP                      │
│ Host: ftp.example.com           │
│ Path: /vod/                     │
│ [Delete]                        │
│                                 │
│ ────────────────────────────    │
│                                 │
│ Recent Uploads                  │
│ ┌─────────────────────────────┐ │
│ │ Movie A (1.2GB) - Done ✅   │ │
│ │ Trailer (250MB) - Done ✅   │ │
│ │ Movie B (1.8GB) - Pending ⏳ │ │
│ │ Series S01E01 (500MB) - Fail │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

### Database

```
- uploads table:
  id, asset_id, target_id, status (pending/done/failed), 
  progress (0-100), error_msg, created_at, updated_at

- upload_targets table:
  id, channel_id, name, type (local/sftp/http/s3), 
  config (JSON), created_at
```

### Funcții

- Create/Edit/Delete upload targets
- Auto-trigger UploadEncodedAssetJob după encoding
- UI: upload status per fișier
- Retry failed uploads
- Cleanup: delete source după upload (opțional)

---

## ✅ CHECKLIST FINAL — CE ÎMI LIVREZI (FĂRĂ EXCUZE)

### TASK A — Encode Profiles Page
- [x] Pagina /admin/encode-profiles
- [x] Lista profiluri cu carduri
- [x] Create/Edit/Duplicate/Delete buttons
- [x] Form cu toți parametrii (resolution, fps, codec, bitrate, etc.)
- [x] Mode toggle LIVE/VOD
- [x] Database updated (EncodeProfile model)
- [x] Validation (no empty names, valid resolutions)

### TASK B — Channel Settings (Refactor)
- [x] TAB 1 General: name, category, mode, description
- [x] TAB 2 Playlist: video list + Queue Encode button
- [x] TAB 3 Encoding: profile dropdown + manual override + preview FFmpeg
- [x] TAB 4 Overlay: logo + text + timer config + filter preview
- [x] TAB 5 Stream Info: ffprobe results + badges (MATCH / NEEDS SCALE / WEIRD)
- [x] TAB 6 Outputs: 2 URL-uri + Copy buttons + status
- [x] Database columns added (overlay_*, etc.)
- [x] Save functionality (persist alle tab changes)

### TASK C — Engine
- [x] Start/Stop buttons funcționali
- [x] Status indicator (Idle / Encoding / Live)
- [x] Log viewer (real-time, 100 lines)
- [x] Job queue (background encoding)
- [x] Smart start: dacă nu sunt toate encode, start cu ce-i gata
- [x] FFmpeg process management (start/kill)

### TASK D — Dual Output
- [x] Master FFmpeg cu TS + HLS outputs
- [x] TS: HTTP server local + Nginx proxy
- [x] HLS: segments pe disk
- [x] Ambele URL-uri afișate în UI
- [x] Nginx config auto-generated per channel
- [x] Testing: curl + VLC pe ambele

### TASK E — Auto Upload
- [x] Storage targets UI (create/edit/delete)
- [x] Local storage (default)
- [x] Remote upload (SFTP, HTTP, S3)
- [x] Queue job (UploadEncodedAssetJob)
- [x] Upload status tracking UI
- [x] Retry mechanism

---

## 📊 PUNCTE IMPORTANTE

### ❌ NU MODIFICA (păstrează dacă lucrezz)
- Database schema fără notificare
- Rutele existente (web.php, console.php)
- Orice cod care nu-i în scopul task-ului
- Playlist drag-drop (deja lucreaza)

### ✅ FACI
- UI clean pe tab-uri (Tailwind, dark theme)
- Toți parametrii configurabili
- Validări în backend + frontend
- Error handling clar
- Log output complet

### ⚠️ GOTCHAS
- FFmpeg preview = read-only, nu executa
- Overlay filtergraph = test cu real ffmpeg după
- TS port formula = 9100 + channel_id
- Safe margins = aplica la toate overlay layers
- Upload targets = encrypt passwords în config JSON

---

## 🚀 DELIVERABLES CHECKLIST

Trimite cu dovada:

1. **Screenshots**:
   - Encode Profiles page (lista + edit modal)
   - Channel Settings cu 6 tab-uri
   - Engine control + logs
   - Outputs tab cu ambele URL-uri

2. **Database**:
   - Migrations pentru overlay columns
   - Migrations pentru upload_targets + uploads

3. **Code**:
   - Controllers: EncodingProfileController, LiveChannelController (refactored)
   - Models: EncodeProfile (updated), LiveChannel (updated), UploadTarget, Upload
   - Jobs: EncodeVideoJob, UploadEncodedAssetJob
   - Services: FFmpegCommandBuilder (refactored cu overlay)
   - Views: profiles page + channel/settings (tab layout)

4. **Testing**:
   - FFmpeg preview command (copy-able)
   - Start/Stop functional
   - Upload tracking functional
   - No database errors

---

## 📞 DACĂ TE BLOCHEZI

1. Check TASK_4_DETAILED.md pentru FFmpeg reference
2. Check database migrations existente
3. Check existing Controllers/Models
4. Ask → clear requirements answer (nu cod, doar clarificare)

---

**Timeline: ~2-3 săptămâni** (depinde de testing)  
**Dificultate: ⭐⭐⭐⭐** (full UI refactor + infrastructure)

**Good luck! 🚀**
