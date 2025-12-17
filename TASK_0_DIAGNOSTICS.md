════════════════════════════════════════════════════════════════════════════════
🔴 TASK 0 — DOVEZI OBLIGATORII (AZI)
════════════════════════════════════════════════════════════════════════════════

LIVRABILE: 4 screenshot-uri + 2 terminal outputs

════════════════════════════════════════════════════════════════════════════════
📋 SCREENSHOT 1: Network — POST requests
════════════════════════════════════════════════════════════════════════════════

Deschide: http://localhost/admin/vod-channels/4/settings

Apasă F12 → Network tab → Preserve log (checkbox)

Click pe butoanele din ordine:
1. ⚙️ ENCODE NOW
2. 🎥 TEST OVERLAY (10s)
3. ▶ START CHANNEL
4. 🔄 START 24/7 LOOP

Pentru FIECARE request:
- Click pe request din lista
- Tab: Headers
- Copy screenshot care arată:
  * URL-ul (/vod-channels/.../engine/...)
  * Status (cu culoare - verde 200/302, roșu 419/500)
  * Request Headers (X-CSRF-TOKEN, Content-Type)
  * Response status line

📌 LIVRABIL: NETWORK_REQUESTS.png

════════════════════════════════════════════════════════════════════════════════
📋 SCREENSHOT 2: Console tab
════════════════════════════════════════════════════════════════════════════════

Apasă F12 → Console tab

Repet click-urile de mai sus.

Caută RED errors (nu warnings).

📌 LIVRABIL: CONSOLE_ERRORS.png (dacă sunt erori roșii, include-le)

════════════════════════════════════════════════════════════════════════════════
📋 OUTPUT 1: Routes list
════════════════════════════════════════════════════════════════════════════════

Deschide Terminal. Ruleaza:

cd /var/www/iptv-panel
php artisan route:list | egrep "vod-channels|video-categories" | head -200

Copy OUTPUT (complet).

📌 LIVRABIL: ROUTES_OUTPUT.txt

════════════════════════════════════════════════════════════════════════════════
📋 OUTPUT 2: Logs
════════════════════════════════════════════════════════════════════════════════

Ruleaza:

tail -n 120 storage/logs/laravel.log

Copy OUTPUT complet (ultimele 120 linii).

Caută: TypeError, Exception, CSRF mismatch, type mismatch, etc.

📌 LIVRABIL: LARAVEL_LOG.txt

════════════════════════════════════════════════════════════════════════════════

FĂRĂ ACESTE 4 FIȘIERE, NU POT DIAGNOSTICA.

════════════════════════════════════════════════════════════════════════════════
🔴 TASK A — Fix 419 CSRF (obligatoriu înainte de orice)
════════════════════════════════════════════════════════════════════════════════

SCOP: Orice POST din JavaScript trebuie să aibă CSRF token.

STEPS:

1. Deschide resources/views/layouts/panel.blade.php

2. Caută în <head>:

   <meta name="csrf-token" content="{{ csrf_token() }}">

   ✅ Dacă e acolo → skip să 3

   ❌ Dacă NU e → adaugă în <head> după <meta name="viewport">

3. Deschide resources/views/admin/vod_channels/settings_tabs/engine.blade.php

4. Caută toți fetch() care fac POST (nu GET)

5. Pentru FIECARE POST fetch(), verifică headers:

   ❌ GREȘIT:
   fetch(url, { method: 'POST' })

   ✅ CORECT:
   fetch(url, {
     method: 'POST',
     headers: {
       'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
       'X-Requested-With': 'XMLHttpRequest',
       'Content-Type': 'application/json'
     },
     credentials: 'same-origin',
     body: JSON.stringify({})
   })

6. Fă la fel pentru:
   - resources/views/admin/vod_channels/settings_tabs/overlay.blade.php
   - resources/views/admin/vod_channels/settings_tabs/outputs.blade.php
   - resources/views/admin/video_categories/browse.blade.php

7. Clear cache:

   php artisan view:clear
   php artisan cache:clear
   php artisan config:clear

8. Retest: F12 → Network → apasă ⚙️ ENCODE NOW

   ❌ Dacă apare 419 → CSRF token nu e transmis corect
   ✅ Dacă apare 200/302 → fix OK

LIVRABIL: screenshot Network unde POST /vod-channels/4/engine/start-encoding arată Status 200 sau 302.

════════════════════════════════════════════════════════════════════════════════
🔴 TASK B — Fix undefined functions (startEncodingAll / showVideoInfo)
════════════════════════════════════════════════════════════════════════════════

