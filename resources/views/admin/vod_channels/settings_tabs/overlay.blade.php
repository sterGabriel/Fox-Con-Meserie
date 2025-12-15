<!-- OVERLAY TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm space-y-6">
    <h2 class="text-lg font-semibold text-slate-100">🎨 Overlay Configuration</h2>

    <!-- LOGO SECTION -->
    <div class="p-4 bg-slate-800/20 rounded-lg border border-slate-600/20">
        <label class="flex items-center gap-3 cursor-pointer mb-4">
            <input type="checkbox" name="overlay_logo_enabled" value="1" {{ old('overlay_logo_enabled', $channel->overlay_logo_enabled ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
            <span class="font-medium text-slate-300">Enable Logo</span>
        </label>

        <div class="space-y-3 {{ old('overlay_logo_enabled', $channel->overlay_logo_enabled ?? false) ? '' : 'hidden' }}">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Upload Logo (PNG/SVG)</label>
                <input type="file" name="overlay_logo_file" accept="image/png,image/svg+xml" class="w-full text-sm text-slate-400">
                @if($channel->overlay_logo_path)
                    <p class="text-xs text-slate-500 mt-1">Current: {{ $channel->overlay_logo_path }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Position</label>
                    <select name="overlay_logo_position" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                        <option value="TL" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? 'TL') === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">X Offset (px)</label>
                    <input type="number" name="overlay_logo_x" value="{{ old('overlay_logo_x', $channel->overlay_logo_x ?? 20) }}" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Y Offset (px)</label>
                    <input type="number" name="overlay_logo_y" value="{{ old('overlay_logo_y', $channel->overlay_logo_y ?? 20) }}" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Size (px)</label>
                    <input type="number" name="overlay_logo_width" value="{{ old('overlay_logo_width', $channel->overlay_logo_width ?? 100) }}" placeholder="width" class="w-full px-2 py-1 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-2">Opacity: <span id="logo-opacity-val">80</span>%</label>
                <input type="range" name="overlay_logo_opacity" min="0" max="100" value="{{ old('overlay_logo_opacity', $channel->overlay_logo_opacity ?? 80) }}" class="w-full" id="logo-opacity">
            </div>
        </div>
    </div>

    <!-- TEXT SECTION -->
    <div class="p-4 bg-slate-800/20 rounded-lg border border-slate-600/20">
        <label class="flex items-center gap-3 cursor-pointer mb-4">
            <input type="checkbox" name="overlay_text_enabled" value="1" {{ old('overlay_text_enabled', $channel->overlay_text_enabled ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
            <span class="font-medium text-slate-300">Enable Text Overlay</span>
        </label>

        <div class="space-y-3 {{ old('overlay_text_enabled', $channel->overlay_text_enabled ?? false) ? '' : 'hidden' }}">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Content Type</label>
                <select name="overlay_text_content" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                    <option value="channel_name" {{ old('overlay_text_content', $channel->overlay_text_content ?? 'channel_name') === 'channel_name' ? 'selected' : '' }}>Channel Name</option>
                    <option value="title" {{ old('overlay_text_content', $channel->overlay_text_content ?? '') === 'title' ? 'selected' : '' }}>Movie Title</option>
                    <option value="custom" {{ old('overlay_text_content', $channel->overlay_text_content ?? '') === 'custom' ? 'selected' : '' }}>Custom Text</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Custom Text</label>
                <input type="text" name="overlay_text_custom" value="{{ old('overlay_text_custom', $channel->overlay_text_custom ?? 'Live Stream') }}" placeholder="Leave blank for dynamic" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Font Size (px)</label>
                    <input type="number" name="overlay_text_font_size" value="{{ old('overlay_text_font_size', $channel->overlay_text_font_size ?? 24) }}" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">BG Color</label>
                    <input type="color" name="overlay_text_bg_color" value="{{ old('overlay_text_bg_color', $channel->overlay_text_bg_color ?? '#000000') }}" class="w-full px-2 py-2 h-10 rounded">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-2">BG Opacity: <span id="text-opacity-val">50</span>%</label>
                <input type="range" name="overlay_text_bg_opacity" min="0" max="100" value="{{ old('overlay_text_bg_opacity', $channel->overlay_text_bg_opacity ?? 50) }}" class="w-full" id="text-opacity">
            </div>
        </div>
    </div>

    <!-- TIMER SECTION -->
    <div class="p-4 bg-slate-800/20 rounded-lg border border-slate-600/20">
        <label class="flex items-center gap-3 cursor-pointer mb-4">
            <input type="checkbox" name="overlay_timer_enabled" value="1" {{ old('overlay_timer_enabled', $channel->overlay_timer_enabled ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
            <span class="font-medium text-slate-300">Enable Timer / Clock</span>
        </label>

        <div class="space-y-3 {{ old('overlay_timer_enabled', $channel->overlay_timer_enabled ?? false) ? '' : 'hidden' }}">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Format</label>
                    <select name="overlay_timer_format" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                        <option value="HH:mm" {{ old('overlay_timer_format', $channel->overlay_timer_format ?? 'HH:mm') === 'HH:mm' ? 'selected' : '' }}>HH:mm</option>
                        <option value="HH:mm:ss" {{ old('overlay_timer_format', $channel->overlay_timer_format ?? '') === 'HH:mm:ss' ? 'selected' : '' }}>HH:mm:ss</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Position</label>
                    <select name="overlay_timer_position" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                        <option value="TL" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? 'TR') === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? 'TR') === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? '') === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? '') === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">X Offset (px)</label>
                    <input type="number" name="overlay_timer_x" value="{{ old('overlay_timer_x', $channel->overlay_timer_x ?? 20) }}" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Y Offset (px)</label>
                    <input type="number" name="overlay_timer_y" value="{{ old('overlay_timer_y', $channel->overlay_timer_y ?? 20) }}" class="w-full px-2 py-2 text-xs bg-slate-950/30 border border-slate-600/20 rounded text-slate-200">
                </div>
            </div>
        </div>
    </div>

    <!-- SAFE MARGINS -->
    <div class="p-4 bg-slate-800/20 rounded-lg border border-slate-600/20">
        <label class="block text-xs font-medium text-slate-400 mb-2">Safe Margin: <span id="margin-val">20</span>px</label>
        <input type="range" name="overlay_safe_margin" min="0" max="50" value="{{ old('overlay_safe_margin', $channel->overlay_safe_margin ?? 20) }}" class="w-full" id="safe-margin">
    </div>

    <!-- Filter Preview -->
    <div>
        <label class="block text-sm font-medium text-slate-300 mb-2">Filter Graph (Preview)</label>
        <div class="p-3 bg-slate-950/50 border border-slate-600/20 rounded-lg font-mono text-xs text-slate-300 h-24 overflow-y-auto">
            <div id="filter-preview">-filter_complex "[0:v]scale=..." (click refresh)</div>
        </div>
    </div>
</div>

<script>
document.getElementById('logo-opacity').addEventListener('input', e => {
    document.getElementById('logo-opacity-val').textContent = e.target.value;
});
document.getElementById('text-opacity').addEventListener('input', e => {
    document.getElementById('text-opacity-val').textContent = e.target.value;
});
document.getElementById('safe-margin').addEventListener('input', e => {
    document.getElementById('margin-val').textContent = e.target.value;
});

document.querySelectorAll('input[name^="overlay_"]').forEach(el => {
    if (el.type === 'checkbox') {
        el.addEventListener('change', function() {
            const fieldName = this.name.replace('_enabled', '_fields');
            const fields = document.querySelector('[data-field-group="' + this.name + '"]');
        });
    }
});
</script>
