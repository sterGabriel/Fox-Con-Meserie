<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use App\Models\LiveChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoCategoryController extends Controller
{
    public function index()
    {
        $categories = VideoCategory::query()
            ->withCount('videos')
            ->orderBy('name')
            ->get();

        return view('admin.video_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:video_categories,name'],
            'description' => ['nullable', 'string'],
            'source_path' => ['nullable', 'string', 'max:2048'],
        ]);

        if (isset($data['source_path'])) {
            $data['source_path'] = trim((string) $data['source_path']);
            if ($data['source_path'] !== '' && !str_starts_with($data['source_path'], '/media')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['source_path' => 'source_path trebuie să fie în /media']);
            }
            if ($data['source_path'] === '') {
                $data['source_path'] = null;
            }
        }

        $data['slug'] = Str::slug($data['name']);

        VideoCategory::create($data);

        return redirect()
            ->route('video-categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(VideoCategory $category)
    {
        return view('admin.video_categories.edit', compact('category'));
    }

    public function update(Request $request, VideoCategory $category)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:video_categories,name,' . $category->id],
            'description' => ['nullable', 'string'],
            'source_path' => ['nullable', 'string', 'max:2048'],
        ]);

        if (isset($data['source_path'])) {
            $data['source_path'] = trim((string) $data['source_path']);
            if ($data['source_path'] !== '' && !str_starts_with($data['source_path'], '/media')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['source_path' => 'source_path trebuie să fie în /media']);
            }
            if ($data['source_path'] === '') {
                $data['source_path'] = null;
            }
        }

        $data['slug'] = Str::slug($data['name']);

        $category->update($data);

        return redirect()
            ->route('video-categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(VideoCategory $category)
    {
        // scoatem categoria de pe toate canalele care o foloseau
        LiveChannel::where('video_category', $category->slug)
            ->update(['video_category' => null]);

        $category->delete();

        return redirect()
            ->route('video-categories.index')
            ->with('success', 'Category deleted.');
    }

    /**
     * Redirect to file browser for importing
     */
    public function browse(VideoCategory $category)
    {
        return redirect()->route('admin.video_categories.browse', $category);
    }
}
