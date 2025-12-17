<?php

namespace App\Http\Controllers;

use App\Models\LiveChannel;
use App\Models\VideoCategory;

class CreateVideoController extends Controller
{
    /**
     * Show Create Video page for a specific channel
     * GET /create-video/{channel}
     */
    public function show(LiveChannel $channel)
    {
        $categories = VideoCategory::orderBy('name')->get(['id', 'name']);

        return view('admin.vod_channels.create-video', compact('channel', 'categories'));
    }
}
