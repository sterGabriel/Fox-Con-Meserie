<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileBrowserController extends Controller
{
    // Schimbă dacă videourile sunt în alt folder:
    private string $basePath = '/home/videos';

    public function index(Request $request)
    {
        $requested = $request->query('path', $this->basePath);

        // Path real
        $realBase = realpath($this->basePath) ?: $this->basePath;
        $realPath = realpath($requested) ?: $realBase;

        // Securitate: NU permitem acces în afara folderului de bază
        if (strpos($realPath, $realBase) !== 0) {
            $realPath = $realBase;
        }

        $dirs  = [];
        $files = [];

        if (is_dir($realPath)) {
            foreach (scandir($realPath) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $full = $realPath . DIRECTORY_SEPARATOR . $entry;

                if (is_dir($full)) {
                    $dirs[] = [
                        'name' => $entry,
                        'path' => $full,
                    ];
                } else {
                    $files[] = [
                        'name' => $entry,
                        'path' => $full,
                    ];
                }
            }
        }

        $parent = $realBase;
        if ($realPath !== $realBase) {
            $parent = dirname($realPath);
        }

        return view('admin.file_browser.index', [
            'currentPath' => $realPath,
            'basePath'    => $realBase,
            'parentPath'  => $parent,
            'dirs'        => $dirs,
            'files'       => $files,
        ]);
    }
}
