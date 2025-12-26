@extends('layouts.panel')

@section('content')

<div style="background: #f3f4f6; padding: 24px; margin: -20px -20px 0 -20px;">
    <div style="max-width: 1400px; margin: 0 auto;">
        <!-- PAGE HEADER -->
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 24px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <h1 style="margin: 0; font-size: 24px; font-weight: 600; color: #111827;">Edit Vod Channel</h1>
                <span style="background: #fbbf24; color: #111827; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">ID {{ $channel->id }}</span>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('vod-channels.encoding', $channel) }}" style="background: #2563eb; color: white; padding: 10px 16px; border-radius: 10px; text-decoration:none; font-weight:700; font-size:13px;">Encoding / Import</a>
                <a href="{{ route('vod-channels.index') }}" style="background: #111827; color: white; padding: 10px 16px; border-radius: 10px; text-decoration:none; font-weight:700; font-size:13px;">Back</a>
            </div>
        </div>

        @if(session('success'))
            <div style="background: #ecfdf5; border-left: 4px solid #059669; padding: 14px 18px; border-radius: 8px; color: #065f46; font-size: 14px; margin-bottom: 20px;">
                ✅ <strong>Saved:</strong> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 14px 18px; border-radius: 8px; color: #991b1b; font-size: 14px; margin-bottom: 20px;">
                ❌ <strong>Please fix:</strong>
                <ul style="margin: 8px 0 0 18px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- MAIN GRID: 2 COLUMNS -->
        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
            <!-- LEFT COLUMN -->
            <div>
                <!-- FORM CARD -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 0; overflow: hidden;">
                    <form method="POST" action="{{ route('vod-channels.settings.update', $channel) }}" enctype="multipart/form-data" style="display: grid; grid-template-columns: 140px 1fr;">
                        @csrf

                        <!-- Channel Name -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Channel Name</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <input type="text" name="name" value="{{ old('name', $channel->name) }}" placeholder="Enter channel name" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                        </div>

                        <!-- Enabled -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Enabled</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; display:flex; align-items:center; gap:10px;">
                            <input type="hidden" name="enabled" value="0" />
                            <input type="checkbox" name="enabled" value="1" {{ old('enabled', $channel->enabled) ? 'checked' : '' }} style="width:18px; height:18px;" />
                            <span style="font-size:14px; color:#111827; font-weight:600;">Active channel</span>
                        </div>

                        <!-- Channel Video Size (HIGHLIGHT YELLOW) -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #fff3a0;">Channel Video Size</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; background: #fff3a0;">
                            @php($res = old('resolution', $channel->resolution ?? '1280x720'))
                            <select name="resolution" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" required>
                                <option value="1920x1080" {{ $res === '1920x1080' ? 'selected' : '' }}>1080p | Full HD (FHD) | 1920x1080</option>
                                <option value="1280x720" {{ $res === '1280x720' ? 'selected' : '' }}>720p | HD | 1280x720</option>
                                <option value="854x480" {{ $res === '854x480' ? 'selected' : '' }}>480p | SD | 854x480</option>
                            </select>
                        </div>

                        <!-- Encoder Profile -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Encoder Profile</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <input type="text" name="encoder_profile" value="{{ old('encoder_profile', $channel->encoder_profile) }}" placeholder="e.g. h264_1500k" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;">
                        </div>

                        <!-- Video Category -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Video Category</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <select name="video_category_id" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;">
                                <option value="">No category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ (string)old('video_category_id', $channel->video_category_id) === (string)$cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Auto Sync Playlist -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Auto Sync</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; display:flex; align-items:center; gap:10px;">
                            <input type="hidden" name="auto_sync_playlist" value="0" />
                            <input type="checkbox" name="auto_sync_playlist" value="1" {{ old('auto_sync_playlist', $channel->auto_sync_playlist) ? 'checked' : '' }} style="width:18px; height:18px;" />
                            <span style="font-size:14px; color:#111827; font-weight:600;">Sync playlist from category</span>
                        </div>

                        <!-- 24/7 -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">24/7</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; display:flex; align-items:center; gap:10px;">
                            <input type="hidden" name="is_24_7_channel" value="0" />
                            <input type="checkbox" name="is_24_7_channel" value="1" {{ old('is_24_7_channel', $channel->is_24_7_channel) ? 'checked' : '' }} style="width:18px; height:18px;" />
                            <span style="font-size:14px; color:#111827; font-weight:600;">This is a 24/7 channel</span>
                        </div>

                        <!-- Description -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Description</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <textarea name="description" placeholder="Optional notes" style="width: 100%; min-height: 80px; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;">{{ old('description', $channel->description) }}</textarea>
                        </div>

                        <!-- Logo Type (HIGHLIGHT YELLOW) -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #fff3a0;">Logo Type</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; background: #fff3a0;">
                            <select name="logo_type" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;" disabled>
                                <option selected>Logo [Jpg,Png,Gif]</option>
                            </select>
                            <div style="font-size:12px; color:#6b7280; margin-top:6px;">Single channel logo is used everywhere.</div>
                        </div>

                        <!-- Logo Upload -->
                        <label style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb; font-size: 13px; font-weight: 500; color: #6b7280; background: #f9fafb;">Logo (16:9)</label>
                        <div style="padding: 14px 18px; border-bottom: 1px dashed #e5e7eb;">
                            <label style="display: inline-block; background: #3b82f6; color: white; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                Choose Logo
                                <input type="file" id="logoFile" name="channel_logo_file" accept="image/*" style="display: none;">
                            </label>
                            <span id="logoText" style="display: inline-block; margin-left: 12px; color: #6b7280; font-size: 13px;">No logo selected</span>
                        </div>

                        <!-- BUTTON -->
                        <div style="grid-column: 1 / -1; padding: 14px 18px; display: flex; justify-content: space-between; align-items:center; gap:12px;">
                            <a href="{{ route('vod-channels.encoding', $channel) }}" style="color:#2563eb; font-weight:700; text-decoration:none;">Go to Encoding / Import →</a>
                            <button type="submit" style="background: #dc2626; color: white; padding: 12px 28px; border: none; border-radius: 20px; font-size: 14px; font-weight: 600; cursor: pointer;">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Current Logo Card -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 18px;">
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 10px 0; color: #111827;">Current Logo</div>
                    <div style="width: 100%; aspect-ratio: 16 / 9; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: #f9fafb; display:flex; align-items:center; justify-content:center;">
                        @if(!empty($channel->logo_path))
                            <img id="currentLogo" src="{{ route('vod-channels.logo.preview', $channel) }}?v={{ urlencode((string) optional($channel->updated_at)->timestamp) }}" alt="" style="width:100%; height:100%; object-fit:contain; display:block;" />
                        @else
                            <span style="color:#9ca3af; font-weight:700; font-size:12px;">No logo</span>
                        @endif
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 10px;">Preview uses the same logo as the list page.</div>
                </div>

                <!-- Logo Size Card -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 18px;">
                    <div style="font-size: 14px; font-weight: 600; margin: 0 0 8px 0; color: #dc2626;">Logo Width / Height</div>
                    <div style="font-size: 26px; font-weight: 800; color: #111827; margin: 0;" id="logoSize">0 x 0</div>
                </div>

                <!-- Quick Links -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); padding: 18px;">
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 10px 0; color: #111827;">Quick Links</div>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <a href="{{ route('vod-channels.playlist', $channel) }}" style="background:#111827; color:white; padding:10px 12px; border-radius:10px; text-decoration:none; font-weight:700; font-size:13px;">Playlist</a>
                        <a href="{{ route('vod-channels.encoding', $channel) }}" style="background:#2563eb; color:white; padding:10px 12px; border-radius:10px; text-decoration:none; font-weight:700; font-size:13px;">Encoding / Import</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const logoFile = document.getElementById('logoFile');
    const logoText = document.getElementById('logoText');
    const logoSize = document.getElementById('logoSize');

    function setLogoSizeFromImage(img) {
        if (!img || !img.naturalWidth || !img.naturalHeight) return;
        logoSize.textContent = img.naturalWidth + ' x ' + img.naturalHeight;
    }

    const current = document.getElementById('currentLogo');
    if (current) {
        if (current.complete) setLogoSizeFromImage(current);
        current.addEventListener('load', () => setLogoSizeFromImage(current));
    }

    if (!logoFile) return;

    logoFile.addEventListener('change', function(e) {
        const file = e.target.files && e.target.files[0];
        if (!file) {
            logoText.textContent = 'No logo selected';
            logoText.style.color = '#6b7280';
            logoText.style.fontWeight = '400';
            if (!current) logoSize.textContent = '0 x 0';
            return;
        }

        logoText.textContent = file.name;
        logoText.style.color = '#059669';
        logoText.style.fontWeight = '700';

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                logoSize.textContent = img.width + ' x ' + img.height;
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
})();
</script>

@endsection
