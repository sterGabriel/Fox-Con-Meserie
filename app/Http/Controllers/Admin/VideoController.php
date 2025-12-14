<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    /**
     * VIDEO LIBRARY
     *
     * - când intri pe /videos:
     *   1. scanează /home/videos pentru fișiere .mp4
     *   2. pentru fiecare fișier care NU există în DB, îl adaugă automat
     *   3. apoi afișează lista de video-uri din tabelul `videos`
     */
    public function index()
    {
        $videoDir = '/home/videos';

        if (is_dir($videoDir)) {
            // toate fișierele .mp4 din /home/videos
            $files = glob($videoDir . '/*.mp4');

            foreach ($files as $path) {
                // dacă nu există deja în DB cu acest file_path, îl creăm
                if (! Video::where('file_path', $path)->exists()) {
                    $filename = basename($path);

                    // facem un titlu frumos din numele fișierului
                    $title = Str::of(pathinfo($filename, PATHINFO_FILENAME))
                        ->replace(['-', '_'], ' ')
                        ->title();

                    Video::create([
                        'title'             => $title,
                        'file_path'         => $path,
                        'video_category_id' => null, // implicit fără categorie
                    ]);
                }
            }
        }

        // după sync, luăm din DB ce avem
        $videos = Video::with('category')
            ->orderBy('id', 'desc')
            ->get();

        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.videos.index', [
            'videos'     => $videos,
            'categories' => $categories,
        ]);
    }

    /**
     * FORMULAR MANUAL (Create Video)
     */
    public function create()
    {
        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.videos.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * SALVARE MANUALĂ VIDEO (cu categorie)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'file_path'         => ['required', 'string', 'max:1024', 'unique:videos,file_path'],
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
        ]);

        Video::create($data);

        return redirect()
            ->route('videos.index')
            ->with('success', 'Video added.');
    }

    /**
     * EDIT VIDEO (alegi și categoria)
     */
    public function edit(Video $video)
    {
        $categories = VideoCategory::orderBy('name')->get();

        return view('admin.videos.edit', [
            'video'      => $video,
            'categories' => $categories,
        ]);
    }

    /**
     * UPDATE VIDEO
     */
    public function update(Request $request, Video $video)
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'file_path'         => ['required', 'string', 'max:1024', 'unique:videos,file_path,' . $video->id],
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
        ]);

        $video->update($data);

        return redirect()
            ->route('videos.index')
            ->with('success', 'Video updated.');
    }

    /**
     * SETEAZĂ CATEGORIA pentru MAI MULTE VIDEOURI odată
     */
    public function bulkCategory(Request $request)
    {
        $data = $request->validate([
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
            'video_ids'         => ['required', 'array'],
            'video_ids.*'       => ['integer', 'exists:videos,id'],
        ]);

        $categoryId = $data['video_category_id'] ?? null;

        Video::whereIn('id', $data['video_ids'])
            ->update(['video_category_id' => $categoryId]);

        return redirect()
            ->route('videos.index')
            ->with('success', 'Category updated for selected videos.');
    }
}
