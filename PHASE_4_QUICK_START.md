# Phase 4 - Quick Start Guide

## What's New

✅ **Real FFmpeg Engine** - Start/stop actual processes, not mock buttons  
✅ **Dual Streaming** - MPEGTS (Xtream) + HLS (Browser) simultaneously  
✅ **24/7 Looping** - Seamless playlist looping with concat demuxer  
✅ **Test Preview** - Generate 10-second MP4 with your overlay  
✅ **Live Dashboard** - Real-time status, logs, stream URLs  

---

## Getting Started

### 1. Open Engine Tab

Go to **VOD Channel** → **Settings** → **Engine Tab**

You'll see:
- 🟢 Status display (IDLE / LIVE STREAMING / 24/7 LOOPING)
- ▶ START CHANNEL button (green)
- 🔄 START 24/7 LOOP button (blue)
- ❚❚ STOP CHANNEL button (red)
- 🎥 TEST OVERLAY button (purple)
- Live log viewer

### 2. Test the Preview

Click **🎥 TEST OVERLAY (10s)**
- Generates a 10-second MP4 with your overlay applied
- Shows in the preview player below the buttons
- Verifies overlay filters are working correctly

### 3. Start Streaming

**Option A: Single Pass**
```
Click ▶ START CHANNEL
↓
FFmpeg starts (real process)
Status shows 🟢 LIVE STREAMING
Logs show ffmpeg output
Both TS and HLS streams available
```

**Option B: 24/7 Loop**
```
Click 🔄 START 24/7 LOOP
↓
Generates concat playlist from all channel videos
FFmpeg starts with concat demuxer
Status shows 🔄 24/7 LOOPING
Videos loop seamlessly forever (or until you click STOP)
```

### 4. Get Stream URLs

Go to **Outputs Tab**

You'll see two streams:
- **TS Stream**: `http://46.4.20.56:2082/streams/{id}.ts`
  - Use in: Xtream Codes, streaming apps
  - Copy the URL and add to your IPTV client

- **HLS Stream**: `http://46.4.20.56:2082/streams/{id}/index.m3u8`
  - Use in: VLC, browsers, web players
  - Works through firewalls, better compatibility

### 5. Stop Streaming

Click **❚❚ STOP CHANNEL**
- Gracefully stops ffmpeg process
- Status shows ⚫ IDLE
- Both streams become unavailable

---

## API Endpoints (For Developers)

### Start Channel (Single Pass)
```bash
POST /vod-channels/{channel}/engine/start
Response: { status, message, pid, job_id }
```

### Start Channel (24/7 Looping)
```bash
POST /vod-channels/{channel}/engine/start-looping
Response: { status, message, mode: "24/7 LOOPING", pid, job_id }
```

### Stop Channel
```bash
POST /vod-channels/{channel}/engine/stop
Response: { status, message }
```

### Get Status
```bash
GET /vod-channels/{channel}/engine/status
Response: { status: { is_running, pid, ... }, logs }
```

### Get Stream URLs
```bash
GET /vod-channels/{channel}/engine/outputs
Response: { is_running, streams: [...] }
```

### Generate Preview
```bash
POST /vod-channels/{channel}/engine/test-preview
Response: { status, preview_url }
```

---

## Real-World Examples

### Example 1: Add Channel to Xtream Codes

