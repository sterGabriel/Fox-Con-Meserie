<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class VodRenameService
{
    private array $allowedExtensions = [
        'mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'ts',
    ];

    public function baseDir(): string
    {
        return (string) config('media.vod_dir', '/media');
    }

    public function isReadableDir(string $dir): bool
    {
        return is_dir($dir) && is_readable($dir);
    }

    public function resolvePathWithinBase(?string $requestedPath): string
    {
        $baseDir = $this->baseDir();
        $realBase = realpath($baseDir) ?: $baseDir;

        $requestedPath = is_string($requestedPath) ? trim($requestedPath) : '';
        $candidate = $requestedPath !== '' ? $requestedPath : $baseDir;
        $realCandidate = realpath($candidate) ?: $realBase;

        if (strpos($realCandidate, $realBase) !== 0) {
            return $realBase;
        }

        if (!is_dir($realCandidate)) {
            return $realBase;
        }

        return $realCandidate;
    }

    /**
     * @return array<int, array{name:string, path:string}>
     */
    public function listDirs(string $dir, string $sort = 'name', string $order = 'asc'): array
    {
        $out = [];
        if (!is_dir($dir) || !is_readable($dir)) {
            return $out;
        }

        foreach (File::directories($dir) as $subdir) {
            $name = basename($subdir);
            if ($name === '' || str_starts_with($name, '.')) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'path' => $subdir,
            ];
        }

        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';
        $sort = strtolower($sort);
        if (!in_array($sort, ['name'], true)) {
            $sort = 'name';
        }

        usort($out, function ($a, $b) use ($sort, $order) {
            $cmp = 0;
            if ($sort === 'name') {
                $cmp = strcasecmp($a['name'], $b['name']);
            }
            return $order === 'desc' ? -$cmp : $cmp;
        });

        return $out;
    }

    /**
     * @return array<int, array{filename:string, basename:string, extension:string, size:int, size_formatted:string, mtime:int}>
     */
    public function listFiles(
        string $dir,
        ?string $query = null,
        string $sort = 'name',
        string $order = 'asc'
    ): array
    {
        $out = [];

        if (!is_dir($dir) || !is_readable($dir)) {
            return $out;
        }

        $query = is_string($query) ? trim($query) : '';
        $q = $query !== '' ? mb_strtolower($query) : '';

        foreach (File::files($dir) as $file) {
            $filename = $file->getFilename();
            if ($filename === '' || str_starts_with($filename, '.')) {
                continue;
            }

            if ($q !== '' && !str_contains(mb_strtolower($filename), $q)) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if ($extension !== '' && !in_array($extension, $this->allowedExtensions, true)) {
                continue;
            }

            $basename = pathinfo($filename, PATHINFO_FILENAME);

            $out[] = [
                'filename' => $filename,
                'basename' => $basename,
                'extension' => $extension,
                'size' => $file->getSize() ?: 0,
                'size_formatted' => $this->formatBytes((int) ($file->getSize() ?: 0)),
                'mtime' => $file->getMTime() ?: 0,
            ];
        }

        $order = strtolower($order) === 'desc' ? 'desc' : 'asc';
        $sort = strtolower($sort);
        if (!in_array($sort, ['name', 'size', 'mtime'], true)) {
            $sort = 'name';
        }

        usort($out, function ($a, $b) use ($sort, $order) {
            $cmp = 0;
            if ($sort === 'name') {
                $cmp = strcasecmp($a['filename'], $b['filename']);
            } elseif ($sort === 'size') {
                $cmp = ($a['size'] <=> $b['size']);
            } elseif ($sort === 'mtime') {
                $cmp = ($a['mtime'] <=> $b['mtime']);
            }

            return $order === 'desc' ? -$cmp : $cmp;
        });

        return $out;
    }

    /**
     * @return array<int, array{name:string, path:string}>
     */
    public function getBreadcrumb(string $currentPath): array
    {
        $baseDir = $this->baseDir();
        $realBase = realpath($baseDir) ?: $baseDir;
        $realCurrent = realpath($currentPath) ?: $realBase;

        if (strpos($realCurrent, $realBase) !== 0) {
            $realCurrent = $realBase;
        }

        $breadcrumb = [];
        $breadcrumb[] = [
            'name' => basename($realBase) ?: 'VOD',
            'path' => $realBase,
        ];

        $relative = trim(str_replace($realBase, '', $realCurrent), DIRECTORY_SEPARATOR);
        if ($relative === '') {
            return $breadcrumb;
        }

        $current = $realBase;
        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $part) {
            if ($part === '') continue;
            $current .= DIRECTORY_SEPARATOR . $part;
            $breadcrumb[] = [
                'name' => $part,
                'path' => $current,
            ];
        }

        return $breadcrumb;
    }

    /**
     * @return array{type:'movie'|'tv', query:string, year:?int, season:?int, episode:?int}
     */
    public function parseMediaCandidate(string $basename): array
    {
        $raw = trim($basename);

        $raw = preg_replace('/[._]+/', ' ', $raw) ?? $raw;
        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;

        // Try to detect SxxEyy (or 1x02) anywhere in name.
        $season = null;
        $episode = null;
        if (preg_match('/\bS(\d{1,2})\s*E(\d{1,2})\b/i', $raw, $m)) {
            $season = (int) $m[1];
            $episode = (int) $m[2];
        } elseif (preg_match('/\b(\d{1,2})x(\d{1,2})\b/i', $raw, $m)) {
            $season = (int) $m[1];
            $episode = (int) $m[2];
        }

        $type = ($season !== null && $episode !== null) ? 'tv' : 'movie';

        // Remove bracketed garbage and common release tokens.
        $clean = $raw;
        $clean = preg_replace('/\[[^\]]*\]/', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\([^)]*(1080p|720p|2160p|4k|x264|x265|h264|h265|hevc|webrip|web-dl|bluray|brrip|hdr|dv|dts|aac|ac3|ddp)[^)]*\)/i', ' ', $clean) ?? $clean;

        $clean = preg_replace('/\b(480p|720p|1080p|2160p|4k|hdr|dv|10bit|x264|x265|h\.264|h\.265|hevc|avc|webrip|web\s?-?dl|bluray|brrip|remux|proper|repack|dvdrip|aac|ac3|eac3|ddp|dts|truehd|atmos)\b/i', ' ', $clean) ?? $clean;

        // If TV, cut everything after the episode token.
        if ($type === 'tv') {
            $clean = preg_replace('/\b(S\d{1,2}\s*E\d{1,2}|\d{1,2}x\d{1,2})\b.*/i', '', $clean) ?? $clean;
        }

        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);

        // Extract trailing year if present.
        $year = null;
        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $raw, $m)) {
            $year = (int) $m[1];
        }

        // Remove leftover year in the query (TMDb gets year separately).
        if ($year) {
            $clean = trim(preg_replace('/\b' . preg_quote((string) $year, '/') . '\b/', '', $clean) ?? $clean);
            $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);
        }

        return [
            'type' => $type,
            'query' => $clean,
            'year' => $year,
            'season' => $season,
            'episode' => $episode,
        ];
    }

    public function formatVodFilenameFromTmdb(
        string $type,
        string $tmdbTitle,
        ?int $year,
        string $extension,
        ?int $season,
        ?int $episode,
    ): string
    {
        $title = $this->sanitizeFilenameComponent($tmdbTitle);
        $yearPart = $year ? (' (' . $year . ')') : '';

        if ($type === 'tv') {
            $se = '';
            if ($season !== null && $episode !== null) {
                $se = sprintf(' - S%02dE%02d', $season, $episode);
            }
            return trim($title . $yearPart . $se) . '.' . $extension;
        }

        return trim($title . $yearPart) . '.' . $extension;
    }

    public function sanitizeFilenameComponent(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return $v;
        }

        // Make names safe across common filesystems.
        $v = str_replace(['/', "\\", "\0"], ' ', $v);
        $v = str_replace([':', '*', '?', '"', '<', '>', '|'], ' ', $v);
        $v = trim(preg_replace('/\s+/', ' ', $v) ?? $v);
        $v = trim($v, " .-_\t\n\r\0\x0B");

        return $v;
    }

    public function renameInDirWithinBase(string $dir, string $oldFilename, string $newFilename): array
    {
        $oldFilename = trim($oldFilename);
        $newFilename = trim($newFilename);

        if ($oldFilename === '' || $newFilename === '') {
            return ['ok' => false, 'message' => 'Nume invalid (gol).'];
        }

        if (str_contains($oldFilename, '/') || str_contains($oldFilename, "\\")) {
            return ['ok' => false, 'message' => 'Old filename invalid.'];
        }

        if (str_contains($newFilename, '/') || str_contains($newFilename, "\\")) {
            return ['ok' => false, 'message' => 'New filename invalid.'];
        }

        $baseDir = $this->baseDir();
        $realBase = realpath($baseDir) ?: $baseDir;
        $realDir = realpath($dir) ?: $dir;

        if (strpos($realDir, $realBase) !== 0) {
            return ['ok' => false, 'message' => 'Folder invalid (în afara base dir).'];
        }

        $from = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $oldFilename;
        $to = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFilename;

        if (!is_file($from)) {
            return ['ok' => false, 'message' => 'Fișierul nu există: ' . $oldFilename];
        }

        if (is_file($to)) {
            return ['ok' => false, 'message' => 'Există deja: ' . $newFilename];
        }

        $ok = @rename($from, $to);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Redenumirea a eșuat (verifică permisiunile).'];
        }

        return ['ok' => true, 'message' => 'OK: ' . $oldFilename . ' → ' . $newFilename];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return number_format($value, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }
}
