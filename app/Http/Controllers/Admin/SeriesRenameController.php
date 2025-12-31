<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use App\Services\CategoryFileTransferService;
use App\Services\MuzicaRenameService;
use App\Services\VideoLibraryImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeriesRenameController extends Controller
{
    public function __construct(
        private readonly MuzicaRenameService $muzicaRenameService,
        private readonly CategoryFileTransferService $categoryFileTransferService,
        private readonly VideoLibraryImportService $videoLibraryImportService,
    )
    {
    }

    public function muzica(Request $request)
    {
        $baseDir = $this->muzicaRenameService->baseDir();
        $currentPath = $this->muzicaRenameService->resolvePathWithinBase($request->query('path'));
        $q = (string) $request->query('q', '');
        $sort = (string) $request->query('sort', 'name');
        $order = (string) $request->query('order', 'asc');
        $dirOrder = (string) $request->query('dir_order', 'asc');

        $categories = VideoCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'source_path']);

        if (!$this->muzicaRenameService->isReadableDir($baseDir)) {
            return view('admin.series.rename-muzica', [
                'basePath' => $baseDir,
                'currentPath' => $baseDir,
                'parentPath' => $baseDir,
                'breadcrumb' => $this->muzicaRenameService->getBreadcrumb($baseDir),
                'q' => $q,
                'sort' => $sort,
                'order' => $order,
                'dir_order' => $dirOrder,
                'dirs' => [],
                'files' => [],
                'categories' => $categories,
                'error' => 'Folderul nu există sau nu este accesibil: ' . $baseDir,
            ]);
        }

        $parent = $baseDir;
        if ($currentPath !== $baseDir) {
            $parent = dirname($currentPath);
            $resolvedParent = $this->muzicaRenameService->resolvePathWithinBase($parent);
            $parent = $resolvedParent;
        }

        return view('admin.series.rename-muzica', [
            'basePath' => $baseDir,
            'currentPath' => $currentPath,
            'parentPath' => $parent,
            'breadcrumb' => $this->muzicaRenameService->getBreadcrumb($currentPath),
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
            'dir_order' => $dirOrder,
            'dirs' => $this->muzicaRenameService->listDirs($currentPath, 'name', $dirOrder),
            'files' => $this->muzicaRenameService->listFiles($currentPath, $q, $sort, $order),
            'categories' => $categories,
            'error' => null,
        ]);
    }

    public function renameMuzica(Request $request)
    {
        $validated = $request->validate([
            'old' => ['required', 'string', 'max:255'],
            'new' => ['required', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:2048'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $baseDir = $this->muzicaRenameService->baseDir();
        $currentPath = $this->muzicaRenameService->resolvePathWithinBase($validated['path'] ?? $baseDir);
        $q = (string) ($validated['q'] ?? '');

        $result = $this->muzicaRenameService->renameInDirWithinBase(
            $currentPath,
            $validated['old'],
            $validated['new'],
        );

        if (!$result['ok']) {
            return redirect()
                ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q])
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q])
            ->with('success', $result['message']);
    }

    public function bulkRenameMuzica(Request $request)
    {
        $validated = $request->validate([
            'path' => ['nullable', 'string', 'max:2048'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:32'],
            'order' => ['nullable', 'string', 'max:8'],
            'dir_order' => ['nullable', 'string', 'max:8'],
            'mode' => ['nullable', 'string', 'max:16'],
            'category_id' => ['nullable', 'integer'],
            'dest_subdir' => ['nullable', 'string', 'max:255'],
            'create_category' => ['nullable', 'string', 'max:8'],
            'new_category_name' => ['nullable', 'string', 'max:255'],
            'new_category_path' => ['nullable', 'string', 'max:2048'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.selected' => ['nullable'],
            'items.*.old' => ['required', 'string', 'max:255'],
            'items.*.new' => ['required', 'string', 'max:255'],
        ]);

        $baseDir = $this->muzicaRenameService->baseDir();
        $currentPath = $this->muzicaRenameService->resolvePathWithinBase($validated['path'] ?? $baseDir);

        $q = (string) ($validated['q'] ?? '');
        $sort = (string) ($validated['sort'] ?? 'name');
        $order = (string) ($validated['order'] ?? 'asc');
        $dirOrder = (string) ($validated['dir_order'] ?? 'asc');

        $mode = strtolower((string) ($validated['mode'] ?? 'leave'));
        // IMPORTANT: user-requested behavior: move/copy only the file into the category folder.
        // Do NOT preserve or use subfolder structure.
        $destSubdir = '';
        if (!in_array($mode, ['leave', 'move', 'copy'], true)) {
            $mode = 'leave';
        }

        $category = null;
        if (in_array($mode, ['move', 'copy'], true)) {
            $categoryId = (int) ($validated['category_id'] ?? 0);
            if ($categoryId > 0) {
                $category = VideoCategory::query()->find($categoryId);
                if (!$category) {
                    return redirect()
                        ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
                        ->with('error', 'Categoria nu există.');
                }

                // Auto-set source_path for the MUZICĂ category (common case)
                if (empty($category->source_path) && Str::slug((string) $category->name) === 'muzica') {
                    $defaultPath = $this->muzicaRenameService->baseDir();
                    $realAllowed = realpath('/media') ?: '/media';
                    $realDefault = realpath($defaultPath) ?: $defaultPath;
                    if (strpos($realDefault, $realAllowed) === 0) {
                        $category->source_path = $realDefault;
                        $category->save();
                    }
                }
            } else {
                $create = strtolower((string) ($validated['create_category'] ?? ''));
                if (in_array($create, ['1', 'on', 'true', 'yes'], true)) {
                    $newName = trim((string) ($validated['new_category_name'] ?? ''));
                    $newPath = trim((string) ($validated['new_category_path'] ?? ''));

                    // Auto defaults from current MUZICA subfolder
                    $baseForDefaults = $baseDir;
                    $relative = '';
                    $bp = rtrim($baseForDefaults, DIRECTORY_SEPARATOR);
                    $cp = (string) $currentPath;
                    if ($bp !== '' && str_starts_with($cp, $bp)) {
                        $relative = ltrim(substr($cp, strlen($bp)), DIRECTORY_SEPARATOR);
                    }
                    $fallbackSubdir = trim($destSubdir) !== '' ? trim($destSubdir) : $relative;

                    if ($newName === '') {
                        $newName = $fallbackSubdir !== '' ? $fallbackSubdir : ('MUZICA-' . date('Ymd-His'));
                    }

                    if ($newPath === '') {
                        $newPath = $fallbackSubdir !== ''
                            ? (rtrim($baseForDefaults, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fallbackSubdir)
                            : $baseForDefaults;
                    }

                    // Security: allow only under /media
                    $realAllowed = realpath('/media') ?: '/media';
                    $realNewPath = realpath($newPath) ?: $newPath;
                    if (strpos($realNewPath, $realAllowed) !== 0) {
                        return redirect()
                            ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
                            ->with('error', 'Folderul categoriei trebuie să fie în /media.');
                    }

                    if (!is_dir($realNewPath)) {
                        $mk = @mkdir($realNewPath, 0755, true);
                        if (!$mk) {
                            return redirect()
                                ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
                                ->with('error', 'Nu pot crea folderul categoriei: ' . $realNewPath);
                        }
                    }

                    $existing = VideoCategory::query()->where('name', $newName)->first();
                    if ($existing) {
                        $category = $existing;
                        if (empty($category->source_path)) {
                            $category->source_path = $realNewPath;
                            $category->save();
                        }
                    } else {
                        $category = VideoCategory::create([
                            'name' => $newName,
                            'slug' => Str::slug($newName),
                            'description' => null,
                            'source_path' => $realNewPath,
                        ]);
                    }
                } else {
                    return redirect()
                        ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
                        ->with('error', 'Alege o categorie pentru mutare/copiere sau bifează “Creează categorie”.');
                }
            }
        }

        $okCount = 0;
        $failCount = 0;
        $selectedCount = 0;
        $transferCount = 0;
        $importCount = 0;
        $errors = [];

        foreach (($validated['items'] ?? []) as $item) {
            $selected = (string) ($item['selected'] ?? '');
            if ($selected === '1' || $selected === 'on' || $selected === 'true') {
                $selectedCount++;
            }
        }

        if ($selectedCount === 0) {
            return redirect()
                ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
                ->with('error', 'Nu ai selectat niciun fișier.');
        }

        foreach (($validated['items'] ?? []) as $item) {
            $selected = (string) ($item['selected'] ?? '');
            if ($selected !== '1' && $selected !== 'on' && $selected !== 'true') {
                continue;
            }

            $result = $this->muzicaRenameService->renameInDirWithinBase(
                $currentPath,
                (string) ($item['old'] ?? ''),
                (string) ($item['new'] ?? ''),
            );

            if (!($result['ok'] ?? false)) {
                $failCount++;
                $errors[] = (string) ($result['message'] ?? 'Rename failed');
                continue;
            }

            $newPath = (string) ($result['new_path'] ?? '');
            if ($newPath !== '' && $category && in_array($mode, ['move', 'copy'], true)) {
                $sourceBeforeTransfer = $newPath;
                $transfer = $this->categoryFileTransferService->transfer($newPath, $category, $mode, $destSubdir);
                if (!($transfer['ok'] ?? false)) {
                    $failCount++;
                    $errors[] = (string) ($transfer['message'] ?? 'Transfer failed');
                    continue;
                }

                $transferCount++;

                $destPath = (string) ($transfer['destination_path'] ?? '');
                if ($destPath !== '') {
                    $import = $this->videoLibraryImportService->importToCategory(
                        $destPath,
                        $category,
                        $mode,
                        $mode === 'move' ? $sourceBeforeTransfer : null,
                    );
                    if (!($import['ok'] ?? false)) {
                        $failCount++;
                        $errors[] = (string) ($import['message'] ?? 'Import failed');
                        continue;
                    }

                    $importCount++;
                }
            }

            $okCount++;
        }

        if ($failCount > 0) {
            $msg = "Renamed: {$okCount} | Selected: {$selectedCount} | Transfer: {$transferCount} | Imported: {$importCount} | Errors: {$failCount}";
            return redirect()
                ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
                ->with('error', $msg)
                ->with('bulk_errors', array_slice($errors, 0, 20));
        }

        return redirect()
            ->route('fox.series.rename-muzica', ['path' => $currentPath, 'q' => $q, 'sort' => $sort, 'order' => $order, 'dir_order' => $dirOrder])
            ->with('success', "Renamed: {$okCount} | Selected: {$selectedCount} | Transfer: {$transferCount} | Imported: {$importCount}");
    }
}