1. Create VOD Channel with videos
2. Go to Engine tab
3. Click "▶ START CHANNEL"
4. Go to Outputs tab
5. Copy **TS URL** (http://.../{id}.ts)
6. In Xtream Codes:
   - Add → Live Channel
   - Paste TS URL
   - Save
7. Enjoy! Stream appears in IPTV app

### Example 2: 24/7 Live Broadcast

1. Create VOD Channel with 5 videos (each 1 hour)
2. Go to Engine tab
3. Click "🔄 START 24/7 LOOP"
4. Watch logs show:
   ```
   🔄 Starting 24/7 looping mode...
   📝 Generating concat playlist from channel videos
   ✅ Channel started with 24/7 looping (PID: 12345)
   🎬 All videos will loop seamlessly
   ```
5. All viewers see same synchronized stream for 24+ hours
6. When first video ends, seamlessly transitions to next (no black frame)
7. After last video, loops back to first

### Example 3: Test Before Going Live

1. Upload 3 short videos to channel
2. Configure overlay (text, logo, timer)
3. Click "🎥 TEST OVERLAY (10s)"
4. Watch preview video in panel - see how overlay looks
5. Adjust overlay if needed
6. Click "▶ START CHANNEL" when happy
7. Add stream to production IPTV

---

## Important Files

| File | Purpose |
|------|---------|
| `app/Services/ChannelEngineService.php` | Process management (Start/Stop/Status) |
| `app/Http/Controllers/Admin/LiveChannelController.php` | API endpoints |
| `routes/web.php` | Route definitions |
| `resources/views/.../engine.blade.php` | Engine control UI |
| `resources/views/.../outputs.blade.php` | Stream URLs UI |
| `/storage/logs/channel_{id}.log` | FFmpeg output logs |
| `/storage/app/streams/{id}/playlist.txt` | Concat playlist (looping) |
| `/storage/app/streams/{id}/stream.ts` | MPEGTS stream file |
| `/storage/app/streams/{id}/hls/stream.m3u8` | HLS master playlist |

---

## Database Tracking

When you start a channel:
- `live_channels.encoder_pid` = FFmpeg process ID
- `live_channels.started_at` = Timestamp when started
- `encoding_jobs.channel_id` = Link to channel
- `encoding_jobs.pid` = FFmpeg process ID
- `encoding_jobs.log_path` = Path to logs
- `encoding_jobs.status` = "running" / "done"

All automatically managed by the service.

---

## Troubleshooting

### Problem: START button doesn't work
```
Solution: Check browser console for errors
          Make sure channel has at least one video
          Check storage/logs/channel_{id}.log for ffmpeg errors
```

### Problem: Preview video doesn't generate
```
Solution: First video's file must exist
          Check storage permissions: chmod -R 755 storage/
          Check ffmpeg binary: which ffmpeg
```

### Problem: Streams not accessible
```
Solution: Check Nginx is configured correctly
          Check files exist: ls -la storage/app/streams/1/
          Test HTTP: curl http://localhost/streams/1.ts
```

### Problem: Looping has gaps between videos
```
Solution: Make sure all videos in playlist have correct file_path
          Check concat playlist: cat storage/app/streams/1/playlist.txt
          All paths should be valid, absolute paths
```

---

## Keyboard Shortcuts

While logs are focused:
- Ctrl+C - Copy selected log text
- Ctrl+A - Select all logs
- Ctrl+L - Clear logs (same as button)

---

## What's Happening Behind the Scenes

When you click **START CHANNEL**:

1. Your click hits the API endpoint `/vod-channels/{id}/engine/start`
2. Controller generates FFmpeg command based on:
   - Channel's encode profile (bitrate, codec, resolution)
   - First video in playlist
   - Overlay settings (text, logo, timer)
3. Real FFmpeg process is started via Symfony\Process
4. Process PID is saved to database
5. FFmpeg begins reading video file and encoding
6. Output is written to both:
   - `/storage/app/streams/{id}/stream.ts` (MPEGTS)
   - `/storage/app/streams/{id}/hls/stream.m3u8` (HLS)
7. Logs are captured and displayed live in panel
8. UI polls `/engine/status` every 2 seconds to update display

When you click **STOP CHANNEL**:

1. API sends SIGTERM signal to FFmpeg process
2. FFmpeg gracefully shuts down (5 second timeout)
3. If still running, SIGKILL is sent (force kill)
4. Database is updated: status = idle, pid = null
5. Stream files become inaccessible

---

## Performance Tips

1. **Keep Playlist Short**: Fewer videos = faster concat playlist generation
2. **Use H.264 Codec**: Works everywhere, good quality/bitrate tradeoff
3. **Optimal Resolution**: 1920x1080 for standard HD, 1280x720 for bandwidth-limited
4. **Bitrate Settings**: 1500k video + 128k audio = ~3 Mbps per channel
5. **HLS Segment Time**: 10 seconds is standard, reduces to 5 for lower latency

---

## Security Notes

- All FFmpeg commands are properly escaped (shell injection safe)
- Process execution uses `Symfony\Process` (safe execution)
- File paths validated before execution
- Database tracking prevents unauthorized process spawning
- Logs don't expose sensitive paths

---

## Next Step: Production Deployment

See `PHASE_4_COMPLETION_REPORT.md` for full deployment guide including:
- Nginx configuration
- Storage setup
- FFmpeg verification
- Database migrations
- Testing checklist

---

**Status**: ✅ Ready to Use  
**Last Updated**: 2025-12-15  
**Support**: Check `/storage/logs/` for detailed error logs
