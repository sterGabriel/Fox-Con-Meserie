<!-- OVERLAY TAB - PROFESSIONAL TV PANEL BUILDER -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm space-y-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-100">🎨 Professional Overlay Builder</h2>
            <p class="text-xs text-slate-400 mt-1">Real-time FFmpeg graphics composition with positioning controls</p>
        </div>
        <div class="text-right">
            <div class="text-2xl" id="overlay-preview-icon">📺</div>
            <p class="text-xs text-slate-500">Preview updates on save</p>
        </div>
    </div>

    <!-- LOGO OVERLAY SECTION -->
    <div class="p-5 bg-gradient-to-br from-slate-800/40 to-slate-900/20 rounded-xl border border-slate-600/30 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="overlay_logo_enabled" value="1" {{ old('overlay_logo_enabled', $channel->overlay_logo_enabled ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500 rounded" id="logo-enabled">
                <span class="font-semibold text-slate-200">📍 Logo Overlay</span>
            </label>
            <span class="text-xs px-2 py-1 bg-blue-500/20 text-blue-300 rounded-full">Positioning Required</span>
        </div>

        <div id="logo-section" class="space-y-4 {{ old('overlay_logo_enabled', $channel->overlay_logo_enabled ?? false) ? '' : 'hidden' }}">
            <!-- File Upload -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Logo File (PNG/SVG)</label>
                <div class="flex items-center gap-3">
                    <input type="file" name="overlay_logo_file" accept="image/png,image/svg+xml" class="flex-1 text-sm text-slate-400 file:px-3 file:py-2 file:bg-slate-700 file:border-0 file:rounded file:text-slate-200 file:cursor-pointer">
                    @if($channel->overlay_logo_path)
                        <span class="text-xs text-slate-500 px-2 py-1 bg-slate-800/50 rounded">{{ basename($channel->overlay_logo_path) }}</span>
                    @endif
                </div>
            </div>

            <!-- Position Selection -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Position</label>
                    <select name="overlay_logo_position" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" id="logo-position">
                        <option value="">-- Select --</option>
                        <option value="TL" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="CUSTOM" {{ old('overlay_logo_position', $channel->overlay_logo_position ?? '') === 'CUSTOM' ? 'selected' : '' }}>Custom (X/Y)</option>
                    </select>
                </div>

                <!-- X Offset -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">X Offset (px)</label>
                    <input type="number" name="overlay_logo_x" value="{{ old('overlay_logo_x', $channel->overlay_logo_x ?? 20) }}" min="0" max="1920" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <!-- Y Offset -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Y Offset (px)</label>
                    <input type="number" name="overlay_logo_y" value="{{ old('overlay_logo_y', $channel->overlay_logo_y ?? 20) }}" min="0" max="1080" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <!-- Width -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Width (px)</label>
                    <input type="number" name="overlay_logo_width" value="{{ old('overlay_logo_width', $channel->overlay_logo_width ?? 150) }}" min="20" max="500" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>

                <!-- Height -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Height (px)</label>
                    <input type="number" name="overlay_logo_height" value="{{ old('overlay_logo_height', $channel->overlay_logo_height ?? 100) }}" min="20" max="500" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                </div>
            </div>

            <!-- Opacity Control -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-semibold text-slate-400">Opacity</label>
                    <span class="text-xs font-mono text-blue-400" id="logo-opacity-display">{{ old('overlay_logo_opacity', $channel->overlay_logo_opacity ?? 80) }}%</span>
                </div>
                <input type="range" name="overlay_logo_opacity" min="0" max="100" value="{{ old('overlay_logo_opacity', $channel->overlay_logo_opacity ?? 80) }}" class="w-full accent-blue-500" id="logo-opacity-slider">
            </div>

            <!-- Logo Preview -->
            <div class="p-3 bg-slate-950/50 rounded-lg border border-slate-600/20 text-center">
                <p class="text-xs text-slate-500 mb-2">Logo Preview</p>
                <div class="w-full h-24 bg-slate-900/50 rounded border border-slate-700/50 flex items-center justify-center overflow-hidden">
                    @if($channel->overlay_logo_path)
                        <img src="{{ asset($channel->overlay_logo_path) }}" alt="Logo Preview" class="max-w-full max-h-full object-contain" id="logo-preview-img">
                    @else
                        <span class="text-xs text-slate-600">No logo selected</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- TEXT OVERLAY SECTION -->
    <div class="p-5 bg-gradient-to-br from-slate-800/40 to-slate-900/20 rounded-xl border border-slate-600/30 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="overlay_text_enabled" value="1" {{ old('overlay_text_enabled', $channel->overlay_text_enabled ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-green-500 rounded" id="text-enabled">
                <span class="font-semibold text-slate-200">📝 Text Overlay</span>
            </label>
            <span class="text-xs px-2 py-1 bg-green-500/20 text-green-300 rounded-full">Font & Position</span>
        </div>

        <div id="text-section" class="space-y-4 {{ old('overlay_text_enabled', $channel->overlay_text_enabled ?? false) ? '' : 'hidden' }}">
            <!-- Text Source -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Text Source</label>
                <select name="overlay_text_content" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500" id="text-source">
                    <option value="channel_name" {{ old('overlay_text_content', $channel->overlay_text_content ?? 'channel_name') === 'channel_name' ? 'selected' : '' }}>Channel Name (Dynamic)</option>
                    <option value="title" {{ old('overlay_text_content', $channel->overlay_text_content ?? '') === 'title' ? 'selected' : '' }}>Video Title (Dynamic)</option>
                    <option value="custom" {{ old('overlay_text_content', $channel->overlay_text_content ?? '') === 'custom' ? 'selected' : '' }}>Custom Text (Static)</option>
                </select>
            </div>

            <!-- Custom Text Input -->
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-2">Custom Text (if selected above)</label>
                <input type="text" name="overlay_text_custom" value="{{ old('overlay_text_custom', $channel->overlay_text_custom ?? 'LIVE') }}" placeholder="e.g., © 2024 Network" maxlength="100" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 placeholder-slate-600 focus:border-green-500 focus:ring-1 focus:ring-green-500">
            </div>

            <!-- Typography Controls -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Font Family</label>
                    <select name="overlay_text_font_family" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                        <option value="Arial" {{ old('overlay_text_font_family', $channel->overlay_text_font_family ?? 'Arial') === 'Arial' ? 'selected' : '' }}>Arial</option>
                        <option value="Helvetica" {{ old('overlay_text_font_family', $channel->overlay_text_font_family ?? '') === 'Helvetica' ? 'selected' : '' }}>Helvetica</option>
                        <option value="Courier" {{ old('overlay_text_font_family', $channel->overlay_text_font_family ?? '') === 'Courier' ? 'selected' : '' }}>Courier</option>
                        <option value="Times" {{ old('overlay_text_font_family', $channel->overlay_text_font_family ?? '') === 'Times' ? 'selected' : '' }}>Times New Roman</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Font Size (px)</label>
                    <input type="number" name="overlay_text_font_size" value="{{ old('overlay_text_font_size', $channel->overlay_text_font_size ?? 28) }}" min="12" max="120" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Font Color</label>
                    <input type="color" name="overlay_text_color" value="{{ old('overlay_text_color', $channel->overlay_text_color ?? '#FFFFFF') }}" class="w-full h-10 px-2 rounded-lg border border-slate-600/40 cursor-pointer">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">BG Padding (px)</label>
                    <input type="number" name="overlay_text_padding" value="{{ old('overlay_text_padding', $channel->overlay_text_padding ?? 6) }}" min="0" max="30" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                </div>
            </div>

            <!-- Background Controls -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">BG Color</label>
                    <input type="color" name="overlay_text_bg_color" value="{{ old('overlay_text_bg_color', $channel->overlay_text_bg_color ?? '#000000') }}" class="w-full h-10 rounded-lg border border-slate-600/40 cursor-pointer">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-slate-400">BG Opacity</label>
                        <span class="text-xs font-mono text-green-400" id="text-bg-opacity-display">{{ old('overlay_text_bg_opacity', $channel->overlay_text_bg_opacity ?? 60) }}%</span>
                    </div>
                    <input type="range" name="overlay_text_bg_opacity" min="0" max="100" value="{{ old('overlay_text_bg_opacity', $channel->overlay_text_bg_opacity ?? 60) }}" class="w-full accent-green-500" id="text-bg-opacity-slider">
                </div>
            </div>

            <!-- Position Controls -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Position</label>
                    <select name="overlay_text_position" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500" id="text-position">
                        <option value="">-- Select --</option>
                        <option value="TL" {{ old('overlay_text_position', $channel->overlay_text_position ?? '') === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ old('overlay_text_position', $channel->overlay_text_position ?? '') === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ old('overlay_text_position', $channel->overlay_text_position ?? '') === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ old('overlay_text_position', $channel->overlay_text_position ?? '') === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="CUSTOM" {{ old('overlay_text_position', $channel->overlay_text_position ?? '') === 'CUSTOM' ? 'selected' : '' }}>Custom (X/Y)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">X Offset (px)</label>
                    <input type="number" name="overlay_text_x" value="{{ old('overlay_text_x', $channel->overlay_text_x ?? 20) }}" min="0" max="1920" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Y Offset (px)</label>
                    <input type="number" name="overlay_text_y" value="{{ old('overlay_text_y', $channel->overlay_text_y ?? 20) }}" min="0" max="1080" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                </div>

                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-slate-400">Text Opacity</label>
                        <span class="text-xs font-mono text-green-400" id="text-opacity-display">{{ old('overlay_text_opacity', $channel->overlay_text_opacity ?? 100) }}%</span>
                    </div>
                    <input type="range" name="overlay_text_opacity" min="0" max="100" value="{{ old('overlay_text_opacity', $channel->overlay_text_opacity ?? 100) }}" class="w-full accent-green-500" id="text-opacity-slider">
                </div>
            </div>
        </div>
    </div>

    <!-- TIMER/CLOCK SECTION -->
    <div class="p-5 bg-gradient-to-br from-slate-800/40 to-slate-900/20 rounded-xl border border-slate-600/30 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="overlay_timer_enabled" value="1" {{ old('overlay_timer_enabled', $channel->overlay_timer_enabled ?? false) ? 'checked' : '' }} class="w-5 h-5 accent-purple-500 rounded" id="timer-enabled">
                <span class="font-semibold text-slate-200">⏱️ Timer / Clock</span>
            </label>
            <span class="text-xs px-2 py-1 bg-purple-500/20 text-purple-300 rounded-full">Real-time Display</span>
        </div>

        <div id="timer-section" class="space-y-4 {{ old('overlay_timer_enabled', $channel->overlay_timer_enabled ?? false) ? '' : 'hidden' }}">
            <!-- Timer Mode -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Timer Type</label>
                    <select name="overlay_timer_mode" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500" id="timer-mode">
                        <option value="realtime" {{ old('overlay_timer_mode', $channel->overlay_timer_mode ?? 'realtime') === 'realtime' ? 'selected' : '' }}>Real Time (System Clock)</option>
                        <option value="elapsed" {{ old('overlay_timer_mode', $channel->overlay_timer_mode ?? '') === 'elapsed' ? 'selected' : '' }}>Elapsed (Stream Duration)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Time Format</label>
                    <select name="overlay_timer_format" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500" id="timer-format">
                        <option value="HH:mm" {{ old('overlay_timer_format', $channel->overlay_timer_format ?? 'HH:mm') === 'HH:mm' ? 'selected' : '' }}>HH:mm (14:30)</option>
                        <option value="HH:mm:ss" {{ old('overlay_timer_format', $channel->overlay_timer_format ?? '') === 'HH:mm:ss' ? 'selected' : '' }}>HH:mm:ss (14:30:45)</option>
                        <option value="HH:mm:ss.mmm" {{ old('overlay_timer_format', $channel->overlay_timer_format ?? '') === 'HH:mm:ss.mmm' ? 'selected' : '' }}>HH:mm:ss.mmm (milliseconds)</option>
                    </select>
                </div>
            </div>

            <!-- Position & Styling -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Position</label>
                    <select name="overlay_timer_position" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500" id="timer-position">
                        <option value="">-- Select --</option>
                        <option value="TL" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? '') === 'TL' ? 'selected' : '' }}>Top Left</option>
                        <option value="TR" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? 'TR') === 'TR' ? 'selected' : '' }}>Top Right</option>
                        <option value="BL" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? '') === 'BL' ? 'selected' : '' }}>Bottom Left</option>
                        <option value="BR" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? '') === 'BR' ? 'selected' : '' }}>Bottom Right</option>
                        <option value="CUSTOM" {{ old('overlay_timer_position', $channel->overlay_timer_position ?? '') === 'CUSTOM' ? 'selected' : '' }}>Custom (X/Y)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">X Offset (px)</label>
                    <input type="number" name="overlay_timer_x" value="{{ old('overlay_timer_x', $channel->overlay_timer_x ?? 20) }}" min="0" max="1920" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Y Offset (px)</label>
                    <input type="number" name="overlay_timer_y" value="{{ old('overlay_timer_y', $channel->overlay_timer_y ?? 20) }}" min="0" max="1080" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Font Size (px)</label>
                    <input type="number" name="overlay_timer_font_size" value="{{ old('overlay_timer_font_size', $channel->overlay_timer_font_size ?? 24) }}" min="12" max="100" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Color</label>
                    <input type="color" name="overlay_timer_color" value="{{ old('overlay_timer_color', $channel->overlay_timer_color ?? '#FFFFFF') }}" class="w-full h-10 rounded-lg border border-slate-600/40 cursor-pointer">
                </div>
            </div>

            <!-- Style Options -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Style</label>
                    <select name="overlay_timer_style" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        <option value="normal" {{ old('overlay_timer_style', $channel->overlay_timer_style ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="bold" {{ old('overlay_timer_style', $channel->overlay_timer_style ?? '') === 'bold' ? 'selected' : '' }}>Bold</option>
                        <option value="shadow" {{ old('overlay_timer_style', $channel->overlay_timer_style ?? '') === 'shadow' ? 'selected' : '' }}>With Shadow</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-2">Background</label>
                    <select name="overlay_timer_bg" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        <option value="none" {{ old('overlay_timer_bg', $channel->overlay_timer_bg ?? 'none') === 'none' ? 'selected' : '' }}>None</option>
                        <option value="dark" {{ old('overlay_timer_bg', $channel->overlay_timer_bg ?? '') === 'dark' ? 'selected' : '' }}>Dark Box</option>
                        <option value="colored" {{ old('overlay_timer_bg', $channel->overlay_timer_bg ?? '') === 'colored' ? 'selected' : '' }}>Colored Box</option>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-slate-400">Opacity</label>
                        <span class="text-xs font-mono text-purple-400" id="timer-opacity-display">{{ old('overlay_timer_opacity', $channel->overlay_timer_opacity ?? 100) }}%</span>
                    </div>
                    <input type="range" name="overlay_timer_opacity" min="0" max="100" value="{{ old('overlay_timer_opacity', $channel->overlay_timer_opacity ?? 100) }}" class="w-full accent-purple-500" id="timer-opacity-slider">
                </div>
            </div>
        </div>
    </div>

    <!-- SAFE MARGINS SECTION -->
    <div class="p-5 bg-gradient-to-br from-slate-800/40 to-slate-900/20 rounded-xl border border-slate-600/30 shadow-lg">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">🛡️</span>
            <h3 class="font-semibold text-slate-200">Safe Area Margins</h3>
        </div>
        <p class="text-xs text-slate-400 mb-4">Applies to all overlays to ensure content is visible on all displays</p>

        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="text-xs font-semibold text-slate-400">Margin Distance</label>
                <span class="text-sm font-mono text-blue-400 bg-slate-950/50 px-3 py-1 rounded" id="margin-display">{{ old('overlay_safe_margin', $channel->overlay_safe_margin ?? 30) }}px</span>
            </div>
            <input type="range" name="overlay_safe_margin" min="0" max="50" value="{{ old('overlay_safe_margin', $channel->overlay_safe_margin ?? 30) }}" class="w-full accent-blue-500" id="safe-margin-slider">
            <div class="flex justify-between text-xs text-slate-500 mt-2">
                <span>No margin</span>
                <span>Maximum safety</span>
            </div>
        </div>
    </div>

    <!-- VIDEO PREVIEW OVERLAY SECTION -->
    <div class="p-5 bg-gradient-to-br from-emerald-800/20 to-slate-900/20 rounded-xl border border-emerald-600/30 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">🎬</span>
                <div>
                    <h3 class="font-semibold text-slate-200">Preview Overlay on Video</h3>
                    <p class="text-xs text-slate-400">Generate 10-second preview with current overlay settings</p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <!-- Video Selection -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Select Video from Playlist</label>
                <select id="preview-video-select" data-channel-id="{{ $channel->id }}" class="w-full px-3 py-2 text-xs bg-slate-950/50 border border-slate-600/40 rounded-lg text-slate-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <option value="">-- Select a video --</option>
                    @if($channel->playlistItems)
                        @foreach($channel->playlistItems as $item)
                            <option value="{{ $item->id }}">{{ $item->title ?? 'Video ' . $item->id }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Preview Actions -->
            <div class="flex gap-2">
                <button type="button" id="btn-generate-preview" onclick="generateOverlayPreview()" class="flex-1 px-4 py-2 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">
                    🎬 Generate Preview (10s)
                </button>
            </div>

            <!-- Preview Message -->
            <div id="preview-message" class="hidden p-3 rounded-lg text-xs text-center"></div>

            <!-- Preview Download Link -->
            <div id="preview-link-container" class="pt-2">
                <a id="preview-link" href="#" download class="hidden inline-block w-full px-4 py-2 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition text-center">
                    📥 Download Preview (10s MP4)
                </a>
            </div>
        </div>
    </div>

    <!-- FFMPEG FILTER PREVIEW SECTION -->
    <div class="p-5 bg-gradient-to-br from-slate-800/40 to-slate-900/20 rounded-xl border border-slate-600/30 shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">⚙️</span>
                <h3 class="font-semibold text-slate-200">FFmpeg Filter Complex</h3>
            </div>
            <span class="text-xs text-slate-500">(Read-only preview)</span>
        </div>

        <div class="space-y-3">
            <div class="p-4 bg-slate-950/70 rounded-lg border border-slate-600/20 font-mono text-xs text-slate-300 whitespace-pre-wrap break-words max-h-40 overflow-y-auto" id="filter-preview">
-filter_complex "[0:v]scale=w=1920:h=1080:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled];[scaled]pad=1920:1080:(ow-iw)/2:(oh-ih)/2[padded];[padded]drawtext=text='LIVE':fontsize=24:fontcolor=white:x=20:y=20[final]"
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="copyFilterPreview()" class="flex-1 px-4 py-2 text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    📋 Copy Filter Command
                </button>
                <button type="button" onclick="updateFilterPreview()" class="flex-1 px-4 py-2 text-xs font-medium bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition">
                    🔄 Refresh Preview
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Logo section toggle
    const logoEnabled = document.getElementById('logo-enabled');
    const logoSection = document.getElementById('logo-section');
    if (logoEnabled) {
        logoEnabled.addEventListener('change', function() {
            logoSection.classList.toggle('hidden');
        });
    }

    // Text section toggle
    const textEnabled = document.getElementById('text-enabled');
    const textSection = document.getElementById('text-section');
    if (textEnabled) {
        textEnabled.addEventListener('change', function() {
            textSection.classList.toggle('hidden');
        });
    }

    // Timer section toggle
    const timerEnabled = document.getElementById('timer-enabled');
    const timerSection = document.getElementById('timer-section');
    if (timerEnabled) {
        timerEnabled.addEventListener('change', function() {
            timerSection.classList.toggle('hidden');
        });
    }

    // Range slider displays
    const logoOpacitySlider = document.getElementById('logo-opacity-slider');
    if (logoOpacitySlider) {
        logoOpacitySlider.addEventListener('input', function() {
            document.getElementById('logo-opacity-display').textContent = this.value + '%';
        });
    }

    const textBgOpacitySlider = document.getElementById('text-bg-opacity-slider');
    if (textBgOpacitySlider) {
        textBgOpacitySlider.addEventListener('input', function() {
            document.getElementById('text-bg-opacity-display').textContent = this.value + '%';
        });
    }

    const textOpacitySlider = document.getElementById('text-opacity-slider');
    if (textOpacitySlider) {
        textOpacitySlider.addEventListener('input', function() {
            document.getElementById('text-opacity-display').textContent = this.value + '%';
        });
    }

    const timerOpacitySlider = document.getElementById('timer-opacity-slider');
    if (timerOpacitySlider) {
        timerOpacitySlider.addEventListener('input', function() {
            document.getElementById('timer-opacity-display').textContent = this.value + '%';
        });
    }

    const safeMarginSlider = document.getElementById('safe-margin-slider');
    if (safeMarginSlider) {
        safeMarginSlider.addEventListener('input', function() {
            document.getElementById('margin-display').textContent = this.value + 'px';
        });
    }

    // Update filter preview on any overlay change
    document.querySelectorAll('input[name^="overlay_"], select[name^="overlay_"]').forEach(el => {
        el.addEventListener('change', updateFilterPreview);
        el.addEventListener('input', updateFilterPreview);
    });
});

function updateFilterPreview() {
    // Build FFmpeg filter complex command based on current form values
    const filters = [];
    
    // Scale video
    filters.push("[0:v]scale=w=1920:h=1080:force_original_aspect_ratio=decrease:force_divisible_by=2[scaled]");
    filters.push("[scaled]pad=1920:1080:(ow-iw)/2:(oh-ih)/2[padded]");
    
    let lastLabel = '[padded]';
    let filterCount = 0;
    
    // Add text overlay
    if (document.getElementById('text-enabled')?.checked) {
        const content = document.querySelector('select[name="overlay_text_content"]')?.value || 'channel_name';
        const customText = document.querySelector('input[name="overlay_text_custom"]')?.value || 'LIVE';
        const fontSize = document.querySelector('input[name="overlay_text_font_size"]')?.value || 24;
        const x = document.querySelector('input[name="overlay_text_x"]')?.value || 20;
        const y = document.querySelector('input[name="overlay_text_y"]')?.value || 20;
        
        const textValue = content === 'custom' ? customText : (content === 'title' ? '%{metadata\\\\:comment}' : '%{metadata\\\\:title}');
        filters.push(`${lastLabel}drawtext=text='${textValue}':fontsize=${fontSize}:fontcolor=white:x=${x}:y=${y}[txt${filterCount}]`);
        lastLabel = `[txt${filterCount}]`;
        filterCount++;
    }
    
    // Add timer
    if (document.getElementById('timer-enabled')?.checked) {
        const format = document.querySelector('select[name="overlay_timer_format"]')?.value || 'HH:mm';
        const x = document.querySelector('input[name="overlay_timer_x"]')?.value || 20;
        const y = document.querySelector('input[name="overlay_timer_y"]')?.value || 50;
        const fontSize = document.querySelector('input[name="overlay_timer_font_size"]')?.value || 24;
        
        filters.push(`${lastLabel}drawtext=timecode='00:00:00:00':timecode_rate=30:text='${format}':fontsize=${fontSize}:x=${x}:y=${y}[timer${filterCount}]`);
        lastLabel = `[timer${filterCount}]`;
        filterCount++;
    }
    
    // Final output
    if (filterCount > 0) {
        filters.push(`${lastLabel}format=yuv420p[out]`);
    } else {
        filters.push(`${lastLabel}format=yuv420p[out]`);
    }
    
    const filterComplex = `-filter_complex "${filters.join(';')}" -map "[out]" -map 0:a`;
    document.getElementById('filter-preview').textContent = filterComplex;
}

function copyFilterPreview() {
    const filterText = document.getElementById('filter-preview').textContent;
    navigator.clipboard.writeText(filterText).then(() => {
        alert('Filter command copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Preview generation
function generateOverlayPreview() {
    const videoSelect = document.getElementById('preview-video-select');
    if (!videoSelect.value) {
        alert('Please select a video');
        return;
    }
    
    const itemId = videoSelect.value;
    const channelId = document.querySelector('[data-channel-id]').getAttribute('data-channel-id');
    const previewBtn = document.getElementById('btn-generate-preview');
    
    previewBtn.disabled = true;
    previewBtn.innerHTML = '⏳ Generating...';
    
    fetch(`/vod-channels/${channelId}/engine/test-preview?item_id=${itemId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        previewBtn.disabled = false;
        previewBtn.innerHTML = '🎬 Generate Preview (10s)';
        
        if (data.status === 'success') {
            const previewLink = document.getElementById('preview-link');
            previewLink.href = data.preview_url;
            previewLink.textContent = '📥 Download Preview (10s MP4)';
            previewLink.classList.remove('hidden');
            
            const msg = document.getElementById('preview-message');
            msg.innerHTML = '✅ Preview generated with current overlay settings';
            msg.classList.remove('hidden', 'text-red-400');
            msg.classList.add('text-green-400');
        } else {
            const msg = document.getElementById('preview-message');
            msg.innerHTML = '❌ ' + (data.message || 'Failed to generate preview');
            msg.classList.remove('hidden', 'text-green-400');
            msg.classList.add('text-red-400');
        }
    })
    .catch(err => {
        previewBtn.disabled = false;
        previewBtn.innerHTML = '🎬 Generate Preview (10s)';
        console.error('Error:', err);
        alert('Error generating preview');
    });
}
</script>});
</script>
