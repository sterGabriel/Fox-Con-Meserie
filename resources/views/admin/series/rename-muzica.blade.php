@extends('layouts.panel')

@section('content')
    <style>
        .wrap { max-width: 1400px; margin: 0 auto; }
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 6px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-h { padding: 12px 14px; border-bottom: 1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .card-t { font-size: 14px; font-weight: 900; color: var(--text-primary); }
        .card-b { padding: 14px; }
        .field label { display:block; font-size: 11px; font-weight: 800; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px; }
        .input { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 13px; background: var(--card-bg); color: var(--text-primary); }
        .btn { padding: 10px 12px; border-radius: 6px; color: #fff; font-weight: 900; font-size: 12px; border: 0; cursor: pointer; }
        .btn-blue { background: var(--fox-blue); }
        .btn-gray { background: #111; }
        .flash { border: 1px solid var(--border-color); background: var(--card-bg); border-radius: 6px; padding: 12px 14px; box-shadow: var(--shadow-sm); margin: 12px 0 16px; }
        .flash.success { border-left: 4px solid var(--fox-green); }
        .flash.error { border-left: 4px solid var(--fox-red); }
        .crumbs a { color: var(--fox-blue); text-decoration: none; font-weight: 800; font-size: 12px; }
        .crumbs span { color: var(--text-muted); font-size: 12px; font-weight: 700; }
        .dirs a { display:inline-flex; align-items:center; gap:8px; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--card-bg); text-decoration: none; color: var(--text-primary); font-weight: 800; font-size: 12px; }
        .dirs a:hover { border-color: var(--fox-blue); }
    </style>

    <script>
        window.__MUZICA_BASE_PATH__ = @json($basePath ?? '/media/MUZICA');
    </script>

    <div class="wrap">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;flex-wrap:wrap;">
            <div>
                <div style="font-size:22px; font-weight:900; color:var(--text-primary);">Rename files (MUZICA)</div>
                <div style="font-size:12px; color:var(--text-muted); margin-top:6px;">Căutare și redenumire în folderul MUZICA (inclusiv subfoldere).</div>
            </div>
        </div>

        @if (session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="flash error">
                <div>{{ session('error') }}</div>
                @if (session('bulk_errors'))
                    <ul style="margin:10px 0 0 0; padding-left: 18px;">
                        @foreach ((array) session('bulk_errors') as $msg)
                            <li style="margin: 4px 0;">{{ $msg }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if (!empty($error))
            <div class="flash error">{{ $error }}</div>
        @endif

        <div class="card" style="margin-bottom: 14px;">
            <div class="card-h">
                <div class="card-t">Folder & Căutare</div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <a class="btn btn-blue" style="text-decoration:none;" href="{{ route('fox.series.rename-muzica', ['path' => $basePath]) }}">MUZICA (root)</a>
                    @if (($currentPath ?? $basePath) !== $basePath)
                        <a class="btn btn-gray" style="text-decoration:none;" href="{{ route('fox.series.rename-muzica', ['path' => $parentPath, 'q' => $q ?? '', 'sort' => $sort ?? 'name', 'order' => $order ?? 'asc', 'dir_order' => $dir_order ?? 'asc']) }}">↑ Sus</a>
                    @endif
                </div>
            </div>
            <div class="card-b">
                <div class="crumbs" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    @foreach(($breadcrumb ?? []) as $idx => $c)
                        @if($idx > 0)
                            <span>›</span>
                        @endif
                        <a href="{{ route('fox.series.rename-muzica', ['path' => $c['path'], 'q' => $q ?? '', 'sort' => $sort ?? 'name', 'order' => $order ?? 'asc', 'dir_order' => $dir_order ?? 'asc']) }}">{{ $c['name'] }}</a>
                    @endforeach
                </div>

                <div style="margin-top:10px; font-size:12px; color: var(--text-muted);">
                    Current path: <span style="font-weight:900; color: var(--text-primary);">{{ $currentPath }}</span>
                </div>

                <form method="GET" action="{{ route('fox.series.rename-muzica') }}" style="margin-top:12px; display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                    <input type="hidden" name="path" value="{{ $currentPath }}">
                    <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                    <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                    <input type="hidden" name="dir_order" value="{{ $dir_order ?? 'asc' }}">
                    <div class="field" style="min-width:280px; flex: 1 1 360px;">
                        <label>Căutare în folder</label>
                        <input class="input" type="text" name="q" value="{{ $q ?? '' }}" placeholder="ex: Sud Est">
                    </div>
                    <div style="font-size:12px; color: var(--text-muted); padding-bottom: 10px;">Enter pentru căutare</div>
                </form>

                <div style="margin-top:14px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <div style="font-size:11px; font-weight:900; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px;">Navigare foldere</div>
                    <form method="GET" action="{{ route('fox.series.rename-muzica') }}" style="display:flex; align-items:center; gap:10px;">
                        <input type="hidden" name="path" value="{{ $currentPath }}">
                        <input type="hidden" name="q" value="{{ $q ?? '' }}">
                        <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                        <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                        <div class="field" style="margin:0;">
                            <label style="margin-bottom:4px;">Sortare foldere</label>
                            <select class="input" name="dir_order" onchange="this.form.submit()" style="padding: 8px 10px; width:auto;">
                                <option value="asc" {{ ($dir_order ?? 'asc') === 'asc' ? 'selected' : '' }}>A → Z</option>
                                <option value="desc" {{ ($dir_order ?? 'asc') === 'desc' ? 'selected' : '' }}>Z → A</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="fox-table-container" style="margin-top:10px;">
                    <table class="fox-table">
                        <thead>
                            <tr>
                                <th>Folder</th>
                                <th style="width:160px;">Acțiune</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($dirs ?? []) as $d)
                                <tr>
                                    <td>📁 {{ $d['name'] }}</td>
                                    <td>
                                        <a href="{{ route('fox.series.rename-muzica', ['path' => $d['path'], 'q' => $q ?? '', 'sort' => $sort ?? 'name', 'order' => $order ?? 'asc', 'dir_order' => $dir_order ?? 'asc']) }}" style="font-weight:900;color:var(--fox-blue);text-decoration:none;">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="color:#666;">Nu există subfoldere aici.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="fox-table-container">
            <div style="padding:14px 16px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                <div style="font-size:12px;font-weight:800;color:#666;letter-spacing:.04em;text-transform:uppercase;">Files</div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <span class="fox-badge blue">{{ count($files ?? []) }} items</span>
                    @if(!empty($q))
                        <span class="fox-badge yellow">filter: {{ $q }}</span>
                    @endif

                    <form method="GET" action="{{ route('fox.series.rename-muzica') }}" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <input type="hidden" name="path" value="{{ $currentPath }}">
                        <input type="hidden" name="q" value="{{ $q ?? '' }}">
                        <input type="hidden" name="dir_order" value="{{ $dir_order ?? 'asc' }}">
                        <select class="input" name="sort" onchange="this.form.submit()" style="padding: 8px 10px; width:auto;">
                            <option value="name" {{ ($sort ?? 'name') === 'name' ? 'selected' : '' }}>Sort: Name</option>
                            <option value="size" {{ ($sort ?? 'name') === 'size' ? 'selected' : '' }}>Sort: Size</option>
                            <option value="mtime" {{ ($sort ?? 'name') === 'mtime' ? 'selected' : '' }}>Sort: Date</option>
                        </select>
                        <select class="input" name="order" onchange="this.form.submit()" style="padding: 8px 10px; width:auto;">
                            <option value="asc" {{ ($order ?? 'asc') === 'asc' ? 'selected' : '' }}>Asc</option>
                            <option value="desc" {{ ($order ?? 'asc') === 'desc' ? 'selected' : '' }}>Desc</option>
                        </select>
                    </form>
                </div>
            </div>

            <form id="bulkForm" method="POST" action="{{ route('fox.series.rename-muzica.bulk') }}">
                @csrf
                <input type="hidden" name="path" value="{{ $currentPath }}">
                <input type="hidden" name="q" value="{{ $q ?? '' }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'name' }}">
                <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
                <input type="hidden" name="dir_order" value="{{ $dir_order ?? 'asc' }}">
                <input type="hidden" name="mode" id="jsMode" value="leave">
                <input type="hidden" name="category_id" id="jsCategoryId" value="">
                <input type="hidden" name="create_category" id="jsCreateCategory" value="0">
                <input type="hidden" name="new_category_name" id="jsNewCategoryName" value="">
                <input type="hidden" name="new_category_path" id="jsNewCategoryPath" value="">
                <input type="hidden" name="dest_subdir" id="jsDestSubdir" value="">

                <div style="padding:12px 16px; border-bottom:1px solid #f0f0f0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <button class="btn btn-blue" type="button" onclick="bulkSelectAll(true)">Select all</button>
                    <button class="btn btn-gray" type="button" onclick="bulkSelectAll(false)">Clear</button>
                    <button class="btn btn-gray" type="button" onclick="bulkSelectFirst(1)">Select 1</button>
                    <button class="btn btn-gray" type="button" onclick="bulkSelectFirst(10)">Select 10</button>
                    <div style="flex:1 1 auto;"></div>
                    <button class="btn btn-blue" type="button" onclick="openMoveModal('selected')">Rename + Move selected</button>
                    <button class="btn btn-gray" type="button" onclick="openMoveModal('all')">Rename + Move all</button>
                </div>

                <table class="fox-table">
                    <thead>
                        <tr>
                            <th style="width:48px;">
                                <input type="checkbox" id="selectAll" onclick="toggleHeaderSelect(this.checked)">
                            </th>
                            <th style="width:40%;">Nume curent</th>
                            <th>Nume nou (fără extensie) — editabil (manual)</th>
                            <th style="width:260px;">VOD info</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($files as $i => $file)
                            <tr>
                                <td>
                                    <input type="checkbox" class="js-select" name="items[{{ $i }}][selected]" value="1">
                                    <input type="hidden" name="items[{{ $i }}][old]" value="{{ $file['filename'] }}">
                                </td>
                                <td style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 520px;">
                                    {{ $file['filename'] }}
                                </td>
                                <td>
                                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                        <input class="input" style="max-width:640px;" type="text" name="items[{{ $i }}][new]" value="{{ $file['basename'] }}">
                                        <span class="fox-badge blue">.{{ $file['extension'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                        <span class="fox-badge yellow">{{ $file['size_formatted'] }}</span>
                                        @if(!empty($file['duration_formatted']) && $file['duration_formatted'] !== '—')
                                            <span class="fox-badge blue">{{ $file['duration_formatted'] }}</span>
                                        @endif
                                        @if(!empty($file['resolution']))
                                            <span class="fox-badge green">{{ $file['resolution'] }}</span>
                                        @endif
                                        @if(!empty($file['video_codec']))
                                            <span class="fox-badge blue">v: {{ $file['video_codec'] }}</span>
                                        @endif
                                        @if(!empty($file['audio_codec']))
                                            <span class="fox-badge blue">a: {{ $file['audio_codec'] }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding:18px 16px; color:#666;">
                                    Nu am găsit fișiere în folder.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <script>
        function getSelectBoxes() {
            return Array.from(document.querySelectorAll('.js-select'));
        }

        function bulkSelectAll(checked) {
            getSelectBoxes().forEach(cb => cb.checked = checked);
            const header = document.getElementById('selectAll');
            if (header) header.checked = checked;
        }

        function bulkSelectFirst(n) {
            bulkSelectAll(false);
            const boxes = getSelectBoxes();
            for (let i = 0; i < Math.min(n, boxes.length); i++) {
                boxes[i].checked = true;
            }
        }

        function toggleHeaderSelect(checked) {
            bulkSelectAll(checked);
        }

        function countSelected() {
            return getSelectBoxes().filter(cb => cb.checked).length;
        }

        function confirmBulk() {
            const cnt = countSelected();
            if (cnt === 0) {
                alert('Nu ai selectat niciun fișier.');
                return false;
            }
            return confirm('Renumești ' + cnt + ' fișiere?');
        }

        function renameAll() {
            bulkSelectAll(true);
            const form = document.getElementById('bulkForm');
            if (!form) return;
            if (!confirmBulk()) return;
            form.submit();
        }
    </script>

    <div id="jsMoveModal" class="fox-modal" style="display:none; position:fixed; inset:0; z-index: 9999;">
        <div class="fox-modal-backdrop" style="position:absolute; inset:0; background: rgba(0,0,0,.55);" onclick="closeMoveModal()"></div>
        <div class="fox-modal-panel" style="position:relative; max-width: 720px; margin: 10vh auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-lg); overflow:hidden;">
            <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <div style="font-weight:900; color:var(--text-primary);">Rename & Mutare</div>
                <button type="button" class="fox-action-btn delete" style="width:auto; padding: 6px 10px;" onclick="closeMoveModal()">X</button>
            </div>

            <div style="padding: 16px;">
                <div style="font-size:12px; color: var(--text-muted); margin-bottom: 10px;">
                    Alege categoria și dacă vrei să <b>ștergi din MUZICA</b> (Move) sau să <b>duplici</b> (Copy). Default: Move.
                </div>

                <div class="field" style="margin-bottom: 12px;">
                    <label>Categorie (destinație)</label>
                    <select class="input" id="jsCategorySelect">
                        <option value="">— Alege categoria —</option>
                        @foreach(($categories ?? []) as $cat)
                            <option value="{{ $cat->id }}" data-path="{{ $cat->source_path ?? '' }}">
                                {{ $cat->name }}
                                @if(empty($cat->source_path))
                                    (NO PATH)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div style="margin-top:6px; font-size:12px; color: var(--text-muted);">
                        Folder categorie: <span id="jsCatPath" style="font-weight:900; color:var(--text-primary);">—</span>
                    </div>
                </div>

                <div class="field" style="margin-bottom: 12px; border-top:1px solid var(--border-light); padding-top: 12px;">
                    <label>Nu ai categoria? Creează acum (opțional)</label>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-primary); font-weight:800;">
                        <input type="checkbox" id="jsCreateCategoryToggle">
                        Creează categorie nouă
                    </label>
                    <div id="jsCreateCategoryFields" style="display:none; margin-top:10px;">
                        <div class="field" style="margin-bottom: 10px;">
                            <label style="margin-bottom:6px;">Nume categorie</label>
                            <input class="input" id="jsNewCategoryNameInput" type="text" placeholder="ex: MUZICĂ">
                        </div>
                        <div class="field">
                            <label style="margin-bottom:6px;">Folder categorie (în /media)</label>
                            <input class="input" id="jsNewCategoryPathInput" type="text" placeholder="ex: /media/MUZICA">
                            <div style="margin-top:6px; font-size:12px; color: var(--text-muted);">
                                Folderul se poate crea automat dacă nu există.
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin: 10px 0 12px 0; font-size:12px; color: var(--text-muted);">
                    Mutarea/copierea se face direct în folderul categoriei (fără subfoldere).
                </div>

                <div class="field" style="margin-bottom: 8px;">
                    <label>Dublăm sau ștergem?</label>
                    <div style="display:flex; gap:12px; flex-wrap:wrap; font-size:13px; color:var(--text-primary);">
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="radio" name="_mode" value="move" checked>
                            Move (șterge din MUZICA)
                        </label>
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="radio" name="_mode" value="copy">
                            Copy (păstrează și în MUZICA)
                        </label>
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="radio" name="_mode" value="leave">
                            Doar rename aici (fără mutare)
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 14px;">
                    <button type="button" class="btn btn-gray" onclick="closeMoveModal()">Cancel</button>
                    <button type="button" class="btn btn-blue" onclick="submitMoveModal()">Aplică</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let bulkIntent = 'selected';

        function openMoveModal(intent) {
            bulkIntent = intent === 'all' ? 'all' : 'selected';
            if (bulkIntent === 'all') {
                bulkSelectAll(true);
            }

            const cnt = countSelected();
            if (cnt === 0) {
                alert('Nu ai selectat niciun fișier.');
                return;
            }

            const modal = document.getElementById('jsMoveModal');
            if (modal) modal.style.display = 'block';

            const sel = document.getElementById('jsCategorySelect');
            if (sel) {
                updateCatPath();
                sel.addEventListener('change', updateCatPath, { once: false });
            }
        }

        function closeMoveModal() {
            const modal = document.getElementById('jsMoveModal');
            if (modal) modal.style.display = 'none';
        }

        function updateCatPath() {
            const sel = document.getElementById('jsCategorySelect');
            const out = document.getElementById('jsCatPath');
            if (!sel || !out) return;
            const opt = sel.options[sel.selectedIndex];
            const p = opt ? (opt.getAttribute('data-path') || '') : '';
            out.textContent = p && p.trim() !== '' ? p : '—';
        }

        function submitMoveModal() {
            const form = document.getElementById('bulkForm');
            if (!form) return;

            const cnt = countSelected();
            if (cnt === 0) {
                alert('Nu ai selectat niciun fișier.');
                return;
            }

            const modeRadio = document.querySelector('input[name="_mode"]:checked');
            const mode = modeRadio ? modeRadio.value : 'leave';

            const sel = document.getElementById('jsCategorySelect');
            const categoryId = sel ? sel.value : '';
            const selectedOpt = sel ? sel.options[sel.selectedIndex] : null;
            const selectedPath = selectedOpt ? (selectedOpt.getAttribute('data-path') || '') : '';
            const selectedText = selectedOpt ? (selectedOpt.textContent || '') : '';

            const createToggle = document.getElementById('jsCreateCategoryToggle');
            const createOn = createToggle ? createToggle.checked : false;
            const newNameEl = document.getElementById('jsNewCategoryNameInput');
            const newPathEl = document.getElementById('jsNewCategoryPathInput');
            const newName = newNameEl ? (newNameEl.value || '').trim() : '';
            const newPath = newPathEl ? (newPathEl.value || '').trim() : '';

            const subdir = '';

            if (mode === 'move' || mode === 'copy') {
                if ((!categoryId || categoryId === '') && !createOn) {
                    alert('Alege categoria pentru mutare/copiere sau bifează “Creează categorie nouă”.');
                    return;
                }

                // If category exists but has no folder path, moving/copying cannot work.
                // Exception: MUZICA can be auto-fixed server-side.
                if (!createOn && categoryId && selectedPath.trim() === '') {
                    const isMuzica = selectedText.toLowerCase().includes('muz');
                    if (!isMuzica) {
                        alert('Categoria aleasă nu are folder setat (NO PATH). Setează source_path la categorie (Category Scan / Edit Category) sau folosește “Creează categorie nouă”.');
                        return;
                    }
                }

                if (createOn && (!newName || !newPath)) {
                    alert('Completează numele și folderul pentru categoria nouă.');
                    return;
                }
            }

            document.getElementById('jsMode').value = mode;
            document.getElementById('jsCategoryId').value = categoryId || '';
            const hiddenSubdir = document.getElementById('jsDestSubdir');
            if (hiddenSubdir) hiddenSubdir.value = '';

            const hCreate = document.getElementById('jsCreateCategory');
            const hName = document.getElementById('jsNewCategoryName');
            const hPath = document.getElementById('jsNewCategoryPath');
            if (hCreate) hCreate.value = createOn ? '1' : '0';
            if (hName) hName.value = createOn ? newName : '';
            if (hPath) hPath.value = createOn ? newPath : '';

            const msg = mode === 'leave'
                ? ('Renumești ' + cnt + ' fișiere aici?')
                : (mode.toUpperCase() + ': Renumești și transferi ' + cnt + ' fișiere?');

            if (!confirm(msg)) {
                return;
            }

            closeMoveModal();
            form.submit();
        }

        // Create-category toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('jsCreateCategoryToggle');
            const fields = document.getElementById('jsCreateCategoryFields');
            const sel = document.getElementById('jsCategorySelect');
            const newNameEl = document.getElementById('jsNewCategoryNameInput');
            const newPathEl = document.getElementById('jsNewCategoryPathInput');
            if (!toggle || !fields) return;

            const applyState = () => {
                fields.style.display = toggle.checked ? 'block' : 'none';
                if (sel) sel.disabled = toggle.checked;

                // When creating category, auto-fill defaults and avoid double nesting.
                if (toggle.checked) {
                    const sub = '';
                    const base = (window.__MUZICA_BASE_PATH__ || '/media/MUZICA').toString();
                    if (newNameEl && (newNameEl.value || '').trim() === '') {
                        newNameEl.value = 'MUZICA';
                    }
                    if (newPathEl && (newPathEl.value || '').trim() === '') {
                        newPathEl.value = base;
                    }
                } else {
                }
            };

            toggle.addEventListener('change', applyState);
            applyState();
        });
    </script>
@endsection
