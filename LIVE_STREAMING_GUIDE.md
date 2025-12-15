# 📡 IPTV Dashboard - LIVE Streaming Configuration Guide

## Overview

The IPTV Panel now includes a comprehensive **LIVE Encoding System** for 24/7 streaming channels. This guide covers all features and how to use them.

---

## Features

### 1. 🎬 LIVE Encoding Profiles
Pre-configured profiles optimized for 24/7 TV streaming:
- **576p SD** (1500 kbps) - Low bandwidth
- **720p Default** (2500 kbps) - Standard quality
- **1080p HQ** (5000 kbps) - High quality
- **720p Low CPU** (2000 kbps) - CPU efficient

**Why profiles?** They handle all the complex ffmpeg parameters:
- MPEGTS container format (TS streams)
- Constant bitrate (CBR) for stability
- Proper PCR/PAT/PMT periods
- 48kHz audio standard for TV
- 25 fps constant frame rate

### 2. 📊 FFmpeg Command Preview
See the exact command that will be executed:
1. Go to **Channel Settings**
2. Select a LIVE profile from dropdown
3. Click **🔄 Refresh** button
4. Preview shows complete ffmpeg command

### 3. 🔧 Manual Override (Advanced)
Need custom settings? Use Advanced mode:
1. Check **🔧 Manual Override (Advanced)**
2. Set custom bitrate and encoder preset
3. Preview updates automatically
4. Settings saved with channel configuration

### 4. ♾️ 24/7 Playlist Looping
Infinite streaming of your playlist:
- Automatically loops through all videos
- ~1000 iterations = 83 days of content (for 2-hour videos)
- Uses concat demuxer for seamless transitions
- No manual restart needed

**How it works:**
1. Add videos to channel playlist (drag to reorder)
2. Queue encoding jobs - system generates looping command
3. ffmpeg reads from `playlist_{channel_slug}.txt`
4. Uses `-stream_loop -1` for infinite playback

### 5. 📺 Stream Info (Video Metadata)
Check video properties before adding to playlist:
1. Go to **Video Library** tab in Playlist view
2. Click **📊 Info** button next to any video
3. See:
   - Duration and total bitrate
   - Video codec, resolution, FPS
   - Audio codec, channels, sample rate
4. Help decide which profile is best suited

---

## Setup Guide

### Step 1: Create a Channel
1. Click **Channels** → **Create Channel**
2. Give it a name (e.g., "Sports 24/7")
3. Continue to next step

### Step 2: Configure Channel Settings
1. Go to channel → **Settings** tab
2. Set basic encoding (resolution, bitrate, fps, audio codec)
3. Select **LIVE Streaming Profile** from dropdown
   - Choose based on your internet bandwidth
   - See preview command at bottom
4. Click **💾 Save Settings**

### Step 3: Build Your Playlist
1. Go to channel → **Playlist** tab
2. **Video Library** section (right side):
   - Use **📊 Info** to check video properties
   - Click **➕ Select** to add to playlist
   - Or check multiple boxes and click **➕ Add selected videos**
3. **Current Playlist** section (left side):
   - Drag videos to reorder
   - Click **💾 Save order**
   - Use ↑↓ buttons or drag for manual ordering

### Step 4: Queue Encoding Jobs
1. In Playlist tab, click **🚀 Queue Encoding Jobs**
   - Creates jobs for all videos in playlist
   - Saves ffmpeg command from selected profile
   - Jobs remain pending until executed

### Step 5: Start Streaming
1. Go to **Encoding Jobs** tab
2. View pending/processing jobs
3. Each job shows:
   - Video being encoded
   - Status (pending/processing/done)
   - FFmpeg command to be executed
4. Execute job (when backend processor runs)
   - Output goes to configured RTMP/HLS output

---

## Advanced Topics

### Custom FFmpeg Parameters
In **Settings → Manual Override**:
- **Bitrate**: 500-20000 kbps
- **Preset**: superfast, veryfast, fast, medium
  - superfast = less quality, less CPU
  - medium = best quality, more CPU

### Profile Details (What They Do)

#### 720p Default Profile
```
Resolution: 1280x720
Bitrate: 2500 kbps (CBR)
FPS: 25 (constant frame rate)
Audio: 128 kbps AAC @ 48kHz
Container: MPEGTS (TS format)
Codec: H.264 (libx264)
Preset: veryfast
```

