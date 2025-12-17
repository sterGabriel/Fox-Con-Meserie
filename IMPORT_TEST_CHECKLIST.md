# 🔧 IMPORT FUNCTIONALITY - TEST & FIX CHECKLIST

**Deadline**: Must provide PROOF, not promises  
**Supervisor**: Review before marking complete

---

## ✅ PHASE 1: VERIFICATION (DO THIS FIRST)

### 1️⃣ Check Routes Are Registered

```bash
cd /var/www/iptv-panel
php artisan route:list | egrep "video-categories.*browse|import"
```

**Expected output:**
```
GET|HEAD  video-categories/{category}/browse  admin.video_categories.browse
POST      video-categories/{category}/import  admin.video_categories.import
```

❌ **If NOT found**: Routes are missing. Show supervisor.

---

### 2️⃣ Check Database Is Empty

```bash
cd /var/www/iptv-panel
php artisan tinker --execute="echo 'Videos in DB: ' . \App\Models\Video::count().PHP_EOL;"
```

**Expected**: Should show a number (starting point)

---

### 3️⃣ Check File Exists in /media

```bash
ls -lh /media/videos/FILME/ACTIUNE/ | head -5
```

**Expected**: Should see .mp4 or .mkv files

---

## 🎯 PHASE 2: REAL TEST (THIS IS THE PROOF)

### Step 1: Open Browser DevTools

1. Open: `http://your-server/video-categories/3/browse`
2. Press **F12** (Developer Tools)
3. Go to **Network** tab
4. Go to **Console** tab

---

### Step 2: Test Import

1. **Check ONE checkbox** next to a video file
2. Click **"📥 Import Selected"** button
3. **WAIT** for page to reload (5-10 seconds)

---

### Step 3: Capture Evidence

#### A. Network Request (CRITICAL)

In DevTools Network tab:
- Look for request to `/video-categories/3/import`
- **Click on it**, then go to **Headers** tab
- Take screenshot showing:
  - URL
  - Method (should be **POST**)
  - Status code (should be **200** or **302**)

Then go to **Preview** tab:
- Take screenshot of Response (should show success message)

**Expected response:**
```
Imported: 1 video | Errors: 0
```

❌ **If you see:**
- **404**: Route not found
- **405**: Wrong HTTP method (check form method="POST")
- **419**: CSRF token missing or invalid
- **500**: Server error (check logs in Step B)

---

#### B. Console Errors

In DevTools Console tab:
- Take screenshot of any red error messages
- If you see JavaScript errors, **STOP and show to supervisor**

**Expected**: Messages like:
```
✅ Browse page loaded - Import system ready
Count updated: 1
🔄 Form submit event fired. Selected: 1
✅ Form allowed to submit with 1 files
```

---

#### C. Server Logs

**During the import**, in a separate terminal:

```bash
cd /var/www/iptv-panel
# Keep this running while you import
tail -f storage/logs/laravel.log
```

When you click Import, you should see entries like:
```
[timestamp] local.INFO: Video imported: filename.mp4 → /media/videos/...
```

**Save the log output** (copy last 50 lines after import)

---

#### D. Database Verification

After import, check if video was created:

```bash
cd /var/www/iptv-panel
php artisan tinker --execute="
\$count = \App\Models\Video::count();
echo 'Total videos: ' . \$count . PHP_EOL;
\$latest = \App\Models\Video::latest()->first();
if (\$latest) {
    echo 'Latest: ID ' . \$latest->id . ' - ' . \$latest->title . PHP_EOL;
}
"
```

**Expected**: Count should increase by 1, latest video shows the imported file

---

#### E. UI Confirmation

After page reloads:
- Take screenshot showing:
  - ✅ Green success message: "Imported: X videos"
  - ✓ Video shows "Imported" badge
  - ☑️ Checkbox is now disabled

---

## 📋 DELIVERABLES (CREATE DOCUMENT WITH)

**File**: `IMPORT_TEST_RESULTS.txt` or `.md`

Include:

