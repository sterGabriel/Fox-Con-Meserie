<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class TmdbSettingsController extends Controller
{
    public function edit()
    {
        $key = (string) AppSetting::getValue('tmdb_api_key', '');

        return view('admin.settings.tmdb', [
            'tmdbKey' => $key,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tmdb_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        $key = trim((string)($data['tmdb_api_key'] ?? ''));
        AppSetting::setValue('tmdb_api_key', $key === '' ? null : $key);

        return redirect('/settings/tmdb')
            ->with('success', 'Cheia TMDB a fost salvată.');
    }
}