#### 1080p HQ Profile
```
Resolution: 1920x1080
Bitrate: 5000 kbps (CBR)
FPS: 25 (constant frame rate)
Audio: 128 kbps AAC @ 48kHz
Container: MPEGTS (TS format)
Codec: H.264 (libx264)
Preset: fast
```

### Concat Demuxer Playlist Format
Generated automatically, but here's what it looks like:
```
file '/path/to/video1.mp4'
file '/path/to/video2.mp4'
file '/path/to/video3.mp4'
file '/path/to/video1.mp4'
file '/path/to/video2.mp4'
... (repeated 1000 times)
```

Uses `-f concat -i playlist.txt` flag in ffmpeg.

---

## Troubleshooting

### Preview Command Not Loading
- Check browser console (F12) for errors
- Ensure profile is selected in dropdown
- Verify database has LIVE profiles seeded

### Video Not Showing in Info Modal
- Ensure ffprobe is installed on system
- Check file path exists and is readable
- Try with different video format

### Jobs Not Queuing
- Verify playlist has videos
- Check channel settings saved
- Ensure profile exists (or default is available)

### Stream Quality Issues
- Use lower bitrate profile (576p/720p)
- Check input video bitrate (Info button)
- Reduce FPS in manual override
- Use "superfast" preset to reduce CPU

### Audio Issues (No Sound)
- Verify input video has audio stream
- Check audio codec compatibility (AAC, MP3, AC3)
- Try copying audio instead of re-encoding

---

## File Locations

- **Encoding Profiles**: Database table `encode_profiles`
- **Playlist Files**: `storage/app/temp/playlist_*.txt`
- **FFMPEG Output**: Configured in channel settings (output paths)
- **Job History**: Database table `encoding_jobs` (includes ffmpeg_command)

---

## API Endpoints

### Preview FFmpeg Command
```
POST /vod-channels/{channel_id}/preview-ffmpeg
Body: {
  profile_id: 1,
  manual_enabled: false,
  manual_bitrate: 2500,
  manual_preset: "veryfast"
}
Response: { command: "ffmpeg -re ...", profile_name: "720p Default" }
```

### Video Metadata (ffprobe)
```
GET /videos/{video_id}/probe
Response: {
  title: "Video Title",
  duration: 3600,
  bit_rate: "2500 kbps",
  video: { codec, width, height, fps, bitrate, ... },
  audio: { codec, channels, sample_rate, bitrate, ... }
}
```

---

## Performance Tips

1. **Use Profiles, Not Manual Override**
   - Pre-configured = optimized
   - Faster processing

2. **Match Profile to Internet**
   - 1 Mbps upload → 576p/720p
   - 5+ Mbps upload → 1080p

3. **Monitor CPU**
   - Use "superfast" preset if CPU high
   - Consider H.265 for lower bitrate

4. **Playlist Size**
   - 100+ videos = larger playlist file
   - Still works fine with 1000x looping

5. **Video Format**
   - MP4 with H.264/H.265 = fastest
   - Convert others to MP4 beforehand

---

## Common Commands (Manual Use)

If using ffmpeg CLI directly:

### Basic LIVE Stream (720p)
```bash
ffmpeg -re -i input.mp4 \
  -c:v libx264 -preset veryfast \
  -vf "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2,format=yuv420p" \
  -r 25 -vsync cfr -g 50 \
  -b:v 2500k -maxrate 2500k -bufsize 5000k \
  -c:a aac -b:a 128k -ar 48000 -ac 2 \
  -f mpegts -mpegts_flags +resend_headers \
  -mpegts_service_name "Channel Name" \
  -pat_period 5000 -pmt_period 5000 -pcr_period 5000 \
  rtmp://localhost/live/channel
```

### Infinite Playlist Loop
```bash
ffmpeg -stream_loop -1 -f concat -safe 0 -i playlist.txt \
  -c:v libx264 -preset veryfast \
  -vf "scale=1280:720..." \
  ... (rest same as above)
```

---

## Support & Documentation

- **Settings**: `/vod-channels/{id}/settings` - Configure channel
- **Playlist**: `/vod-channels/{id}/playlist` - Manage videos
- **Jobs**: `/encoding-jobs` - Monitor encoding progress
- **Video Library**: `/videos` - Browse and probe videos

For more info, check logs at `storage/logs/laravel.log`

---

**Last Updated**: 2025-12-15  
**Version**: 3.0.0 (LIVE Streaming)