SCOP: Zero "is not defined" errors în Console.

STEPS:

1. Deschide F12 → Console

2. Click pe butoanele de settings

3. Caută RED errors care conțin "is not defined"

4. Dacă vezi:

   ❌ startEncodingAll is not defined
   ❌ showVideoInfo is not defined
   ❌ someFunction is not defined

   Atunci:

   a) Cauta funcția în resources/views/admin/vod_channels/settings_tabs/*.blade.php

   b) Dacă e în alt tab → copiaz funcția în engine.blade.php sau settings.blade.php

   c) Sau adaugă <script src="/js/vod-settings.js"></script> în settings.blade.php

5. Rulează hard refresh: Ctrl+F5

6. Retest Console

LIVRABIL: screenshot Console cu 0 red errors.

════════════════════════════════════════════════════════════════════════════════
🔴 TASK C — Fix queue-encoding 500 error (type mismatch)
════════════════════════════════════════════════════════════════════════════════

SCOP: POST .../queue-encoding să dea 200, nu 500.

SYMPTOM din laravel.log:

  "LiveChannel expected, EncodeProfile given"
  TypeError in EncodingJobController.php:81

STEPS:

1. Deschide:

   /var/www/iptv-panel/app/Http/Controllers/Admin/EncodingJobController.php

2. Salt la line ~81 (unde scrie buildCommand)

3. Dacă e ceva de genul:

   ❌ $cmd = EncodingProfileBuilder::buildCommand($encodeProfile)

   Fix:

   ✅ $cmd = EncodingProfileBuilder::buildCommand($liveChannel)

   (sau $channel, depinde cum se cheamă variabila)

4. Salveaza fișierul

5. Clear cache:

   php artisan view:clear && php artisan cache:clear

6. Retest:

   Mergi la /admin/vod-channels/4/settings

   Click pe ⚙️ ENCODE NOW

   F12 → Network → caută POST .../queue-encoding

   ✅ Status trebuie să fie 200 sau 302

   ❌ Dacă e 500, citește laravel.log cu:

   tail -n 50 storage/logs/laravel.log | grep -i "error\|exception\|type"

LIVRABIL: screenshot Network cu POST queue-encoding = 200.

════════════════════════════════════════════════════════════════════════════════
🔴 TASK D — Fix test-preview 500 error (path not found)
════════════════════════════════════════════════════════════════════════════════

SCOP: POST .../engine/test-preview să dea 200, nu 500.

SYMPTOM din laravel.log:

  "file_get_contents(...): Failed to open stream: No such file or directory"
  "Path not found: /media/videos/..."

STEPS:

1. Citește laravel.log:

   tail -n 100 storage/logs/laravel.log | grep -i "preview\|not found\|failed"

2. Cauta path-ul exact care nu e găsit (va fi ceva de genul):

   /media/videos/muzica/song.mp3

3. Verifică pe server dacă fișierul există:

   ls -lah "/media/videos/muzica/song.mp3"

   ❌ Dacă nu găsește → file-ul nu e pe disk

4. Verifică DB:

   cd /var/www/iptv-panel
   php artisan tinker
   >>> App\Models\Video::where('file_path', 'like', '%song%')->first()
   >>> # va arăta path-ul din DB

5. Probleme posibile:

   a) Path-ul e folder (/media/videos/muzica) în loc de fișier

      Fix: Import trebuie să salveze FIȘIER complet, nu doar folder

   b) Path-ul e relativ sau greșit (ex: ../../../...)

      Fix: Trebuie absolute path (/media/videos/...)

   c) Fișierul a fost șters

      Fix: Reimport fișierele

6. După fix, clear cache și retest:

   php artisan cache:clear
   
   Mergi la /admin/vod-channels/4/settings
   
   Click pe 🎥 TEST OVERLAY (10s)
   
   F12 → Network → caută POST .../test-preview
   
   ✅ Status 200

LIVRABIL: screenshot Network cu POST test-preview = 200.

════════════════════════════════════════════════════════════════════════════════
📋 FINAL CHECKLIST
════════════════════════════════════════════════════════════════════════════════

✅ TASK A done:  POST requests dau 200/302 (nu 419)
✅ TASK B done:  Console 0 red errors
✅ TASK C done:  queue-encoding POST = 200
✅ TASK D done:  test-preview POST = 200

Trimite DOAR screenshot-urile + outputs.txt completate.

Fără povești, fără statuses.

════════════════════════════════════════════════════════════════════════════════
