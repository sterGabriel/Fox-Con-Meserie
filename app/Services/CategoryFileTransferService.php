<?php

namespace App\Services;

use App\Models\VideoCategory;

class CategoryFileTransferService
{
    private string $allowedBase = '/media';

    /**
     * @return array{ok:bool, message:string, destination_path?:string}
     */
    public function transfer(string $sourceFilePath, VideoCategory $category, string $mode, string $destSubdir = ''): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['move', 'copy'], true)) {
            return ['ok' => false, 'message' => 'Mode invalid (move/copy).'];
        }

        $destDir = (string) ($category->source_path ?? '');
        $destDir = trim($destDir);
        if ($destDir === '') {
            return ['ok' => false, 'message' => 'Categoria nu are source_path setat.'];
        }

        $realAllowed = realpath($this->allowedBase) ?: $this->allowedBase;
        $realDestBase = realpath($destDir) ?: $destDir;

        if (strpos($realDestBase, $realAllowed) !== 0) {
            return ['ok' => false, 'message' => 'Destinația categoriei nu este permisă (în afara /media).'];
        }

        $destSubdir = trim($destSubdir);
        if ($destSubdir !== '') {
            $destSubdir = str_replace('\\', DIRECTORY_SEPARATOR, $destSubdir);
            $destSubdir = ltrim($destSubdir, DIRECTORY_SEPARATOR);
            if ($destSubdir === '' || $destSubdir === '.' || $destSubdir === '..' || str_contains($destSubdir, '..')) {
                return ['ok' => false, 'message' => 'Subfolder destinație invalid.'];
            }
        }

        $realDest = $realDestBase;
        if ($destSubdir !== '') {
            $realDest = rtrim($realDestBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $destSubdir;
        }

        if (!is_dir($realDest)) {
            $mk = @mkdir($realDest, 0755, true);
            if (!$mk) {
                return ['ok' => false, 'message' => 'Nu pot crea subfolderul destinație: ' . $realDest];
            }
        }

        $realDestChecked = realpath($realDest) ?: $realDest;
        if (strpos($realDestChecked, $realAllowed) !== 0) {
            return ['ok' => false, 'message' => 'Subfolderul destinației nu este permis (în afara /media).'];
        }

        if (!is_dir($realDestChecked) || !is_writable($realDestChecked)) {
            return ['ok' => false, 'message' => 'Folder destinație inaccesibil: ' . $realDestChecked];
        }

        if (!is_file($sourceFilePath) || !is_readable($sourceFilePath)) {
            return ['ok' => false, 'message' => 'Fișier sursă inaccesibil: ' . basename($sourceFilePath)];
        }

        $filename = basename($sourceFilePath);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return ['ok' => false, 'message' => 'Nume fișier invalid.'];
        }

        $destinationPath = rtrim($realDestChecked, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $srcReal = realpath($sourceFilePath) ?: $sourceFilePath;
        $dstReal = realpath($destinationPath) ?: $destinationPath;
        if ($srcReal === $dstReal) {
            return ['ok' => true, 'message' => 'Fișierul este deja în destinație.', 'destination_path' => $destinationPath];
        }

        if (file_exists($destinationPath)) {
            return ['ok' => false, 'message' => 'Există deja un fișier cu acest nume în destinație: ' . $filename];
        }

        if ($mode === 'move') {
            $ok = @rename($sourceFilePath, $destinationPath);
            if (!$ok) {
                return ['ok' => false, 'message' => 'Mutarea a eșuat (permisiuni?).'];
            }
            return ['ok' => true, 'message' => 'Mutat în categorie: ' . $category->name, 'destination_path' => $destinationPath];
        }

        // copy
        $ok = @copy($sourceFilePath, $destinationPath);
        if (!$ok) {
            return ['ok' => false, 'message' => 'Copierea a eșuat (permisiuni/spațiu?).'];
        }

        return ['ok' => true, 'message' => 'Copiat în categorie: ' . $category->name, 'destination_path' => $destinationPath];
    }
}
