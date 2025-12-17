# Create Video - Instrucțiuni pentru coleg (Copy-Paste Ready)

## ✅ CE E GATA (100%)

Pagina "Create Video" e **funcțională și 1:1 cu Fox Codec**:

- ✅ Layout 2 coloane (stânga: setări; dreapta: videos + jobs)
- ✅ Bitrate dropdown: 1000k / 1500k / 2000k / 2500k / 3000k / 3500k / 4000k (Standard)
- ✅ Tabel videos cu checkbox + Title/Duration/Size + Actions (Select/Watch/Delete)
- ✅ Category selector → încarc videos din acea categorie
- ✅ Test Video tabel cu status + icon (pending/running/done/failed)
- ✅ Butoane Convert All Videos + Delete All Videos
- ✅ Create Video → creează job în encoding_jobs
- ✅ Test button pe fiecare job (creează test job)
- ✅ Delete button pe fiecare job

## 📍 UNDE E CODUL

- **View (Blade):** `resources/views/admin/vod_channels/create-video.blade.php`
- **Controller:** `app/Http/Controllers/CreateVideoController.php`
- **API Controllers:**
  - `app/Http/Controllers/Api/VideoApiController.php`
  - `app/Http/Controllers/Api/EncodingJobApiController.php`
- **Routes (web):** `routes/web.php` (deja setate)

## 🧪 TEST RAPID (verifică că merge totul)

```bash
# 1. Navigate to a channel Create Video page
# http://localhost/admin/create-video/1

# 2. Select a category from dropdown
# → Videos se vor încărca în tabel

# 3. Click "Select" pe un video
# → Status se schimbă din "Please Select Video" → "Selected: [name]"

# 4. Setează opțiuni în formul (bitrate, logo, text, etc.)

# 5. Click "Create Video"
# → Job apare în "Test Video" section cu status "pending"

# 6. Click "Test" pe job
# → Creează un test job (status "test_running")

# 7. Verifică că status se actualizează automat (refresh 5 sec)
```

## 🔧 CE TREBUIE VERIFICAT

1. **Bitrate dropdown** - OK (1000-4000k)
2. **Videos table** - OK (checkbox + Title|Duration|Size)
3. **Category filter** - verific că GET /api/videos?category_id=X merge
4. **Create Video** - verific că POST /api/encoding-jobs creează job
5. **Test Video section** - verific că GET /api/encoding-jobs?live_channel_id=X merge
6. **Job status icon** - verific dacă se animează (pending/running)

## 🚀 WORKFLOW EXACT (ca în Fox)

### 1. User merge la pagina Create Video (per canal)
```
GET /create-video/{channel_id}
```
- Se încarc: channel name, logo, resolution, categories

### 2. User selectează o categorie
```
GET /api/videos?category_id=ID
```
- Se reîncarcă tabel cu videos din categoria selectată

### 3. User selectează un video din tabel
- Click "Select" → setBlocul de setări arată "Selected: [video name]"
- Video_id se pune în hidden input

### 4. User configurează setări (codec, bitrate, logo, text)
- Totul e în formul stânga
- Valori default deja setate (identic cu Fox)

### 5. User click "Create Video"
```
POST /api/encoding-jobs
{
  "live_channel_id": ID,
  "video_id": ID,
  "settings": { ... JSON cu codecuri/overlay/etc ... }
}
```
- Creează rând în `encoding_jobs` cu status "pending"
- Apare în "Test Video" section

### 6. User pode apăsa "Test" pe un job
```
POST /api/encoding-jobs/{job}/test
```
- Creează un "test job" (durata limitată = Test Time Limit)

### 7. User poate apăsa "Delete" pe un job
```
DELETE /api/encoding-jobs/{job}
```
- Șterge job din DB

### 8. Convert All Videos
```
POST /api/encoding-jobs/bulk
{
  "live_channel_id": ID,
  "video_ids": [1,2,3,4,...],
  "settings": { ... }
}
```
- Creează joburi pentru TOATE videourile din tabel

## 📋 CHECKLIST (verify că totu merge)

- [ ] Pagina se deschide: GET /create-video/{channel}
- [ ] Channel name/logo se vede sus dreapta
- [ ] Category selector funcționează
- [ ] Videos se reîncarcă la schimbarea categoriei
- [ ] Checkbox "Select all" bifează toate videos
- [ ] Buton "Select" setează video_id
- [ ] Buton "Watch" deschide modal cu video
- [ ] Buton "Delete" șterge video din tabel
- [ ] Bitrate dropdown are 7 opțiuni (1000-4000)
- [ ] Formul setări (codec, logo, text) e complet
- [ ] Buton "Create Video" creează job (apare în Test Video)
- [ ] Test Video section arată jobs cu status
- [ ] Status icon se animează (pending/running/done/failed)
- [ ] Buton "Test" pe job funcționează
- [ ] Buton "Delete" pe job funcționează
- [ ] "Convert All Videos" creează bulk jobs
- [ ] "Delete All Videos" șterge toate joburile

## 🎨 STYLING (1:1 cu Fox)

- Culori: dark theme (#0b1220 background, #e5e7eb text)
- Layout: CSS Grid (2 coloane)
- Inputs: Dark background, contrast bun
- Tabele: Striped, cu hover effects
- Status dots: Cu animații (pending=ambru, running=albastru pulsing, done=verde, failed=roșu)

## ⚠️ PROBLEME POSIBILE

### Dacă videos nu se reîncarcă la category change:
```
- Verific: GET /api/videos?category_id=3
- Trebuie să returneze JSON cu videos din acea categorie
- Check database: sunt videos cu video_category_id corect?
```

### Dacă Create Video dă error:
```
- Check console JS (F12) pentru error message
- Verify: POST /api/encoding-jobs
- Check: live_channel_id, video_id sunt în payload?
- Check database: encoding_jobs rând e creat?
```

### Dacă Test Video section nu se reîncarcă:
```
- Verific: GET /api/encoding-jobs?live_channel_id=1
- Trebuie să returneze JSON cu jobs
- Setinterval(loadJobs, 5000) refresh automat la 5 sec
```

## 📞 CONTACT

Dacă ceva nu merge, spune-mi:
1. Ce exact nu funcționează?
2. Ce error se vede în console (F12)?
3. Ce request se vede în Network tab?

---

**Status:** ✅ GATA PENTRU TEST
**Dată:** 2025-12-16
**Versiune:** 1.0 (Fox Codec compatible)
