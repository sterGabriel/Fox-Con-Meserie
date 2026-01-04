@extends('layouts.panel')

@section('content')
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:900; color:#0f172a;">Profile</h1>
            <div style="margin-top:6px; font-size:13px; color:#64748b;">Edit your account details, change password, and view login history.</div>
        </div>
    </div>

    @if (session('status') === 'profile-updated')
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">Profile updated.</div>
    @endif
    @if (session('status') === 'password-updated')
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">Password updated.</div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
            <div style="font-weight:900; margin-bottom:6px;">Please fix the errors:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="font-size:13px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:14px;">
        <div class="fox-table-container" style="padding: 16px;">
            <div style="font-weight:900; color:#0f172a; margin-bottom:10px;">Account</div>

            <div style="display:grid; grid-template-columns: 160px 1fr; gap:10px; font-size:13px; color:#475569;">
                <div style="font-weight:800; color:#0f172a;">User ID</div>
                <div>{{ $user->id }}</div>

                <div style="font-weight:800; color:#0f172a;">Username</div>
                <div>{{ $user->name }}</div>

                <div style="font-weight:800; color:#0f172a;">Email</div>
                <div>{{ $user->email }}</div>

                <div style="font-weight:800; color:#0f172a;">Created</div>
                <div>{{ $user->created_at?->format('Y-m-d H:i:s') ?? '-' }}</div>

                <div style="font-weight:800; color:#0f172a;">Updated</div>
                <div>{{ $user->updated_at?->format('Y-m-d H:i:s') ?? '-' }}</div>

                <div style="font-weight:800; color:#0f172a;">Last Login</div>
                <div>
                    <div style="font-variant-numeric: tabular-nums;">{{ $user->last_login_at?->format('Y-m-d H:i:s') ?? '-' }}</div>
                    <div style="margin-top:4px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size:12px; color:#64748b;">{{ $user->last_login_ip ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="fox-table-container" style="padding: 16px;">
            <div style="font-weight:900; color:#0f172a; margin-bottom:10px;">Edit Profile</div>

            <form method="POST" action="{{ route('profile.update') }}" style="display:flex; flex-direction:column; gap:10px;">
                @csrf
                @method('PATCH')

                <div>
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Username</div>
                    <input name="name" value="{{ old('name', $user->name) }}" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                </div>

                <div>
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Email</div>
                    <input name="email" value="{{ old('email', $user->email) }}" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" style="padding:10px 12px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:900;">Save</button>
                </div>
            </form>
        </div>

        <div class="fox-table-container" style="padding: 16px;">
            <div style="font-weight:900; color:#0f172a; margin-bottom:10px;">Change Password</div>

            <form method="POST" action="{{ route('password.update') }}" style="display:flex; flex-direction:column; gap:10px;">
                @csrf
                @method('PUT')

                <div>
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Current Password</div>
                    <input type="password" name="current_password" autocomplete="current-password" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                </div>

                <div>
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">New Password</div>
                    <input type="password" name="password" autocomplete="new-password" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                </div>

                <div>
                    <div style="font-size:12px; font-weight:900; color:#64748b; margin-bottom:6px;">Confirm New Password</div>
                    <input type="password" name="password_confirmation" autocomplete="new-password" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; font-size:13px;" />
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button type="submit" style="padding:10px 12px;border-radius:10px;border:0;background:#111827;color:#fff;font-weight:900;">Update Password</button>
                </div>
            </form>
        </div>

        <div class="fox-table-container" style="padding: 0; overflow:hidden; grid-column: 1 / -1;">
            <div style="padding: 14px 16px; border-bottom: 1px solid #eef2f7; display:flex; align-items:flex-start; justify-content:space-between; gap: 12px;">
                <div>
                    <div style="font-weight:900;">Login History</div>
                    <div style="margin-top:2px;font-size:12px;color:#6b7280;">Latest {{ isset($loginEvents) ? $loginEvents->count() : 0 }} events (IP + device).</div>
                </div>
            </div>

            <div style="overflow:auto;">
                <table class="fox-table" style="width:100%; min-width: 980px;">
                    <thead>
                        <tr>
                            <th style="width:220px;">Logged In At</th>
                            <th style="width:180px;">IP</th>
                            <th style="width:140px;">Guard</th>
                            <th style="width:120px;">Remember</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($loginEvents ?? collect()) as $ev)
                            <tr>
                                <td style="font-variant-numeric: tabular-nums;">{{ $ev->logged_in_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px;">{{ $ev->ip_address ?? '-' }}</td>
                                <td style="color:#475569; font-size: 13px;">{{ $ev->guard ?: '-' }}</td>
                                <td style="color:#475569; font-size: 13px;">{{ $ev->remember ? 'yes' : 'no' }}</td>
                                <td style="color:#475569; font-size: 13px;">{{ $ev->user_agent ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 18px; color: #6b7280;">No login events tracked yet. (They start logging after this feature is enabled.)</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
