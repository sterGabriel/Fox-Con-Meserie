# QUICK TEST GUIDE - TASKS 1-5

## Prerequisites
- ✅ Server has FFmpeg + ffprobe installed
- ✅ Videos available in a server folder (e.g., `/mnt/media/TEST/`)
- ✅ Database migrated: `php artisan migrate`
- ✅ Storage linked: `php artisan storage:link`

---

## TEST SEQUENCE (10 minutes)

### 1️⃣ Test TASK 1 - Delete Playlist Item (1 min)
**Location**: Settings → Playlist tab

1. Click "Add Video" to add any video to playlist
2. Click "Remove" button next to the video
3. Confirm dialog appears
4. Item disappears without page reload
5. ✅ **PASS**: No 405 Method Not Allowed error

---

### 2️⃣ Test TASK 2 - Import Videos from Server (4 min)
**Location**: Video Categories → Select category → "Import" button

1. Click "Import" next to any category
2. Enter server folder path (e.g., `/mnt/media/TEST`)
3. Click "🔍 Scan Folder"
4. **Wait 5-10 seconds** for folder scan
5. Files appear with metadata:
   - Filename, Size, Duration, Modified date
   - ✅ Duration shows (e.g., 01:23:45)
6. Click Info button on any file → Modal shows:
   - Video: codec, resolution, FPS, bitrate
   - Audio: codec, channels, sample rate, bitrate
7. Select multiple files → "Select All" checkbox works
8. Click "⬆️ Import Selected"
9. Success message + files added to Video Library
10. ✅ **PASS**: All files imported with metadata

---

### 3️⃣ Test TASK 3 - Video Info Modal (1 min)
**Location**: Settings → Playlist tab → Available Videos table

1. In "Available Videos" list, click "ℹ️ Info" button
2. Modal appears instantly (< 1 second)
3. Shows:
   - Title, ID, Duration, Category
   - File path
   - Video codec, resolution, FPS, bitrate
   - Audio codec, channels, sample rate, bitrate
4. Click X or background to close
5. ✅ **PASS**: Metadata loads fast and accurate

---

### 4️⃣ Test TASK 4 - Preview Overlay Test 10s (2 min)
**Location**: Settings → Overlay tab → "Test Preview Overlay" section

1. Select a video from dropdown
2. Click "▶️ Test Overlay (10s)" button
3. Loading indicator shows
4. **Wait 30-60 seconds** for FFmpeg to encode
5. Video player appears with preview MP4
6. Click Play → Video plays (should be ~10 seconds)
7. ✅ **PASS**: Preview generates with overlay baked in

---

### 5️⃣ Test TASK 5.1 - Encode All to TS (2 min)
**Location**: Settings → Playlist tab → "🎬 Encode All to TS" button

1. Add 2-3 videos to playlist
2. Click "🎬 Encode All to TS (Offline)" button
3. Progress bar appears: "Starting encode jobs..."
4. Shows "X/Y complete" as encoding progresses
5. **Wait 2-5 minutes** (depends on video length + count)
6. Bar reaches 100% → "All videos encoded successfully!"
7. Check disk: `ls storage/app/streams/{channel_id}/`
   - Should see: `video_1.ts`, `video_2.ts`, etc. (real files, not empty)
8. File sizes should match video length (not 0 bytes)
9. ✅ **PASS**: Real TS files created on disk

---

### 6️⃣ Test TASK 5.2 - Play from Encoded TS (1 min)
**Location**: Settings → Engine tab

1. After encoding complete (from step 5), click "▶ START CHANNEL" button
2. Success message should show: "PLAY (from X encoded TS files)"
3. Check logs for: FFmpeg process started with concat command
4. **Optional**: Play channel in VLC:
   - VLC → File → Network Stream → `http://localhost:6001/stream.ts`
   - Should see video playing smoothly
5. Click "❚❚ STOP CHANNEL" to stop
6. ✅ **PASS**: Channel starts in PLAY mode using encoded files

---

## 🔍 Verification Checklist

### Database
```bash
# Check migrations ran
php artisan migrate:status | grep "video_categories\|source_path"

# Check video categories
php artisan tinker
>>> \App\Models\VideoCategory::all();

# Check encoded jobs
>>> \App\Models\EncodingJob::all();
```

### Files
```bash
# Check encoded TS files exist
ls -lh storage/app/streams/*/

# Check preview files
ls -lh storage/app/public/previews/*/

# Check video metadata in DB
sqlite3 database/database.sqlite "SELECT id, title, duration, metadata FROM videos LIMIT 1;"
```

### Network (DevTools F12)
```
POST /vod-channels/{id}/engine/start-encoding → 200 OK, status: success
GET /vod-channels/{id}/engine/encoding-jobs → 200 OK, total_jobs: X
GET /videos/{id}/info → 200 OK, metadata returned
```

---

## ⚡ Performance Notes

| Task | Expected Time | Actual |
|------|----------------|--------|
| TASK 1 (Delete) | <1 sec | Instant ✅ |
| TASK 2 (Scan) | 5-10 sec | Depends on folder size |
| TASK 3 (Info) | <1 sec | Instant ✅ |
| TASK 4 (Preview 10s) | 30-60 sec | Depends on video |
| TASK 5.1 (Encode all) | 2-5 min | Per playlist length |
| TASK 5.2 (Play) | <3 sec | Very fast ✅ |

---

## 🐛 Troubleshooting

### Scan doesn't find files
- Check folder path exists: `ls /mnt/media/TEST/`
- Check file extensions are supported: .mp4 .mkv .avi .mov .webm .flv .wmv .ts
- Check read permissions: `ls -la /mnt/media/TEST/`

### Preview encoding fails
- Check FFmpeg installed: `which ffmpeg`
- Check video file readable: `file /path/to/video.mp4`
- Check storage path writable: `ls -la storage/app/public/`

### Encoding produces empty TS files
- Check EncodingService config
- Verify overlay settings (might cause issues)
- Check FFmpeg errors in logs: `tail -50 storage/logs/laravel.log`

### Play mode not working
- Verify TS files exist: `ls storage/app/streams/{id}/`
- Check ChannelEngineService.generatePlayCommand()
- Test manually: `ffmpeg -i stream1.ts -i stream2.ts -filter_complex "[0:v][0:a][1:v][1:a]concat=n=2:v=1:a=1[vout][aout]" -map "[vout]" -map "[aout]" output.ts`

---

## 📋 Success Criteria

### ✅ All TASKS PASSED if:
1. Playlist delete works without errors
2. Files can be imported from server folder
3. Info modals load in < 1 second
4. 10-second preview encodes and plays
5. TS files appear on disk after encoding
6. Channel starts using encoded files (not fallback)

### 🚀 Ready for TASK 6 if:
- All above ✅ PASS
- Files are real (not empty)
- No errors in logs
- Network requests show 200 OK responses

---

**Estimated total test time: 15-20 minutes** ⏱️
