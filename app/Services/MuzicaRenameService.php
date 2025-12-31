<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class MuzicaRenameService
{
    private array $allowedExtensions = [
        'mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv', 'ts',
        'mp3', 'flac', 'wav', 'm4a', 'aac', 'ogg',
    ];

    public function baseDir(): string
    {
        return (string) config('media.muzica_dir', '/media/MUZICA');
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

            $mediaInfo = $this->getMediaInfo($file->getPathname());

            $out[] = [
                'filename' => $filename,
                'basename' => $basename,
                'extension' => $extension,
                'size' => $file->getSize() ?: 0,
                'size_formatted' => $this->formatBytes((int) ($file->getSize() ?: 0)),
                'mtime' => $file->getMTime() ?: 0,
                'duration_formatted' => $mediaInfo['duration_formatted'],
                'video_codec' => $mediaInfo['video_codec'],
                'audio_codec' => $mediaInfo['audio_codec'],
                'resolution' => $mediaInfo['resolution'],
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
     * @return array{duration_formatted:string, video_codec:?string, audio_codec:?string, resolution:?string}
     */
    private function getMediaInfo(string $path): array
    {
        $result = [
            'duration_formatted' => '—',
            'video_codec' => null,
            'audio_codec' => null,
            'resolution' => null,
        ];

        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return $result;
        }

        try {
            $cmd = sprintf(
                'ffprobe -v quiet -print_format json -show_format -show_streams %s 2>/dev/null',
                escapeshellarg($path)
            );
            $json = shell_exec($cmd);
            if (!is_string($json) || trim($json) === '') {
                return $result;
            }

            $data = json_decode($json, true);
            if (!is_array($data)) {
                return $result;
            }

            // duration
            $duration = null;
            if (isset($data['format']['duration'])) {
                $duration = (float) $data['format']['duration'];
            }
            if (is_float($duration) && $duration > 0) {
                $result['duration_formatted'] = $this->formatDuration($duration);
            }

            $video = null;
            $audio = null;
            if (isset($data['streams']) && is_array($data['streams'])) {
                foreach ($data['streams'] as $stream) {
                    if (!is_array($stream) || !isset($stream['codec_type'])) continue;
                    if ($stream['codec_type'] === 'video' && $video === null) {
                        $video = $stream;
                    }
                    if ($stream['codec_type'] === 'audio' && $audio === null) {
                        $audio = $stream;
                    }
                }
            }

            if (is_array($video)) {
                $result['video_codec'] = isset($video['codec_name']) ? (string) $video['codec_name'] : null;
                $w = isset($video['width']) ? (int) $video['width'] : 0;
                $h = isset($video['height']) ? (int) $video['height'] : 0;
                if ($w > 0 && $h > 0) {
                    $result['resolution'] = $w . 'x' . $h;
                }
            }

            if (is_array($audio)) {
                $result['audio_codec'] = isset($audio['codec_name']) ? (string) $audio['codec_name'] : null;
            }

            return $result;
        } catch (\Throwable $e) {
            return $result;
        }
    }

    private function formatDuration(float $seconds): string
    {
        $seconds = max(0, (int) round($seconds));
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
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
            'name' => basename($realBase) ?: 'MUZICA',
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
     * Rename a file inside a dir that is within base dir, preserving extension by default.
     *
     * @return array{ok:bool, message:string, old_path?:string, new_path?:string, new_filename?:string}
     */
    public function renameInDirWithinBase(string $dir, string $oldFilename, string $newInput): array
    {
        $baseDir = $this->baseDir();
        $realBase = realpath($baseDir) ?: $baseDir;
        $realDir = realpath($dir) ?: $realBase;

        if (strpos($realDir, $realBase) !== 0) {
            return ['ok' => false, 'message' => 'Folder invalid (în afara /media/MUZICA).'];
        }

        if (!$this->isReadableDir($realDir)) {
            return ['ok' => false, 'message' => 'Folderul nu este accesibil: ' . $realDir];
        }

        $oldFilename = trim($oldFilename);
        $newInput = trim($newInput);

        if ($oldFilename === '' || $newInput === '') {
            return ['ok' => false, 'message' => 'Numele fișierului este invalid.'];
        }

        // Prevent path traversal: we only accept a plain filename (no slashes)
        if (basename($oldFilename) !== $oldFilename) {
            return ['ok' => false, 'message' => 'Fișier invalid (path traversal).'];
        }

        $oldPath = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $oldFilename;

        if (!is_file($oldPath) || !is_readable($oldPath)) {
            return ['ok' => false, 'message' => 'Fișierul nu există sau nu poate fi citit: ' . $oldFilename];
        }

        $oldExt = pathinfo($oldFilename, PATHINFO_EXTENSION);
        $oldBase = pathinfo($oldFilename, PATHINFO_FILENAME);

        // Disallow renaming to dot names
        if ($newInput === '.' || $newInput === '..') {
            return ['ok' => false, 'message' => 'Numele nou este invalid.'];
        }

        // Disallow any directory separators or null bytes
        if (str_contains($newInput, '/') || str_contains($newInput, '\\') || str_contains($newInput, "\0")) {
            return ['ok' => false, 'message' => 'Numele nou nu poate conține / sau \\.' ];
        }

        // If user typed an extension, require it matches the original.
        $newExt = pathinfo($newInput, PATHINFO_EXTENSION);
        $newBase = pathinfo($newInput, PATHINFO_FILENAME);

        if ($newExt !== '' && strcasecmp($newExt, $oldExt) !== 0) {
            return ['ok' => false, 'message' => 'Extensia trebuie să rămână aceeași: .' . $oldExt];
        }

        // If user typed only base name, keep old extension
        if ($newExt === '') {
            $newBase = $newInput;
        }

        $newBase = $this->sanitizeBaseName($newBase);

        if ($newBase === '') {
            return ['ok' => false, 'message' => 'Numele nou (fără extensie) este gol după curățare.'];
        }

        if (mb_strlen($newBase) > 200) {
            return ['ok' => false, 'message' => 'Numele nou este prea lung.'];
        }

        $newFilename = $newBase;
        if ($oldExt !== '') {
            $newFilename .= '.' . $oldExt;
        }

        // No-op (allow move/copy flows even if rename not needed)
        if ($newFilename === $oldFilename || $newBase === $oldBase) {
            return [
                'ok' => true,
                'message' => 'Numele nou este identic cu cel curent.',
                'old_path' => $oldPath,
                'new_path' => $oldPath,
                'new_filename' => $oldFilename,
            ];
        }

        $newPath = rtrim($realDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFilename;

        if (file_exists($newPath)) {
            return ['ok' => false, 'message' => 'Există deja un fișier cu acest nume: ' . $newFilename];
        }

        $ok = @rename($oldPath, $newPath);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Redenumirea a eșuat (verifică permisiunile pe /media/MUZICA).'];
        }

        return [
            'ok' => true,
            'message' => 'Redenumit: ' . $oldFilename . ' → ' . $newFilename,
            'old_path' => $oldPath,
            'new_path' => $newPath,
            'new_filename' => $newFilename,
        ];
    }

    private function sanitizeBaseName(string $name): string
    {
        $name = trim($name);

        // Normalize whitespace
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        // Strip leading/trailing dots/spaces
        $name = trim($name, " \t\n\r\0\x0B.");

        return $name;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return rtrim(rtrim(number_format($size, 2, '.', ''), '0'), '.') . ' ' . $units[$i];
    }
}
