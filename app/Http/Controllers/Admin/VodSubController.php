<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiveChannel;
use App\Models\VideoCategory;

class VodSubController extends Controller
{
    public function index()
    {
        $channels = LiveChannel::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = VideoCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.series.rename-vod-sub', [
            'channels' => $channels,
            'categories' => $categories,
        ]);
    }
}
