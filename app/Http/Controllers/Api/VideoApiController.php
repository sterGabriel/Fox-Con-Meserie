<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;

class VideoApiController extends Controller
{
    /**
     * Get videos by category
     * GET /api/videos?category_id=X
     */
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        
        if (!$categoryId) {
            return response()->json([], 200);
        }

        $videos = Video::query()
            ->where('video_category_id', (int)$categoryId)
            ->orderByDesc('id')
            ->limit(1000)
            ->get([
                'id', 'title', 'file_path', 'duration_seconds', 
                'bitrate_kbps', 'resolution', 'size_bytes', 'format'
            ]);

        return response()->json($videos);
    }

    /**
     * Delete a video
     * DELETE /api/videos/{video}
     */
    public function destroy(Video $video)
    {
        // Optional: Check if video has encoding jobs (prevent deletion if jobs exist)
        // if ($video->encodingJobs()->exists()) {
        //     return response()->json(['message' => 'Cannot delete video with active encoding jobs'], 409);
        // }

        $video->delete();
        return response()->json(['message' => 'Video deleted successfully']);
    }
}
