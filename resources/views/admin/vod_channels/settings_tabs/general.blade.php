<!-- GENERAL TAB -->
<div class="rounded-2xl border border-slate-500/20 bg-slate-900/40 p-6 backdrop-blur-sm">
    <h2 class="text-lg font-semibold mb-6 text-slate-100">📋 Channel General Info</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Channel Name</label>
            <div class="px-4 py-3 rounded-lg bg-slate-950/30 border border-slate-500/20 text-slate-200 font-semibold">
                {{ $channel->name }}
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
            <select name="video_category" class="w-full px-4 py-2 rounded-lg border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200">
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (int)old('video_category', $channel->video_category ?? 0) === (int)$cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_24_7_channel" value="1" {{ old('is_24_7_channel', $channel->is_24_7_channel ?? true) ? 'checked' : '' }} class="w-5 h-5 accent-blue-500">
                <span class="text-slate-300">🔴 24/7 Channel (Loop VOD Playlist)</span>
            </label>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-300 mb-2">Description (optional)</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 rounded-lg border border-slate-500/20 bg-slate-950/30 focus:border-blue-400 text-slate-200 placeholder-slate-500">{{ old('description', $channel->description ?? '') }}</textarea>
        </div>
    </div>
</div>
