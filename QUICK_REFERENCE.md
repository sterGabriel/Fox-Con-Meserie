# QUICK REFERENCE CARD - TASK 0-6

## 🚀 QUICK START

### Access the Application
```
http://localhost:8000
http://46.4.20.56:2082  (if deployed)
```

### Navigate to Features
1. **Home**: Dashboard
2. **Video Categories**: Manage + Import videos
3. **VOD Channels**: Create channels and manage playback

---

## 📋 FEATURES AT A GLANCE

| Feature | Location | Steps |
|---------|----------|-------|
| **DELETE** | Playlist Tab | Select video → Delete → Confirm |
| **FILE BROWSER** | Categories → Browse & Import | Click folder → select videos → Import |
| **INFO MODAL** | Playlist Tab | Click info icon on video |
| **PREVIEW 10s** | Overlay Tab | Select video → Test Overlay |
| **ENCODE TS** | Playlist Tab | Click Encode All → Monitor progress |
| **PLAY** | Engine Tab | Click Start Channel |
| **OUTPUTS** | Engine Tab | See URLs when channel running |

---

## 🔧 REQUIRED SETUP

**Before Testing**:
```bash
# 1. Ensure /home/movies exists
mkdir -p /home/movies

# 2. Ensure /streams directory is writable
mkdir -p /streams
chown www-data:www-data /streams
chmod 755 /streams

# 3. Verify FFmpeg installed
which ffmpeg ffprobe

# 4. Test the app loads
php artisan tinker  # Should start Psy Shell

# 5. Check routes
php artisan route:list | grep video
```

---

## 📂 KEY DIRECTORIES

```
/home/movies              - Source videos for import
/streams/{channel_id}/    - Streaming output
  ├─ encoded/             - Encoded .ts files
  ├─ live.ts              - TS stream (active when running)
  └─ index.m3u8           - HLS playlist (active when running)
```

---

## 🎯 TESTING SEQUENCE

### 1. FILE BROWSER (TASK 2)
```
Video Categories → "Browse & Import" on any category
→ Verify you can see folder tree + breadcrumb
→ Click a video → shows metadata (size, duration, codec)
```

### 2. IMPORT VIDEO
```
Select 2-3 videos with checkboxes
→ Click "Import Selected"
→ Should show success message
→ Refresh: videos should show ✅ badge when imported
```

### 3. DELETE (TASK 1)
```
VOD Channels → select a channel → Playlist tab
→ Find a video in the list
→ Click Delete → confirm
→ DevTools Network tab should show DELETE request
→ Item disappears
```

### 4. PREVIEW (TASK 4)
```
VOD Channels → select channel → Settings → Overlay tab
→ Select video from dropdown
→ Click "Test Overlay (10s)"
→ 10-second preview plays with overlay visible
```

### 5. ENCODE (TASK 5)
```
VOD Channels → select channel → Settings → Playlist tab
→ Click "Encode All to TS"
→ Watch progress bar
→ Terminal: ls -lh /streams/{channel_id}/encoded/
→ Should see multiple .ts files
```

### 6. PLAY (TASK 6)
```
VOD Channels → select channel → Settings → Engine tab
→ Click "START CHANNEL"
→ Status should show "🟢 PLAYING"
→ DevTools Performance: CPU should be < 20%
→ Let run 10+ minutes → CPU stays stable
```

### 7. STREAMING URLs (TASK 7)
```
Engine tab → Scroll down to "Streaming Outputs"
→ Should show TS URL + HLS URL
→ Copy each URL
→ Open VLC → Media → Open Network Stream
→ Paste TS URL → should play stream
→ Paste HLS URL → should also play stream
```

---

## 🐛 TROUBLESHOOTING QUICK FIXES

### "Page not found" when accessing browser
- Check route registered: `php artisan route:list | grep browse`
- Clear cache: `php artisan cache:clear`

### File browser shows empty
- Ensure `/home/movies` exists: `mkdir -p /home/movies`
- Change base path in `FileBrowserController.php` line 12

### Encoding fails
- Check FFmpeg: `which ffmpeg`
- Check permissions: `ls -ld /streams`
- Check disk space: `df -h /`

### Streaming URLs don't show
- Start channel first (click START CHANNEL)
- Wait 3 seconds for status to update
- Check process running: `ps aux | grep ffmpeg | grep -v grep`

### CPU too high during PLAY
- Stop channel, encode videos, then play from .ts files
- Don't use realtime encoding + playback together

### VLC can't connect to stream
- Verify channel is running: check status in Engine tab
- Try direct URL in browser first: `http://host/streams/{id}/live.ts`
- Check firewall: `sudo ufw allow 5000 5001 5002` (or your ports)

---

## 📊 IMPORTANT CONFIGS

**Edit if needed**:
```
.env              - APP_URL, database connection
config/app.php    - app.streaming_domain
FileBrowserController.php line 12 - base path for videos
```

**Example .env changes**:
```bash
APP_URL=http://46.4.20.56:2082
DB_HOST=127.0.0.1
DB_DATABASE=iptv_panel
BROADCAST_DRIVER=log
```

---

## 🎮 VLC TESTING COMMANDS

**Command Line** (instead of GUI):
```bash
# Test TS stream
vlc http://localhost/streams/1/live.ts

# Test HLS playlist
vlc http://localhost/streams/1/index.m3u8

# Specific time
vlc http://localhost/streams/1/live.ts --start-time=30
```

---

## 📱 API ENDPOINTS (for reference)

```
GET    /video-categories/{id}/browse      - File browser
POST   /video-categories/{id}/import      - Import files
POST   /vod-channels/{id}/playlist        - Add to playlist
DELETE /vod-channels/{id}/playlist/{item} - Remove from playlist
POST   /vod-channels/{id}/engine/start    - Start channel
POST   /vod-channels/{id}/engine/stop     - Stop channel
GET    /vod-channels/{id}/engine/status   - Check status
POST   /vod-channels/{id}/engine/test-preview - Test overlay
POST   /vod-channels/{id}/engine/start-encoding - Encode to TS
```

---

## ✅ ACCEPTANCE CRITERIA CHECKLIST

Before claiming "working":
- [ ] Feature functions as designed
- [ ] No errors in browser console
- [ ] No 404/500 responses in Network tab
- [ ] Database records created/updated correctly
- [ ] Files written to correct directories
- [ ] Performance acceptable (CPU < 20%, no memory leaks)

Before claiming "production ready":
- [ ] All features above ✅
- [ ] Screenshots of DevTools Network tab
- [ ] Screenshots of DevTools Performance
- [ ] VLC screenshots showing actual playback
- [ ] Stress tested (10+ minutes running)
- [ ] Error logs reviewed (no critical errors)

---

## 📞 QUICK HELP

**Feature not showing?**
1. Check route exists: `php artisan route:list | grep keyword`
2. Check view exists: `ls resources/views/admin/...`
3. Clear view cache: `php artisan view:clear`

**Getting 405 Method Not Allowed?**
- Make sure POST form has `@method('DELETE')` for DELETE operations
- Check route definition is `Route::delete()` not `Route::post()`

**Getting 500 error?**
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Run: `php artisan config:cache`
- Check database: `php artisan tinker` → `DB::connection()->getPdo()`

**Need to reset everything?**
```bash
php artisan migrate:fresh --seed
php artisan cache:clear
php artisan view:clear
php artisan storage:link
```

---

**Last Updated**: December 15, 2024  
**Status**: ✅ All 7 Tasks Implemented  
**Version**: 1.0  
