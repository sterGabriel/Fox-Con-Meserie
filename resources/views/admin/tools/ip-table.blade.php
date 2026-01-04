@extends('layouts.panel')

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;">
        <div>
            <h1 style="margin:0;font-size:24px;font-weight:800;">IP Table</h1>
            <div style="margin-top:6px;font-size:13px;color:#6b7280;">Seen IPs, labeling, and allow/block rules.</div>
        </div>
    </div>

    @if (session('success'))
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">{{ session('error') }}</div>
    @endif

    <div class="fox-table-container" style="padding: 0; overflow: hidden;">
        <div style="overflow:auto;">
            <table class="fox-table" style="width:100%; min-width: 980px;">
                <thead>
                    <tr>
                        <th style="width:160px;">IP</th>
                        <th style="width:220px;">Label</th>
                        <th style="width:140px;">Status</th>
                        <th style="width:90px; text-align:right;">Hits</th>
                        <th style="width:170px;">Last Seen</th>
                        <th>Last Path</th>
                        <th style="width:120px;">User</th>
                        <th style="width:240px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ips as $ip)
                        @php
                            $rule = $rulesByIp[$ip->ip] ?? null;
                            $status = $rule?->action;
                            $badgeBg = $status === 'allow' ? '#065f46' : ($status === 'block' ? '#991b1b' : '#334155');
                            $badgeText = $status === 'allow' ? 'ALLOW' : ($status === 'block' ? 'BLOCK' : 'NONE');
                        @endphp
                        <tr>
                            <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px;">{{ $ip->ip }}</td>

                            <td>
                                <form method="POST" action="{{ route('tools.ip-table.label', $ip) }}" style="display:flex; gap:8px; align-items:flex-start;">
                                    @csrf
                                    <div style="flex:1; display:flex; flex-direction:column; gap:8px;">
                                        <input name="label" value="{{ old('label', $ip->label) }}" placeholder="e.g. Office / ISP" style="width: 100%; padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; font-size: 13px;" />
                                        <input name="notes" value="{{ old('notes', $ip->notes) }}" placeholder="Notes (optional)" style="width: 100%; padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; font-size: 13px;" />
                                    </div>
                                    <button type="submit" style="padding:8px 10px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:700;">Save</button>
                                </form>
                            </td>

                            <td>
                                <span style="display:inline-block;padding:6px 10px;border-radius:999px;background:{{ $badgeBg }};color:#fff;font-weight:800;font-size:11px;letter-spacing:.04em;">{{ $badgeText }}</span>
                            </td>

                            <td style="text-align:right; font-variant-numeric: tabular-nums;">{{ number_format((int) $ip->hit_count) }}</td>

                            <td style="font-variant-numeric: tabular-nums;">
                                {{ $ip->last_seen_at ? $ip->last_seen_at->format('Y-m-d H:i:s') : '-' }}
                            </td>

                            <td style="color:#475569; font-size: 13px;">{{ $ip->last_path ?? '-' }}</td>

                            <td style="color:#475569; font-size: 13px;">{{ $ip->last_user_id ?? '-' }}</td>

                            <td>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <form method="POST" action="{{ route('tools.ip-table.allow', $ip) }}">
                                        @csrf
                                        <button type="submit" style="padding:8px 10px;border-radius:10px;border:0;background:linear-gradient(135deg,#10b981,#34d399);color:#062a1b;font-weight:800;">Allow</button>
                                    </form>
                                    <form method="POST" action="{{ route('tools.ip-table.block', $ip) }}">
                                        @csrf
                                        <button type="submit" style="padding:8px 10px;border-radius:10px;border:0;background:linear-gradient(135deg,#ef4444,#f97316);color:#3b0a0a;font-weight:800;">Block</button>
                                    </form>
                                    <form method="POST" action="{{ route('tools.ip-table.clear', $ip) }}">
                                        @csrf
                                        <button type="submit" style="padding:8px 10px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#111827;font-weight:800;">Clear</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 18px; color: #6b7280;">No IPs tracked yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding: 14px 16px; border-top: 1px solid #eef2f7;">
            {{ $ips->links() }}
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 14px;">
        <div class="fox-table-container" style="padding: 0; overflow: hidden;">
            <div style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap: 12px;">
                <div>
                    <div style="font-weight:900;">Whitelist (ALLOW)</div>
                    <div style="margin-top:2px;font-size:12px;color:#6b7280;">{{ isset($allowRules) ? $allowRules->count() : 0 }} IPs</div>
                </div>
                <form method="POST" action="{{ route('tools.ip-table.rule.allow') }}" style="display:flex; gap:8px; align-items:center;">
                    @csrf
                    <input name="ip" placeholder="Add IP" style="width: 170px; padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; font-size: 13px;" />
                    <button type="submit" style="padding:8px 10px;border-radius:10px;border:0;background:linear-gradient(135deg,#10b981,#34d399);color:#062a1b;font-weight:800;">Allow</button>
                </form>
            </div>
            <div style="overflow:auto;">
                <table class="fox-table" style="width:100%; min-width: 520px;">
                    <thead>
                        <tr>
                            <th style="width:180px;">IP</th>
                            <th>Label</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($allowRules ?? collect()) as $r)
                            <tr>
                                <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px;">{{ $r->ip }}</td>
                                <td style="color:#475569; font-size: 13px;">{{ ($labelsByIp[$r->ip] ?? null) ?: '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('tools.ip-table.rule.clear') }}">
                                        @csrf
                                        <input type="hidden" name="ip" value="{{ $r->ip }}" />
                                        <button type="submit" style="padding:8px 10px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#111827;font-weight:800;">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding: 18px; color: #6b7280;">No allow rules yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fox-table-container" style="padding: 0; overflow: hidden;">
            <div style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap: 12px;">
                <div>
                    <div style="font-weight:900;">Blocked (BLOCK)</div>
                    <div style="margin-top:2px;font-size:12px;color:#6b7280;">{{ isset($blockRules) ? $blockRules->count() : 0 }} IPs</div>
                </div>
                <form method="POST" action="{{ route('tools.ip-table.rule.block') }}" style="display:flex; gap:8px; align-items:center;">
                    @csrf
                    <input name="ip" placeholder="Add IP" style="width: 170px; padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; font-size: 13px;" />
                    <button type="submit" style="padding:8px 10px;border-radius:10px;border:0;background:linear-gradient(135deg,#ef4444,#f97316);color:#3b0a0a;font-weight:800;">Block</button>
                </form>
            </div>
            <div style="overflow:auto;">
                <table class="fox-table" style="width:100%; min-width: 520px;">
                    <thead>
                        <tr>
                            <th style="width:180px;">IP</th>
                            <th>Label</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($blockRules ?? collect()) as $r)
                            <tr>
                                <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px;">{{ $r->ip }}</td>
                                <td style="color:#475569; font-size: 13px;">{{ ($labelsByIp[$r->ip] ?? null) ?: '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('tools.ip-table.rule.clear') }}">
                                        @csrf
                                        <input type="hidden" name="ip" value="{{ $r->ip }}" />
                                        <button type="submit" style="padding:8px 10px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:800;">Unblock</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding: 18px; color: #6b7280;">No blocked IPs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
