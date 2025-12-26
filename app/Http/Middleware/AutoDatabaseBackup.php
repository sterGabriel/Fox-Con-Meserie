<?php

namespace App\Http\Middleware;

use App\Services\DatabaseBackupService;
use Closure;
use Illuminate\Http\Request;

class AutoDatabaseBackup
{
    public function handle(Request $request, Closure $next)
    {
        // Only before mutating requests.
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $reason = 'http_' . strtolower($request->method()) . '_' . trim(str_replace('/', '_', $request->path()), '_');
            app(DatabaseBackupService::class)->backupIfEnabledAndDue($reason);
        }

        return $next($request);
    }
}