```
═════════════════════════════════════════════════════════════
IMPORT FUNCTIONALITY TEST - DATE: 2025-12-15
═════════════════════════════════════════════════════════════

1. ROUTE VERIFICATION
Command: php artisan route:list | egrep "import"
Output:
[PASTE HERE]

2. PRE-IMPORT VIDEO COUNT
Command: php artisan tinker --execute="echo \App\Models\Video::count().PHP_EOL;"
Output: [PASTE NUMBER]

3. NETWORK REQUEST
URL: /video-categories/3/import
Method: POST
Status: [PASTE STATUS]
[ATTACH SCREENSHOT]

4. CONSOLE OUTPUT
[ATTACH SCREENSHOT showing no red errors]

5. SERVER LOGS
Entries from tail -f during import:
[PASTE LOG ENTRIES]

6. POST-IMPORT VIDEO COUNT
Command: php artisan tinker --execute="echo \App\Models\Video::count().PHP_EOL;"
Output: [PASTE NUMBER - SHOULD BE +1]

7. UI PROOF
[ATTACH SCREENSHOT showing ✅ green message + ✓ Imported badge]

RESULT: ✅ IMPORT WORKS / ❌ IMPORT BROKEN

═════════════════════════════════════════════════════════════
```

---

## 🐛 PHASE 3: IF TEST FAILS

### If you see 419 error:

Check the form in `browse.blade.php`:

```php
<form method="POST" action="{{ route('admin.video_categories.import', $category) }}">
    @csrf
    <!-- checkboxes here -->
    <button type="submit">Import</button>
</form>
```

**MUST HAVE:**
- ✅ `method="POST"` 
- ✅ `@csrf` token
- ✅ Checkboxes with `name="files[]"`
- ✅ Button with `type="submit"`

---

### If you see 405 error:

Check route in `routes/web.php`:

```php
Route::post('/video-categories/{category}/import', [FileBrowserController::class, 'import'])
    ->name('admin.video_categories.import');
```

Must be `Route::post()` not `Route::get()`

---

### If you see 500 error:

Check Laravel logs:

```bash
tail -100 /var/www/iptv-panel/storage/logs/laravel.log
```

Look for Exception stack trace, **copy ENTIRE error** and send to supervisor.

---

### If page doesn't reload:

Open Console (F12), look for red errors.

Common issues:
- `document.getElementById('browser-form') is null` → Form doesn't exist in DOM
- CSRF token validation failed → Check @csrf in form
- Fetch/XHR errors → Check Network tab for failed request

---

## ⚡ PHASE 4: PERFORMANCE FIX (IF SLOW)

If browsing `/media` is slow:

### Problem: ffprobe runs on ALL files during list

**Location**: `FileBrowserController.php` - `browse()` method

Look for:
```php
'duration' => $this->getVideoDuration($full),
'metadata' => $this->getVideoMetadata($full),
```

### Solution: Remove from list, only on demand

**Replace with:**
```php
'duration' => null,  // Don't load on list
'metadata' => null,  // Don't load on list
```

Load metadata only when:
- User clicks "Info" button
- User hovers over video
- Via separate AJAX request

---

## ✅ FINAL CHECKLIST

- [ ] Routes verified (POST /video-categories/{id}/import exists)
- [ ] Form has method="POST" + @csrf
- [ ] Checkboxes have name="files[]"
- [ ] Button has type="submit"
- [ ] Clicked Import button
- [ ] Page reloaded (didn't just disappear)
- [ ] Green success message appeared
- [ ] Video shows "✓ Imported" badge
- [ ] Database count increased by 1
- [ ] Network tab shows POST → 200/302
- [ ] Console has NO red errors
- [ ] Screenshots collected

---

## 🎯 SUCCESS CRITERIA

✅ **PASS**:
- POST request shows Status 200 or 302
- Database video count increased
- Page reloaded with success message
- Video shows "Imported" badge
- Can see video in Video Library → Playlist

❌ **FAIL**:
- Status is 404, 405, 419, or 500
- Database count didn't change
- No success message
- Console shows JavaScript errors
- Page didn't reload

---

**Submit results to supervisor with ALL evidence before claiming task complete.**
