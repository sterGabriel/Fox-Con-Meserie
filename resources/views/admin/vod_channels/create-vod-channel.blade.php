@extends('layouts.panel')

@section('content')

<div style="background: #f3f4f6; padding: 24px; margin: -20px -20px 0 -20px;">
    <div style="max-width: 1400px; margin: 0 auto;">
        <!-- PAGE HEADER -->
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #111827;">Create Vod Channel</h1>
            <span style="background: #fbbf24; color: #111827; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">Server 1</span>
        </div>

        <!-- MAIN GRID: 2 COLUMNS -->
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
            <!-- LEFT COLUMN -->
            <div>
                <!-- ALERT -->
                <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px 18px; border-radius: 8px; color: #991b1b; font-size: 14px; margin-bottom: 20px;">
                    ⚠️ <strong>Important Notice:</strong> Fill in all required fields carefully. Channel configuration affects streaming quality and user experience.
                </div>

                <!-- FORM CARD -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 0; overflow: hidden;">
                    <form id="createChannelForm" style="display: grid; grid-template-columns: 140px 1fr;">
                        <!-- Server -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Server</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="server" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Server</option>
                                <option value="server1" selected>Server 1 (46.4.20.56)</option>
                                <option value="server2">Server 2 (45.142.186.233)</option>
                            </select>
                        </div>

                        <!-- Channel Type -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Channel Type</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="channel_type" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Type</option>
                                <option value="default">Default Playlist</option>
                                <option value="live">Live Stream</option>
                            </select>
                        </div>

                        <!-- Channel Category -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Channel Category</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="category" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Category</option>
                                <option value="movies">Film Channel</option>
                                <option value="sports">Sports Channel</option>
                            </select>
                        </div>

                        <!-- Channel Country -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Channel Country</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="country" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Country</option>
                                <option value="ro">Romania</option>
                                <option value="ad">Andorra</option>
                            </select>
                        </div>

                        <!-- Channel Output -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Channel Output</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="output" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Output</option>
                                <option value="mpegts">MPEG-TS</option>
                                <option value="hls">HLS</option>
                            </select>
                        </div>

                        <!-- Channel Video Size (HIGHLIGHT YELLOW) -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #fff3a0;">Channel Video Size</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; background: #fff3a0;">
                            <select name="video_size" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Size</option>
                                <option value="1080p" selected>1080p | Full HD (FHD) | 1920x1080</option>
                                <option value="720p">720p | HD | 1280x720</option>
                            </select>
                        </div>

                        <!-- EPG -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">EPG</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="epg" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select EPG</option>
                                <option value="enabled" selected>Enabled</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>

                        <!-- Channel Name -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Channel Name</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <input type="text" name="channel_name" placeholder="Enter channel name" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                        </div>

                        <!-- Icon URL -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Icon URL</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <input type="url" name="icon_url" placeholder="Channel Logo URL (EPG - M3U)" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;">
                        </div>

                        <!-- Logo Type (HIGHLIGHT YELLOW) -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #fff3a0;">Logo Type</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; background: #fff3a0;">
                            <select name="logo_type" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="">Select Logo Type</option>
                                <option value="jpg" selected>Logo [Jpg,Png,Gif]</option>
                                <option value="png">PNG Image</option>
                            </select>
                        </div>

                        <!-- Logo Upload -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Logo (Max 300px)</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <label style="display: inline-block; background: #3b82f6; color: white; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                Choose Logo
                                <input type="file" id="logoFile" name="logo" accept="image/*" style="display: none;">
                            </label>
                            <span id="logoText" style="display: inline-block; margin-left: 12px; color: #6b7280; font-size: 13px;">No logo selected</span>
                        </div>

                        <!-- BUTTON -->
                        <div style="grid-column: 1 / -1; padding: 14px 18px; display: flex; justify-content: flex-end;">
                            <button type="submit" style="background: #dc2626; color: white; padding: 12px 28px; border: none; border-radius: 20px; font-size: 14px; font-weight: 600; cursor: pointer;">Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Logo Size Card -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 24px;">
                    <div style="font-size: 14px; font-weight: 600; margin: 0 0 12px 0; color: #dc2626;">Logo Width / Height</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;" id="logoSize">0 x 0</div>
                </div>

                <!-- Channel Limit Card -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 24px;">
                    <div style="font-size: 14px; font-weight: 600; margin: 0 0 12px 0; color: #2563eb;">Channel Limit</div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;">∞</div>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">Unlimited</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Logo file selection
    document.getElementById('logoFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) {
            document.getElementById('logoText').textContent = 'No logo selected';
            document.getElementById('logoSize').textContent = '0 x 0';
            return;
        }

        document.getElementById('logoText').textContent = file.name;
        document.getElementById('logoText').style.color = '#059669';
        document.getElementById('logoText').style.fontWeight = '500';

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                document.getElementById('logoSize').textContent = img.width + ' x ' + img.height;
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Form submission
    document.getElementById('createChannelForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        fetch('/api/live-channels', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.id) {
                    alert('✅ Channel created successfully!');
                    window.location.href = '/vod-channels';
                } else {
                    alert('❌ Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(e => {
                console.error('Error:', e);
                alert('❌ Network error: ' + e.message);
            });
    });
</script>

@endsection
