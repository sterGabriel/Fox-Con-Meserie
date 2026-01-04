<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $tmdbKey = (string) AppSetting::getValue('tmdb_api_key', '');
        $streamingDomain = (string) AppSetting::getValue('streaming_domain', '');
        $dnsServers = (string) AppSetting::getValue('dns_servers', '');

        $systemDns = '';
        try {
            $systemDns = (string) file_get_contents('/etc/resolv.conf');
        } catch (\Throwable $e) {
            $systemDns = '';
        }

        return view('admin.settings.index', [
            'tmdbKey' => $tmdbKey,
            'streamingDomain' => $streamingDomain,
            'dnsServers' => $dnsServers,
            'defaultStreamingDomain' => rtrim((string) config('app.streaming_domain', ''), '/'),
            'systemDns' => $systemDns,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tmdb_api_key' => ['nullable', 'string', 'max:255'],
            'streaming_domain' => ['nullable', 'string', 'max:255'],
            'dns_servers' => ['nullable', 'string', 'max:5000'],
        ]);

        $tmdbKey = trim((string) ($data['tmdb_api_key'] ?? ''));
        $streamingDomain = rtrim(trim((string) ($data['streaming_domain'] ?? '')), '/');
        $dnsServers = trim((string) ($data['dns_servers'] ?? ''));

        AppSetting::setValue('tmdb_api_key', $tmdbKey === '' ? null : $tmdbKey);
        AppSetting::setValue('streaming_domain', $streamingDomain === '' ? null : $streamingDomain);
        AppSetting::setValue('dns_servers', $dnsServers === '' ? null : $dnsServers);

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}
