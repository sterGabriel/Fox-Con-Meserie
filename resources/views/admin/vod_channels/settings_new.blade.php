@extends('layouts.panel')

@section('content')
<style>
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
    .page-title { font-size: 22px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .page-subtitle { font-size: 12px; color: var(--text-secondary); margin-top: 6px; }

    .flash { border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 6px; padding: 12px 14px; box-shadow: var(--shadow-sm); margin: 12px 0 16px; }
    .flash.success { border-left: 4px solid var(--fox-green); }
    .flash.error { border-left: 4px solid var(--fox-red); }
    .flash-title { font-weight: 700; color: var(--text-primary); margin-bottom: 4px; font-size: 13px; }
    .flash-body { font-size: 12px; color: var(--text-secondary); }

    .layout { display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; padding-bottom: 64px; }
    @media (max-width: 1100px) { .layout { grid-template-columns: 1fr; } }

    .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .card-h { padding: 12px 14px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .card-t { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .card-b { padding: 14px; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 800px) { .grid-2 { grid-template-columns: 1fr; } }

    .field label { display: block; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }
    .input, .select, .textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 13px; background: var(--card-bg); color: var(--text-primary); }
    .textarea { min-height: 84px; resize: vertical; }
    .hint { font-size: 11px; color: var(--text-muted); margin-top: 6px; }

    .checkbox { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--card-bg); }
    .checkbox input { width: 16px; height: 16px; }
    .checkbox span { font-size: 13px; color: var(--text-primary); font-weight: 600; }

    .btn-row { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { padding: 10px 12px; border-radius: 6px; color: #fff; font-weight: 800; font-size: 12px; }
    .btn-save { background: var(--fox-blue); }
    .btn-save:hover { filter: brightness(0.95); }
    .btn-green { background: var(--btn-start); }
    .btn-green:hover { filter: brightness(0.95); }
    .btn-yellow { background: var(--btn-epg); }
    .btn-yellow:hover { filter: brightness(0.95); }
    .btn-red { background: var(--btn-stop); }
    .btn-red:hover { filter: brightness(0.95); }

    .logo-169 { width: 160px; height: 90px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--border-light); overflow: hidden; }
    .logo-169 img { width: 100%; height: 100%; object-fit: contain; display: block; }

    .queue { display: grid; gap: 8px; max-height: 220px; overflow: auto; }
    .queue-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--card-bg); }
    .queue-item span { font-size: 12px; color: var(--text-primary); font-weight: 600; }
    .queue-item button { background: transparent; color: var(--fox-red); font-weight: 800; }

    .encode-thumb { width: 96px; height: 54px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--border-light); object-fit: cover; display:block; }
    .encode-thumb.placeholder { display:flex; align-items:center; justify-content:center; font-size: 10px; color: var(--text-muted); }

    .prog { width: 110px; }
    .prog-bar { height: 6px; border: 1px solid var(--border-color); border-radius: 999px; background: var(--border-light); overflow: hidden; }
    .prog-fill { height: 100%; background: var(--fox-blue); width: 0%; }
    .prog-txt { font-size: 10px; color: var(--text-muted); margin-top: 4px; text-align: right; }

    .right-actions { display: flex; gap: 10px; flex-wrap: wrap; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Encoding / Import</h1>
        <div class="page-subtitle" style="display:flex; align-items:center; gap:10px;">
            <span class="logo-169" style="width:64px; height:36px;" aria-hidden="true">
                @if(!empty($channel->logo_path))
                    <img src="{{ route('vod-channels.logo.preview', $channel) }}?v={{ urlencode((string) optional($channel->updated_at)->timestamp) }}" alt="" loading="lazy" decoding="async" onerror="this.style.visibility='hidden'" />
                @endif
            </span>
            <span style="font-size: 20px; font-weight: 800; line-height: 1;">{{ $channel->name }}</span>
        </div>
    </div>
    <div class="btn-row">
        <a class="btn btn-save" href="{{ route('vod-channels.settings', $channel) }}">Edit</a>
        <a class="btn btn-yellow" href="{{ route('vod-channels.playlist', $channel) }}">Playlist</a>
        <a class="btn btn-save" href="{{ route('vod-channels.index') }}">Channels</a>
    </div>
</div>

@if(session('success'))
    <div class="flash success">
        <div class="flash-title">Saved</div>
        <div class="flash-body">{{ session('success') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="flash error">
        <div class="flash-title">Validation errors</div>
        <div class="flash-body">
            <ul style="margin-left: 16px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="layout">
    <div>
    <div class="card" style="margin-bottom: 16px;">
        <div class="card-h">
            <div class="card-t">Selected (<span id="queueCount">0</span>) · Tested (<span id="testedCount">0</span>)</div>
            <button type="button" class="btn btn-red" onclick="clearSelection()">Clear</button>
        </div>
        <div class="card-b">
            <div>
                <div id="encodeQueue" class="queue">
                    <div style="font-size: 12px; color: var(--text-muted);">Bifează filmele din dreapta — apar aici.</div>
                </div>
            </div>

            <div style="margin-top: 14px;">
                <div class="field" style="margin-bottom: 10px;">
                    <label>Stream Output (HLS)</label>
                    <input class="input" type="text" id="streamHlsUrl" value="" readonly>
                    <div class="hint">Use this in VLC / players.</div>
                </div>
                <div class="field" style="margin-bottom: 10px;">
                    <label>Stream Output (TS)</label>
                    <input class="input" type="text" id="streamTsUrl" value="" readonly>
                </div>
            </div>
        </div>
    </div>

    <form id="channelSettingsForm" class="card" method="POST" action="{{ route('vod-channels.settings.update', $channel) }}" enctype="multipart/form-data">
        @csrf
        <div class="card-h">
            <div class="card-t">General</div>
            <button type="submit" class="btn btn-save">Save Settings</button>
        </div>
        <div class="card-b">
            <div class="grid-2">
                <div class="field">
                    <label>Channel Category (Saved)</label>
                    @php
                        $savedCategoryId = (int) old('video_category_id', $channel->video_category_id ?? 0);
                        $savedCategoryName = '';
                        foreach ($categories as $cat) {
                            if ((int) $cat->id === $savedCategoryId) { $savedCategoryName = (string) $cat->name; break; }
                        }
                    @endphp
                    <input class="input" type="text" id="savedCategoryName" value="{{ $savedCategoryName }}" readonly>
                    <input type="hidden" id="savedCategoryId" name="video_category_id" value="{{ old('video_category_id', $channel->video_category_id ?? '') }}">
                    <div class="hint">Categoria salvată pentru acest canal (Sync Playlist folosește asta).</div>
                </div>

                <div class="field">
                    <label>Description</label>
                    <textarea class="textarea" name="description" placeholder="Optional description">{{ old('description', $channel->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="grid-2" style="margin-top: 12px;">
                <label class="checkbox">
                    <input type="checkbox" name="is_24_7_channel" value="1" {{ old('is_24_7_channel', $channel->is_24_7_channel ?? true) ? 'checked' : '' }}>
                    <span>24/7 Channel</span>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="auto_sync_playlist" value="1" {{ old('auto_sync_playlist', $channel->auto_sync_playlist ?? false) ? 'checked' : '' }}>
                    <span>Auto Sync Playlist</span>
                </label>
            </div>
        </div>

        <div class="card-h">
            <div class="card-t">Channel Logo</div>
        </div>
        <div class="card-b">
            <div class="grid-2">
                <div class="field">
                    <label>Upload Logo</label>
                    <input class="input" type="file" name="channel_logo_file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml">
                    <div class="hint">Single logo for this channel (used in list + this page + overlay if enabled).</div>
                </div>
                <div>
                    <div class="logo-169" aria-hidden="true">
                        @if(!empty($channel->logo_path))
                            <img src="{{ route('vod-channels.logo.preview', $channel) }}" alt="" loading="lazy" decoding="async" onerror="this.style.visibility='hidden'" />
                        @endif
                    </div>
                    <div class="hint">Current logo preview.</div>
                </div>
            </div>
        </div>

        <div style="display:none;">
        <div class="card-h">
            <div class="card-t">LIVE Profile</div>
        </div>
        <div class="card-b">
            <div class="grid-2">
                <div class="field">
                    <label>Preset Profile</label>
                    <select class="select" name="encode_profile_id">
                        <option value="">-- Use Default --</option>
                        @foreach($liveProfiles as $profile)
                            <option value="{{ $profile->id }}" {{ (int)old('encode_profile_id', $channel->encode_profile_id ?? 0) === (int)$profile->id ? 'selected' : '' }}>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <label class="checkbox" style="align-self: end;">
                    <input type="checkbox" name="manual_override_encoding" value="1" {{ old('manual_override_encoding', $channel->manual_override_encoding ?? false) ? 'checked' : '' }}>
                    <span>Manual Override (Advanced)</span>
                </label>
            </div>

            <div class="grid-2" style="margin-top: 12px;">
                <div class="field">
                    <label>Manual Width</label>
                    <input class="input" type="number" name="manual_width" value="{{ old('manual_width', $channel->manual_width ?? '') }}" placeholder="e.g. 1280">
                </div>
                <div class="field">
                    <label>Manual Height</label>
                    <input class="input" type="number" name="manual_height" value="{{ old('manual_height', $channel->manual_height ?? '') }}" placeholder="e.g. 720">
                </div>
                <div class="field">
                    <label>Manual FPS</label>
                    <input class="input" type="number" name="manual_fps" value="{{ old('manual_fps', $channel->manual_fps ?? '') }}" placeholder="e.g. 25">
                </div>
                <div class="field">
                    <label>Manual Codec</label>
                    <input class="input" type="text" name="manual_codec" value="{{ old('manual_codec', $channel->manual_codec ?? '') }}" placeholder="e.g. libx264">
                </div>
                <div class="field">
                    <label>Manual Preset</label>
                    <input class="input" type="text" name="manual_preset" value="{{ old('manual_preset', $channel->manual_preset ?? '') }}" placeholder="e.g. veryfast">
                </div>
                <div class="field">
                    <label>Manual Bitrate (kbps)</label>
                    <input class="input" type="number" name="manual_bitrate" value="{{ old('manual_bitrate', $channel->manual_bitrate ?? '') }}" placeholder="e.g. 2500">
                </div>
                <div class="field">
                    <label>Manual Audio Bitrate (kbps)</label>
                    <input class="input" type="number" name="manual_audio_bitrate" value="{{ old('manual_audio_bitrate', $channel->manual_audio_bitrate ?? '') }}" placeholder="e.g. 128">
                </div>
                <div class="field">
                    <label>Manual Audio Codec</label>
                    <input class="input" type="text" name="manual_audio_codec" value="{{ old('manual_audio_codec', $channel->manual_audio_codec ?? '') }}" placeholder="e.g. aac">
                </div>
            </div>
        </div>
        </div>

        <div class="card-h">
            <div class="card-t">Overlays</div>
        </div>
        <div class="card-b">
            @php
                $pos = old('overlay_logo_position', $channel->overlay_logo_position ?? 'TL');
                $tp = old('overlay_text_position', $channel->overlay_text_position ?? 'BL');
                $tpos = old('overlay_timer_position', $channel->overlay_timer_position ?? 'BL');
            @endphp

            <div class="grid-2" style="margin-bottom: 12px;">
                <div class="field">
                    <label>Name Position</label>
                    <select class="select" name="overlay_text_position" id="overlay_text_position">
                        <option value="TL" {{ $tp === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ $tp === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ $tp === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ $tp === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="CUSTOM" {{ $tp === 'CUSTOM' ? 'selected' : '' }}>Custom</option>
                    </select>
                    <div class="hint">Textul este numele canalului.</div>
                </div>
                <div class="field">
                    <label>Countdown Position</label>
                    <select class="select" name="overlay_timer_position" id="overlay_timer_position">
                        <option value="TL" {{ $tpos === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ $tpos === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ $tpos === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ $tpos === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="CUSTOM" {{ $tpos === 'CUSTOM' ? 'selected' : '' }}>Custom</option>
                    </select>
                    <div class="hint">Timer-ul este setat pe Countdown.</div>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 12px;">
                <div class="field">
                    <label>Encoding Preset (Resolution)</label>
                    <select class="select" id="encodingPreset">
                        <option value="">-- Custom --</option>
                        <option value="3840x2160">4K (3840x2160)</option>
                        <option value="2560x1440">2K (2560x1440)</option>
                        <option value="1920x1080">1080p (1920x1080)</option>
                        <option value="1280x720">720p (1280x720)</option>
                    </select>
                    <div class="hint">Setează automat mărimi recomandate. După poți edita manual.</div>
                </div>
                <div class="field">
                    <label>Safe Margin</label>
                    <input class="input" type="number" name="overlay_safe_margin" id="overlay_safe_margin" value="{{ old('overlay_safe_margin', $channel->overlay_safe_margin ?? 30) }}" min="0" max="100">
                    <div class="hint">Spațiu față de margini (pentru BL/TR etc).</div>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 12px;">
                <div class="field">
                    <label>Logo Width</label>
                    <input class="input" type="number" name="overlay_logo_width" id="overlay_logo_width" value="{{ old('overlay_logo_width', $channel->overlay_logo_width ?? 150) }}" min="10" max="2000">
                </div>
                <div class="field">
                    <label>Logo Height</label>
                    <input class="input" type="number" name="overlay_logo_height" id="overlay_logo_height" value="{{ old('overlay_logo_height', $channel->overlay_logo_height ?? 100) }}" min="10" max="2000">
                </div>
                <div class="field">
                    <label>VOD Name Font Size</label>
                    <input class="input" type="number" name="overlay_text_font_size" id="overlay_text_font_size" value="{{ old('overlay_text_font_size', $channel->overlay_text_font_size ?? 28) }}" min="8" max="200">
                </div>
                <div class="field">
                    <label>Timer Font Size</label>
                    <input class="input" type="number" name="overlay_timer_font_size" id="overlay_timer_font_size" value="{{ old('overlay_timer_font_size', $channel->overlay_timer_font_size ?? 24) }}" min="8" max="200">
                </div>
            </div>

            <div class="grid-2" id="overlay_text_custom_xy" style="margin-bottom: 12px; display:none;">
                <div class="field"><label>Name X</label><input class="input" type="number" name="overlay_text_x" value="{{ old('overlay_text_x', $channel->overlay_text_x ?? 20) }}"></div>
                <div class="field"><label>Name Y</label><input class="input" type="number" name="overlay_text_y" value="{{ old('overlay_text_y', $channel->overlay_text_y ?? 20) }}"></div>
            </div>

            <div class="grid-2" id="overlay_timer_custom_xy" style="margin-bottom: 12px; display:none;">
                <div class="field"><label>Countdown X</label><input class="input" type="number" name="overlay_timer_x" value="{{ old('overlay_timer_x', $channel->overlay_timer_x ?? 20) }}"></div>
                <div class="field"><label>Countdown Y</label><input class="input" type="number" name="overlay_timer_y" value="{{ old('overlay_timer_y', $channel->overlay_timer_y ?? 20) }}"></div>
            </div>

            <input type="hidden" name="overlay_logo_enabled" value="1">
            <input type="hidden" name="overlay_logo_position" value="{{ $pos }}">
            <input type="hidden" name="overlay_logo_x" value="{{ old('overlay_logo_x', $channel->overlay_logo_x ?? 20) }}">
            <input type="hidden" name="overlay_logo_y" value="{{ old('overlay_logo_y', $channel->overlay_logo_y ?? 20) }}">
            {{-- sizes are editable above --}}
            <input type="hidden" name="overlay_logo_opacity" value="{{ old('overlay_logo_opacity', $channel->overlay_logo_opacity ?? 80) }}">

            <input type="hidden" name="overlay_text_enabled" value="1">
            <input type="hidden" name="overlay_text_content" value="title">
            <input type="hidden" name="overlay_text_custom" value="{{ old('overlay_text_custom', $channel->overlay_text_custom ?? '') }}">
            <input type="hidden" name="overlay_text_font_family" value="{{ old('overlay_text_font_family', $channel->overlay_text_font_family ?? 'Arial') }}">
            {{-- font size is editable above --}}
            <input type="hidden" name="overlay_text_color" value="{{ old('overlay_text_color', $channel->overlay_text_color ?? '#FFFFFF') }}">
            <input type="hidden" name="overlay_text_padding" value="{{ old('overlay_text_padding', $channel->overlay_text_padding ?? 6) }}">
            <input type="hidden" name="overlay_text_opacity" value="{{ old('overlay_text_opacity', $channel->overlay_text_opacity ?? 100) }}">
            <input type="hidden" name="overlay_text_bg_color" value="{{ old('overlay_text_bg_color', $channel->overlay_text_bg_color ?? '#000000') }}">
            <input type="hidden" name="overlay_text_bg_opacity" value="{{ old('overlay_text_bg_opacity', $channel->overlay_text_bg_opacity ?? 60) }}">

            <input type="hidden" name="overlay_timer_enabled" value="1">
            <input type="hidden" name="overlay_timer_mode" value="countdown">
            <input type="hidden" name="overlay_timer_format" value="HH:mm:ss">
            {{-- font size is editable above --}}
            <input type="hidden" name="overlay_timer_color" value="{{ old('overlay_timer_color', $channel->overlay_timer_color ?? '#FFFFFF') }}">
            <input type="hidden" name="overlay_timer_style" value="{{ old('overlay_timer_style', $channel->overlay_timer_style ?? 'normal') }}">
            <input type="hidden" name="overlay_timer_bg" value="{{ old('overlay_timer_bg', $channel->overlay_timer_bg ?? 'none') }}">
            <input type="hidden" name="overlay_timer_opacity" value="{{ old('overlay_timer_opacity', $channel->overlay_timer_opacity ?? 100) }}">

            {{-- safe margin is editable above --}}

            <div style="margin-top: 12px;">
                <div class="btn-row">
                    <button type="submit" class="btn btn-save">Save Settings</button>
                    <button type="button" class="btn btn-green" onclick="prepareEncodePanel()">Encodare</button>
                </div>
                <div class="hint">Apasă Encodare ca să trimiți selecția în panoul din dreapta jos.</div>
            </div>
        </div>
    </form>

    </div>

    <div>
        <div class="card" style="margin-bottom: 16px;">
            <div class="card-h">
                <div class="card-t">Import VOD</div>
                <div class="right-actions">
                    <button type="button" class="btn btn-yellow" onclick="syncPlaylist({{ $channel->id }})">Sync Playlist</button>
                    <button type="button" class="btn btn-green" onclick="startEncodingJobs({{ $channel->id }})">Start Encoding</button>
                        <button type="button" class="btn btn-green" onclick="startEncodingTested({{ $channel->id }})">Encode Tested</button>
                </div>
            </div>
            <div class="card-b">
                @php($tmdbKeyPresent = trim((string) \App\Models\AppSetting::getValue('tmdb_api_key', (string) env('TMDB_API_KEY', ''))) !== '')
                @if(!$tmdbKeyPresent)
                    <div class="flash error" style="margin: 0 0 12px;">
                        <div class="flash-title">TMDB Key lipsă</div>
                        <div class="flash-body">Mergi la <strong>Settings</strong> și salvează cheia ca să apară posterele.</div>
                    </div>
                @endif

                <div class="field" style="margin-bottom: 12px;">
                    <label>Category (Preview list)</label>
                    <select class="select" id="categorySelect" onchange="loadVideos()">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (int) old('video_category_id', $channel->video_category_id ?? 0) === (int)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <div class="hint">Doar pentru listă/preview. Sync Playlist folosește categoria salvată a canalului.</div>
                </div>

                <div class="fox-table-container">
                    <table class="fox-table">
                        <thead>
                            <tr>
                                <th style="width: 44px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                                <th style="width: 56px;">Poster</th>
                                <th>Title</th>
                                <th style="width: 110px;">Duration</th>
                                <th style="width: 130px;">Resolution</th>
                                <th style="width: 90px;">Size</th>
                            </tr>
                        </thead>
                        <tbody id="videosTable">
                            <tr>
                                <td colspan="6" style="padding: 18px; color: var(--text-muted);">Select category first</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 12px; display:flex; align-items:center; justify-content:space-between; gap: 10px;">
                    <div class="btn-row">
                        <button type="button" class="btn btn-save" onclick="addSelectedToPlaylist()">Add Selected to Playlist</button>
                        <button type="button" class="btn btn-yellow" onclick="ffprobeScanSelected()">FFprobe Scan Selected</button>
                    </div>
                    <div class="btn-row">
                        <button type="button" class="btn btn-yellow" onclick="tmdbScanAllCategory()">TMDB Scan All (Category)</button>
                        <button type="button" class="btn btn-yellow" onclick="testEncoding({{ $channel->id }})">Test 30s</button>
                    </div>
                </div>

                <div id="encodePanel" class="card" style="margin-top: 14px;">
                    <div class="card-h">
                        <div class="card-t">Test Video</div>
                    </div>
                    <div class="card-b">
                        <div class="hint" style="margin: 0 0 10px;">Lista de aici se umple când apeși <strong>Encodare</strong> în stânga. După Test, apare Watch.</div>

                        <div class="fox-table-container">
                            <table class="fox-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th style="width: 120px;">Status</th>
                                        <th style="width: 240px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="encodePanelTable">
                                    <tr>
                                        <td colspan="3" style="padding: 18px; color: var(--text-muted);">Nimic aici încă. Apasă Encodare în stânga.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 12px; display:flex; align-items:center; justify-content:flex-end; gap: 10px;">
                            <button type="button" class="btn btn-green" onclick="encodePanelEncodeAll({{ $channel->id }})">Convert All Videos</button>
                            <button type="button" class="btn btn-red" onclick="encodePanelDeleteAll()">Delete All Videos</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.15/dist/hls.min.js"></script>
<script>
let selectedVideos = [];
let encodeQueue = [];
let encodePanelQueue = [];
let activePreviewVideoId = null;
const testedVideoIds = new Set();
const previewByVideoId = new Map();
const jobStateByVideoId = new Map();
let jobsPollTimer = null;
const autoScanned = { probe: new Set(), tmdb: new Set() };
const CHANNEL_ID = {{ (int) $channel->id }};

function syncOverlayCustomVisibility() {
    const textPos = document.getElementById('overlay_text_position');
    const timerPos = document.getElementById('overlay_timer_position');
    const textXY = document.getElementById('overlay_text_custom_xy');
    const timerXY = document.getElementById('overlay_timer_custom_xy');

    if (textPos && textXY) {
        textXY.style.display = (String(textPos.value) === 'CUSTOM') ? '' : 'none';
    }
    if (timerPos && timerXY) {
        timerXY.style.display = (String(timerPos.value) === 'CUSTOM') ? '' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const textPos = document.getElementById('overlay_text_position');
    const timerPos = document.getElementById('overlay_timer_position');
    if (textPos) textPos.addEventListener('change', syncOverlayCustomVisibility);
    if (timerPos) timerPos.addEventListener('change', syncOverlayCustomVisibility);
    syncOverlayCustomVisibility();

    // Keep selected Import VOD category in sync with the saved channel category.
    // This way, when you hit "Save Settings", the chosen category is actually persisted.
    const categorySelect = document.getElementById('categorySelect');
    const savedCategoryId = document.getElementById('savedCategoryId');
    const savedCategoryName = document.getElementById('savedCategoryName');
    if (categorySelect && savedCategoryId) {
        const syncCategory = () => {
            savedCategoryId.value = String(categorySelect.value || '');
            if (savedCategoryName) {
                const opt = categorySelect.options[categorySelect.selectedIndex];
                savedCategoryName.value = opt && opt.value ? (opt.textContent || '').trim() : '';
            }
        };
        categorySelect.addEventListener('change', syncCategory);
        syncCategory();

        // If a category is already selected (saved), load its videos immediately.
        if (String(categorySelect.value || '').trim() !== '') {
            loadVideos();
        }
    }

    // Queue actions (CSP-safe: no inline onclick)
    const queueDiv = document.getElementById('encodeQueue');
    if (queueDiv) {
        queueDiv.addEventListener('click', (e) => {
            const target = e.target;
            if (!target || !target.closest) return;

            const removeBtn = target.closest('button.js-queue-remove');
            if (removeBtn) {
                const idx = Number(removeBtn.getAttribute('data-index'));
                if (!Number.isNaN(idx)) removeFromQueue(idx);
                return;
            }
        });
    }

    startJobsPolling();

    // Encode panel actions
    const encodePanelTable = document.getElementById('encodePanelTable');
    if (encodePanelTable) {
        encodePanelTable.addEventListener('click', (e) => {
            const target = e.target;
            if (!target || !target.closest) return;

            const testBtn = target.closest('button.js-encodepanel-test');
            if (testBtn) {
                const vid = testBtn.getAttribute('data-video-id');
                if (vid) testSelectedVideo(String(vid));
                return;
            }

            const watchBtn = target.closest('button.js-encodepanel-watch');
            if (watchBtn) {
                const vid = watchBtn.getAttribute('data-video-id');
                if (vid) playPreviewVideo(String(vid));
                return;
            }

            const removeBtn = target.closest('button.js-encodepanel-remove');
            if (removeBtn) {
                const idx = Number(removeBtn.getAttribute('data-index'));
                if (!Number.isNaN(idx)) removeFromEncodePanel(idx);
                return;
            }
        });
    }
});

function csrf() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

async function loadVideos() {
    const catId = document.getElementById('categorySelect').value;
    if (!catId) {
        document.getElementById('videosTable').innerHTML = '<tr><td colspan="6" style="padding: 18px; color: var(--text-muted);">Select category first</td></tr>';
        return;
    }

    try {
        const response = await fetch(`/api/videos?category_id=${encodeURIComponent(catId)}&channel_id=${encodeURIComponent(String(CHANNEL_ID))}&exclude_encoded=1`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const videos = await response.json();

        // Sort by resolution (desc), then title
        const sorted = [...videos].sort((a, b) => {
            const ar = parseResolutionScore(a.resolution);
            const br = parseResolutionScore(b.resolution);
            if (br !== ar) return br - ar;
            return String(a.title || '').localeCompare(String(b.title || ''));
        });

        let html = '';
        sorted.forEach(v => {
            const duration = (v.duration_seconds && Number(v.duration_seconds) > 0)
                ? new Date(Number(v.duration_seconds) * 1000).toISOString().substring(11, 19)
                : '--:--:--';
            const resolution = v.resolution ? escapeHtml(v.resolution) : '--';
            const sizeMb = (Number(v.size_bytes || 0) / 1024 / 1024).toFixed(0);

            const posterPath = v.tmdb_poster_path ? String(v.tmdb_poster_path) : '';
            const backdropPath = v.tmdb_backdrop_path ? String(v.tmdb_backdrop_path) : '';
            // Prefer poster; fallback to backdrop if poster missing
            const imgPath = posterPath || backdropPath;
            const posterUrl = imgPath ? ('https://image.tmdb.org/t/p/w92' + imgPath) : '';
            const posterCell = posterUrl
                ? `<img src="${posterUrl}" alt="" style="width:40px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--border-color);background:var(--border-light);" onerror="this.style.display='none'">`
                : `<div style="width:40px;height:60px;border-radius:6px;border:1px solid var(--border-color);background:var(--border-light);"></div>`;
            html += `
                <tr>
                    <td><input type="checkbox" value="${v.id}" class="videoCheck" onchange="updateSelected()"></td>
                    <td>${posterCell}</td>
                    <td>${escapeHtml(v.title || '')}</td>
                    <td>${duration}</td>
                    <td>${resolution}</td>
                    <td>${sizeMb} MB</td>
                </tr>
            `;
        });

        document.getElementById('videosTable').innerHTML = html || '<tr><td colspan="6" style="padding: 18px; color: var(--text-muted);">No videos found</td></tr>';
        document.getElementById('selectAll').checked = false;

        // Ensure selection always updates (some browsers/themes can be flaky with inline handlers)
        const tbody = document.getElementById('videosTable');
        if (tbody) {
            tbody.querySelectorAll('input.videoCheck').forEach(cb => {
                cb.addEventListener('change', updateSelected);
                cb.addEventListener('click', () => setTimeout(updateSelected, 0));
            });

            // Click on row toggles checkbox (but ignore clicks on interactive elements)
            tbody.querySelectorAll('tr').forEach(tr => {
                tr.addEventListener('click', (e) => {
                    if (e.target && e.target.closest && e.target.closest('input,button,a,select,label')) return;
                    const cb = tr.querySelector('input.videoCheck');
                    if (!cb) return;
                    cb.checked = !cb.checked;
                    updateSelected();
                });
            });
        }

        updateSelected();

        // Auto scan: ffprobe + TMDB (one pass per category)
        await autoFillMissing(catId, videos);
    } catch (error) {
        console.error('Error loading videos:', error);
        document.getElementById('videosTable').innerHTML = '<tr><td colspan="6" style="padding: 18px; color: var(--text-muted);">Failed to load videos</td></tr>';
    }
}

function parseResolutionScore(res) {
    if (!res) return 0;
    const m = String(res).match(/(\d{2,5})\s*[xX]\s*(\d{2,5})/);
    if (!m) return 0;
    const w = Number(m[1] || 0);
    const h = Number(m[2] || 0);
    return (w * h) || 0;
}

function chunkArray(arr, size) {
    const out = [];
    for (let i = 0; i < arr.length; i += size) out.push(arr.slice(i, i + size));
    return out;
}

function needsProbe(v) {
    const d = Number(v.duration_seconds || 0);
    const r = String(v.resolution || '');
    const s = Number(v.size_bytes || 0);
    return d <= 0 || r.trim() === '' || s <= 0;
}

function needsTmdb(v) {
    const p = String(v.tmdb_poster_path || '');
    const id = Number(v.tmdb_id || 0);
    const t = String(v.title || '').trim();
    const numericTitle = (t !== '' && /^\d+$/.test(t));
    // Run TMDB scan if posters/ID missing OR if title is unusable (empty/numeric)
    // so we can populate the real TMDB title.
    return (((!p || p.trim() === '') && id <= 0) || t === '' || numericTitle);
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(body)
    });
    const json = await response.json().catch(() => ({}));
    return { response, json };
}

async function autoFillMissing(catId, videos) {
    // 1) ffprobe
    if (!autoScanned.probe.has(String(catId))) {
        const ids = (videos || []).filter(needsProbe).map(v => Number(v.id)).filter(Boolean);
        if (ids.length) {
            const chunks = chunkArray(ids.slice(0, 50), 10); // cap per load
            for (const c of chunks) {
                await postJson('/api/videos/probe', { video_ids: c });
            }
            autoScanned.probe.add(String(catId));
            // reload once after update
            await reloadOnce(catId);
            return;
        }
        autoScanned.probe.add(String(catId));
    }

    // 2) TMDB
    if (!autoScanned.tmdb.has(String(catId))) {
        const ids = (videos || []).filter(needsTmdb).map(v => Number(v.id)).filter(Boolean);
        if (ids.length) {
            const chunks = chunkArray(ids.slice(0, 50), 10); // cap per load
            for (const c of chunks) {
                const { response, json } = await postJson('/api/videos/tmdb-scan', { video_ids: c });
                if (!response.ok || json.ok === false) {
                    const msg = (json && json.message) ? String(json.message) : 'TMDB scan failed';
                    // Dacă lipsește cheia, NU marcăm ca scanat ca să poată merge după ce salvezi cheia.
                    if (response.status === 422 && msg.toLowerCase().includes('tmdb key')) {
                        return;
                    }
                    alert('TMDB scan error: ' + msg);
                    autoScanned.tmdb.add(String(catId));
                    return;
                }
            }
            autoScanned.tmdb.add(String(catId));
            await reloadOnce(catId);
            return;
        }
        autoScanned.tmdb.add(String(catId));
    }
}

async function tmdbScanSelected() {
    updateSelected();
    if (!selectedVideos || selectedVideos.length === 0) {
        alert('Selectează filmele întâi!');
        return;
    }

    if (selectedVideos.length > 10) {
        alert('Selectează maxim 10 filme pentru TMDB scan.');
        return;
    }

    try {
        const { response, json } = await postJson('/api/videos/tmdb-scan', { video_ids: selectedVideos.map(v => Number(v)) });
        if (!response.ok || json.ok === false) {
            alert(json.message || 'TMDB scan failed');
            return;
        }

        const failed = (json.results || []).filter(r => !r.ok);
        if (failed.length) {
            alert(`TMDB scan gata. Failed: ${failed.length}`);
        } else {
            alert('TMDB scan gata. Postere actualizate.');
        }

        await loadVideos();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function tmdbScanAllCategory() {
    const sel = document.getElementById('categorySelect');
    const catId = sel ? Number(sel.value || 0) : 0;
    if (!catId) {
        alert('Selectează categoria întâi!');
        return;
    }

    if (!confirm('Rulează TMDB sync pentru TOATE item-urile din această categorie? (Se rulează în background pe queue)')) {
        return;
    }

    try {
        const { response, json } = await postJson('/api/videos/tmdb-scan-all', { category_id: catId });
        if (!response.ok || json.ok === false) {
            alert(json.message || 'TMDB scan all failed');
            return;
        }
        const queued = Number(json.queued || 0);
        const jobs = Number(json.jobs || 0);
        if (queued <= 0) {
            alert(json.message || 'Nimic de sincronizat.');
            return;
        }
        alert(`TMDB sync queued: ${queued} video(s) in ${jobs} job(s).\nPornește worker-ul (php artisan queue:work) dacă nu rulează deja.`);
        await loadVideos();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

let reloading = false;
async function reloadOnce(catId) {
    if (reloading) return;
    reloading = true;
    try {
        // keep same category selected
        const sel = document.getElementById('categorySelect');
        if (sel) sel.value = String(catId);
        await loadVideos();
    } finally {
        reloading = false;
    }
}

function updateSelected() {
    const tbody = document.getElementById('videosTable');
    const checked = Array.from((tbody ? tbody.querySelectorAll('.videoCheck:checked') : document.querySelectorAll('.videoCheck:checked')));
    selectedVideos = checked.map(el => String(el.value));
    encodeQueue = checked.map(el => {
        const row = el.closest('tr');
        const title = row?.cells?.[2]?.textContent || '';
        return { id: String(el.value), title: String(title).trim() };
    });

    if (encodeQueue.length === 0) {
        activePreviewVideoId = null;
    } else {
        if (!activePreviewVideoId || !selectedVideos.includes(String(activePreviewVideoId))) {
            activePreviewVideoId = encodeQueue[0].id;
        }
    }

    updateQueueDisplay();
}

function toggleSelectAll(checkbox) {
    const tbody = document.getElementById('videosTable');
    (tbody ? tbody.querySelectorAll('.videoCheck') : document.querySelectorAll('.videoCheck')).forEach(el => el.checked = checkbox.checked);
    updateSelected();
}

function setActivePreviewVideo(videoId) {
    activePreviewVideoId = String(videoId);
    updateQueueDisplay();
}

function testSelectedVideo(videoId) {
    setActivePreviewVideo(videoId);
    // Run preview immediately for this video (independent of checkbox selection)
    testEncoding(CHANNEL_ID, String(videoId));
}

function playPreviewVideo(videoId) {
    const id = String(videoId);
    const url = previewByVideoId.get(id);
    if (!url) {
        alert('Nu există preview pentru acest video. Apasă Test întâi.');
        return;
    }
    setActivePreviewVideo(id);
    // Player UI removed; open preview in new tab.
    try {
        window.open(String(url), '_blank', 'noopener');
    } catch (e) {
        // fallback
        location.href = String(url);
    }
}

function prepareEncodePanel() {
    updateSelected();
    if (!encodeQueue || encodeQueue.length === 0) {
        alert('Bifează filmele din dreapta întâi (Import VOD).');
        return;
    }
    encodePanelQueue = encodeQueue.map(v => ({ id: String(v.id), title: String(v.title || '') }));
    renderEncodePanel();
    const panel = document.getElementById('encodePanel');
    if (panel && panel.scrollIntoView) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function removeFromEncodePanel(index) {
    if (index < 0 || index >= encodePanelQueue.length) return;
    encodePanelQueue.splice(index, 1);
    renderEncodePanel();
}

function renderEncodePanel() {
    const tbody = document.getElementById('encodePanelTable');
    if (!tbody) return;

    if (!encodePanelQueue || encodePanelQueue.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="padding: 18px; color: var(--text-muted);">Nimic aici încă. Apasă Encodare în stânga.</td></tr>';
        return;
    }

    let html = '';
    encodePanelQueue.forEach((v, i) => {
        const id = String(v.id);
        const title = String(v.title || '').substring(0, 120);
        const isTested = testedVideoIds.has(id);
        const hasPreview = previewByVideoId.has(id);
        const st = jobStateByVideoId.get(id) || null;
        const status = st && st.status ? String(st.status) : (isTested ? 'done' : '');
        const pct = st && typeof st.progress === 'number' ? st.progress : null;
        const showPct = (pct !== null && !Number.isNaN(pct));
        const statusTxt = status ? escapeHtml(status) : (isTested ? 'done' : '');
        const pctTxt = showPct ? (' ' + Math.max(0, Math.min(100, pct)) + '%') : '';
        const safeIdAttr = id.replace(/"/g, '&quot;');
        const watchDisabled = hasPreview ? '' : 'opacity:.45;';
        const watchDisabledAttr = hasPreview ? '' : 'disabled';

        html += `
            <tr>
                <td style="font-weight: 700; color: var(--text-primary);">${isTested ? '✓ ' : ''}${escapeHtml(title)}</td>
                <td style="color: var(--text-secondary); font-size: 12px;">${statusTxt}${pctTxt}</td>
                <td>
                    <div class="btn-row" style="gap:8px; justify-content:flex-start;">
                        <button type="button" class="btn btn-yellow js-encodepanel-test" data-video-id="${safeIdAttr}" style="padding: 6px 10px; font-size: 12px;">Test</button>
                        <button type="button" class="btn btn-save js-encodepanel-watch" data-video-id="${safeIdAttr}" ${watchDisabledAttr} style="padding: 6px 10px; font-size: 12px; ${watchDisabled}">Watch</button>
                        <button type="button" class="btn btn-red js-encodepanel-remove" data-index="${i}" style="padding: 6px 10px; font-size: 12px;">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function encodePanelDeleteAll() {
    if (!encodePanelQueue || encodePanelQueue.length === 0) return;
    if (!confirm('Ștergi toate video-urile din lista Test Video?')) return;
    encodePanelQueue = [];
    renderEncodePanel();
}

async function addSelectedToPlaylist() {
    updateSelected();
    if (!selectedVideos || selectedVideos.length === 0) {
        alert('Select videos first!');
        return;
    }

    const ok = await persistVideosToPlaylist(selectedVideos);
    if (ok) alert('✅ Added selected videos to playlist');
}

async function persistVideosToPlaylist(videoIds) {
    const ids = (videoIds || []).map(v => String(v)).filter(Boolean);
    if (ids.length === 0) return true;

    try {
        const response = await fetch(`/vod-channels/${CHANNEL_ID}/playlist/add-bulk`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({ video_ids: ids.join(',') }).toString()
        });

        // This endpoint normally redirects for non-AJAX flows; treat any 2xx as success.
        if (response.ok) return true;

        const text = await response.text().catch(() => '');
        alert('❌ Failed to add to playlist' + (text ? (': ' + text) : ''));
        return false;
    } catch (e) {
        alert('Error: ' + e.message);
        return false;
    }
}

function clearSelection() {
    const tbody = document.getElementById('videosTable');
    (tbody ? tbody.querySelectorAll('.videoCheck') : document.querySelectorAll('.videoCheck')).forEach(el => el.checked = false);
    const sa = document.getElementById('selectAll');
    if (sa) sa.checked = false;
    testedVideoIds.clear();
    updateTestedCount();
    updateSelected();
}

function updateTestedCount() {
    const el = document.getElementById('testedCount');
    if (el) el.textContent = String(testedVideoIds.size);
}

function updateQueueDisplay() {
    const queueDiv = document.getElementById('encodeQueue');
    document.getElementById('queueCount').textContent = encodeQueue.length;
    updateTestedCount();

    if (encodeQueue.length === 0) {
        queueDiv.innerHTML = '<div style="font-size: 12px; color: var(--text-muted);">Bifează filmele din dreapta — apar aici.</div>';
        return;
    }

    let html = '';
    encodeQueue.forEach((v, i) => {
        const isActive = String(v.id) === String(activePreviewVideoId);
        const isTested = testedVideoIds.has(String(v.id));
        const hasPreview = previewByVideoId.has(String(v.id));
        const st = jobStateByVideoId.get(String(v.id)) || null;
        const pct = st && typeof st.progress === 'number' ? st.progress : null;
        const showPct = (pct !== null && !Number.isNaN(pct));
        const speedTxt = st && st.speed ? String(st.speed) : '';
        const style = isActive
            ? 'border:1px solid var(--border-color); background: var(--border-light);'
            : 'border:1px solid var(--border-color); background: transparent;';
        const safeIdAttr = String(v.id).replace(/"/g, '&quot;');
        html += `
            <div class="queue-item" style="${style}">
                <span style="flex:1; ${isActive ? 'font-weight:600;' : ''}">${isTested ? '✓ ' : ''}${escapeHtml(String(v.title || '').substring(0, 60))}</span>
                <div class="prog" aria-hidden="true">
                    <div class="prog-bar"><div class="prog-fill" style="width:${showPct ? Math.max(0, Math.min(100, pct)) : 0}%;"></div></div>
                    <div class="prog-txt">${showPct ? (Math.max(0, Math.min(100, pct)) + '%') : ''}${speedTxt ? (' · ' + escapeHtml(speedTxt)) : ''}</div>
                </div>
                <button type="button" class="js-queue-remove" data-index="${i}" title="Unselect">✕</button>
            </div>
        `;
    });
    queueDiv.innerHTML = html;
}

function removeFromQueue(index) {
    const v = encodeQueue[index];
    if (v && v.id) {
        const id = String(v.id);
        testedVideoIds.delete(id);
        const checkbox = Array.from(document.querySelectorAll('.videoCheck')).find(el => String(el.value) === id);
        if (checkbox) checkbox.checked = false;
    }
    updateSelected();
}

async function testEncoding(channelId, forcedVideoId = null) {
    if (!forcedVideoId) {
        updateSelected();
    }

    // Prefer: explicit video id (from table), else active/selected.
    // Fallback: dacă nu e nimic bifat, backend-ul ia primul video din playlist.
    const videoId = forcedVideoId
        ? String(forcedVideoId)
        : (activePreviewVideoId
            ? String(activePreviewVideoId)
            : ((selectedVideos && selectedVideos.length > 0) ? String(selectedVideos[0]) : null));

    if (videoId) setActivePreviewVideo(String(videoId));

    const settings = collectEncodingSettings();
    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/test-encode`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(videoId ? { video_id: videoId, settings } : { settings })
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok) {
            startJobsPolling();
            alert(json.message || '✅ Test 30s started');
        } else {
            alert(json.message || '❌ Test eșuat');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function encodePanelEncodeAll(channelId) {
    if (!encodePanelQueue || encodePanelQueue.length === 0) {
        alert('Nu ai nimic în lista de encodare. Apasă Encodare în stânga.');
        return;
    }

    const ids = encodePanelQueue.map(v => Number(v.id)).filter(n => Number.isFinite(n));
    if (ids.length === 0) {
        alert('Lista de encodare e invalidă.');
        return;
    }

    if (!confirm(`Encodează tot din lista de encodare (${ids.length})?`)) return;

    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ video_ids: ids, settings: collectEncodingSettings() })
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.status === 'success') {
            alert(json.message || '✅ Encoding started');
        } else {
            alert(json.message || '❌ Encoding failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function deleteTestVideo(videoId) {
    const id = String(videoId);
    if (!confirm('Ștergi testul (preview) pentru acest video?')) return;
    try {
        const response = await fetch(`/vod-channels/${CHANNEL_ID}/engine/delete-test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ video_id: Number(id) })
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            alert(json.message || '❌ Delete failed');
            return;
        }
        previewByVideoId.delete(id);
        testedVideoIds.delete(id);
        updateTestedCount();
        updateQueueDisplay();
        alert(json.message || '✅ Test deleted');
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

function startJobsPolling() {
    if (jobsPollTimer) return;
    jobsPollTimer = setInterval(() => pollEncodingJobs().catch(() => {}), 1500);
    pollEncodingJobs().catch(() => {});
}

async function pollEncodingJobs() {
    const response = await fetch(`/vod-channels/${CHANNEL_ID}/engine/encoding-jobs`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const json = await response.json().catch(() => ({}));
    if (!response.ok || json.status !== 'success') return;

    const jobs = Array.isArray(json.jobs) ? json.jobs : [];

    // Build latest state per video_id (prefer newest job id)
    const latest = new Map();
    for (const j of jobs) {
        const vid = j && j.video_id ? String(j.video_id) : null;
        if (!vid) continue;
        const prev = latest.get(vid);
        if (!prev || Number(j.id) > Number(prev.id)) latest.set(vid, j);
    }

    latest.forEach((j, vid) => {
        jobStateByVideoId.set(String(vid), {
            id: Number(j.id),
            status: String(j.status || ''),
            progress: Number(j.progress || 0),
            speed: j.speed ? String(j.speed) : '',
            output_url: j.output_url ? String(j.output_url) : ''
        });

        // If this is a completed TEST job with output_url, make it playable + mark tested.
        if ((String(j.status) === 'done') && j.output_url) {
            previewByVideoId.set(String(vid), String(j.output_url));
            testedVideoIds.add(String(vid));
        }
    });

    updateTestedCount();
    updateQueueDisplay();

    // Refresh encode panel thumbnails/status when previews arrive.
    renderEncodePanel();
}

async function startEncodingTested(channelId) {
    updateSelected();

    const testedSelected = (selectedVideos || [])
        .map(v => String(v))
        .filter(v => testedVideoIds.has(v));

    if (testedSelected.length === 0) {
        alert('Nu ai niciun video testat. Apasă Test pe un video, apoi Encode Tested.');
        return;
    }

    if (!confirm(`Encode doar video-urile testate (${testedSelected.length})?`)) return;

    // Edit Playlist shows only encoded outputs; don't pre-add here.

    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ video_ids: testedSelected.map(v => Number(v)), settings: collectEncodingSettings() })
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.status === 'success') {
            alert(json.message || '✅ Encoding started');
        } else {
            alert(json.message || '❌ Encoding failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function syncPlaylist(channelId) {
    if (!confirm('Sync playlist from saved channel category?')) return;
    try {
        const response = await fetch(`/vod-channels/${channelId}/sync-playlist-from-category`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.success) {
            alert(json.message || '✅ Playlist synced');
        } else {
            alert(json.message || '❌ Sync failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function startEncodingJobs(channelId) {
    updateSelected();

    // If user selected videos in Import VOD, encode only those.
    if (selectedVideos && selectedVideos.length > 0) {
        if (!confirm(`Pornești encodarea pentru selecție (${selectedVideos.length})? (În Edit Playlist apar doar cele encodate.)`)) return;

        try {
            const response = await fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ video_ids: selectedVideos.map(v => Number(v)), settings: collectEncodingSettings() })
            });
            const json = await response.json().catch(() => ({}));
            if (response.ok && json.status === 'success') {
                alert(json.message || '✅ Encoding started');
            } else {
                alert(json.message || '❌ Encoding failed');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
        return;
    }

    if (!confirm('Start encoding jobs for playlist videos?')) return;
    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ settings: collectEncodingSettings() })
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.status === 'success') {
            alert(json.message || '✅ Encoding started');
        } else {
            alert(json.message || '❌ Encoding failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function startEncodingSelected(channelId) {
    updateSelected();
    if (!selectedVideos || selectedVideos.length === 0) {
        alert('Selectează cel puțin un video pentru Encode Selected.');
        return;
    }

    if (!confirm(`Encode doar selecția (${selectedVideos.length})?`)) return;

    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/start-encoding`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ video_ids: selectedVideos.map(v => Number(v)), settings: collectEncodingSettings() })
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.status === 'success') {
            alert(json.message || '✅ Encoding started');
        } else {
            alert(json.message || '❌ Encoding failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function collectEncodingSettings() {
    const form = document.getElementById('channelSettingsForm');
    const settings = {};

    const read = (name) => {
        if (!form) return null;
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) return null;
        if (el.type === 'checkbox') return el.checked ? 1 : 0;
        return (el.value ?? '').toString();
    };

    const keys = [
        'resolution',
        'manual_override_encoding',
        'manual_encode_enabled',
        'manual_width',
        'manual_height',
        'manual_bitrate',
        'manual_codec',
        'manual_preset',
        'manual_audio_codec',
        'manual_audio_bitrate',
        'overlay_logo_enabled',
        'overlay_logo_position',
        'overlay_logo_x',
        'overlay_logo_y',
        'overlay_logo_width',
        'overlay_logo_height',
        'overlay_logo_opacity',
        'overlay_text_enabled',
        'overlay_text_content',
        'overlay_text_custom',
        'overlay_text_font_family',
        'overlay_text_font_size',
        'overlay_text_color',
        'overlay_text_padding',
        'overlay_text_position',
        'overlay_text_x',
        'overlay_text_y',
        'overlay_text_opacity',
        'overlay_text_bg_color',
        'overlay_text_bg_opacity',
        'overlay_timer_enabled',
        'overlay_timer_mode',
        'overlay_timer_format',
        'overlay_timer_position',
        'overlay_timer_x',
        'overlay_timer_y',
        'overlay_timer_font_size',
        'overlay_timer_color',
        'overlay_timer_style',
        'overlay_timer_bg',
        'overlay_timer_opacity',
        'overlay_safe_margin',
    ];

    for (const k of keys) {
        const v = read(k);
        if (v !== null) settings[k] = v;
    }

    // Quick overrides from the right-hand Encoding card (without Save)
    const resEl = document.getElementById('resolution');
    const vbEl = document.getElementById('videoBitrate');
    const abEl = document.getElementById('audioBitrate');
    if (resEl && resEl.value && resEl.value.trim() !== '') settings.resolution = resEl.value.trim();
    if (vbEl && vbEl.value && String(vbEl.value).trim() !== '') {
        settings.manual_override_encoding = 1;
        settings.manual_bitrate = String(vbEl.value).trim();
    }
    if (abEl && abEl.value && String(abEl.value).trim() !== '') {
        settings.manual_override_encoding = 1;
        settings.manual_audio_bitrate = String(abEl.value).trim();
    }

    // Normalize checkbox values to numbers
    settings.overlay_logo_enabled = Number(settings.overlay_logo_enabled || 0);
    settings.overlay_text_enabled = Number(settings.overlay_text_enabled || 0);
    settings.overlay_timer_enabled = Number(settings.overlay_timer_enabled || 0);
    settings.manual_override_encoding = Number(settings.manual_override_encoding || 0);
    settings.manual_encode_enabled = Number(settings.manual_encode_enabled || 0);

    return settings;
}

async function startChannel(channelId) {
    if (!confirm('Start channel now?')) return;
    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/start`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.status === 'success') {
            alert(json.message || '✅ Channel started');
            await refreshOutputs(channelId);
        } else {
            alert(json.message || '❌ Start failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function stopChannel(channelId) {
    if (!confirm('Stop channel now?')) return;
    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/stop`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await response.json().catch(() => ({}));
        if (response.ok && json.status === 'success') {
            alert(json.message || '✅ Channel stopped');
        } else {
            alert(json.message || '❌ Stop failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function refreshOutputs(channelId) {
    try {
        const response = await fetch(`/vod-channels/${channelId}/engine/outputs`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const json = await response.json().catch(() => ({}));
        if (!response.ok || json.status !== 'success') return;

        const hls = (json.streams || []).find(s => String(s.format) === 'hls');
        const ts = (json.streams || []).find(s => String(s.format) === 'mpegts');

        const hlsUrl = hls?.url || '';
        const tsUrl = ts?.url || '';
        document.getElementById('streamHlsUrl').value = hlsUrl;
        document.getElementById('streamTsUrl').value = tsUrl;

        // Player UI removed (URLs only).
    } catch (e) {
        // silent
    }
}

function setPreviewPlayer(url) {
    const player = document.getElementById('previewPlayer');
    if (!player) return;
    player.src = url;
    player.load();
    player.play().catch(() => {});
}

function attachHlsPlayer(url) {
    const video = document.getElementById('hlsPlayer');
    if (!video) return;

    // Native HLS (Safari)
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        if (video.src !== url) {
            video.src = url;
            video.load();
        }
        return;
    }

    // hls.js fallback (Chrome/Firefox)
    if (window.Hls && window.Hls.isSupported()) {
        if (window.__hlsInstance) {
            window.__hlsInstance.destroy();
        }
        const hls = new window.Hls({ lowLatencyMode: true });
        window.__hlsInstance = hls;
        hls.loadSource(url);
        hls.attachMedia(video);
        return;
    }
}

function escapeHtml(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function ffprobeScanSelected() {
    updateSelected();
    if (!selectedVideos || selectedVideos.length === 0) {
        alert('Select videos first!');
        return;
    }

    if (selectedVideos.length > 10) {
        alert('Select maximum 10 videos for FFprobe scan.');
        return;
    }

    try {
        const response = await fetch(`/api/videos/probe`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ video_ids: selectedVideos.map(v => Number(v)) })
        });

        const json = await response.json().catch(() => ({}));
        if (!response.ok || json.ok === false) {
            alert(json.message || 'FFprobe scan failed');
            return;
        }

        const failed = (json.results || []).filter(r => !r.ok);
        if (failed.length) {
            alert(`FFprobe done. Failed: ${failed.length}`);
        } else {
            alert('FFprobe done. Metadata updated.');
        }

        await loadVideos();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Encoding presets (4K/2K/1080/720): fill manual sizes + recommended overlay sizes.
    const preset = document.getElementById('encodingPreset');
    const manualOverride = document.querySelector('[name="manual_override_encoding"]');
    const manualW = document.querySelector('[name="manual_width"]');
    const manualH = document.querySelector('[name="manual_height"]');
    const logoW = document.getElementById('overlay_logo_width');
    const logoH = document.getElementById('overlay_logo_height');
    const textFs = document.getElementById('overlay_text_font_size');
    const timerFs = document.getElementById('overlay_timer_font_size');

    const applyPreset = (value) => {
        const v = String(value || '').trim();
        if (!v) return;

        const map = {
            '3840x2160': { w: 3840, h: 2160, logoW: 260, logoH: 170, titleFs: 52, timerFs: 44 },
            '2560x1440': { w: 2560, h: 1440, logoW: 200, logoH: 130, titleFs: 40, timerFs: 34 },
            '1920x1080': { w: 1920, h: 1080, logoW: 150, logoH: 100, titleFs: 28, timerFs: 24 },
            '1280x720':  { w: 1280, h: 720,  logoW: 110, logoH: 75,  titleFs: 22, timerFs: 18 },
        };

        const p = map[v];
        if (!p) return;

        if (manualOverride) manualOverride.checked = true;
        if (manualW) manualW.value = String(p.w);
        if (manualH) manualH.value = String(p.h);

        if (logoW) logoW.value = String(p.logoW);
        if (logoH) logoH.value = String(p.logoH);
        if (textFs) textFs.value = String(p.titleFs);
        if (timerFs) timerFs.value = String(p.timerFs);
    };

    if (preset) {
        preset.addEventListener('change', () => applyPreset(preset.value));
    }

    // Load initial outputs (if already running)
    refreshOutputs(CHANNEL_ID);
});
</script>
@endsection
