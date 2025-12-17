# 📌 EMPLOYEE: TEST IMPORT FUNCTIONALITY

**GOAL**: Provide PROOF that import works (or identify exact problem)

**TIME**: 15-20 minutes

---

## 📋 WHAT YOU'LL DO

1. Open browser and test import
2. Capture screenshots from DevTools
3. Check server logs and database
4. Document everything
5. Send results

---

## 🚀 STEP-BY-STEP

### STEP 1: Check Everything Is Ready

Open terminal and run:

```bash
cd /var/www/iptv-panel

# Check routes
php artisan route:list | egrep "import"

# Check video count BEFORE
php artisan tinker --execute="echo 'Videos before: ' . \App\Models\Video::count() . PHP_EOL;"
```

**Expected:**
- Should see: `POST ... /video-categories/{category}/import ... admin.video_categories.import`
- Video count: Any number (e.g., 19)

---

### STEP 2: Open Import Page in Browser

1. Go to: `http://your-server/video-categories/3/browse`
   
2. You should see:
   - Page title: "Import Videos into: ACTIUNE"
   - List of files with checkboxes
   - Button: "📥 Import Selected"

---

### STEP 3: Open DevTools

1. Press **F12** on keyboard
2. Click **Network** tab
3. Click **Console** tab (side by side with Network)

---

### STEP 4: Test Import

1. **Select ONE checkbox** next to any video file
2. You should see counter change to: **(1 selected)**
3. Click **"📥 Import Selected"** button
4. **WAIT 5-10 seconds** for page to reload

---

### STEP 5: Capture Evidence

#### Screenshot 1: Network Request

In DevTools:
- Look for request to `/video-categories/3/import`
- Click on it
- Click **Headers** tab
- Take screenshot showing:
  ```
  POST /video-categories/3/import HTTP/1.1
  Status: 200 OK (or 302 Found)
  ```

#### Screenshot 2: Console Output

Click **Console** tab, take screenshot showing:
- No red error messages (or document them if there are)
- Should see messages like: `✅ Browse page loaded`

#### Screenshot 3: Browser Page

After page reloads, take screenshot showing:
- ✅ Green message: **"Imported: 1 video"**
- Video has badge: **"✓ Imported"**
- Checkbox is disabled (greyed out)

---

### STEP 6: Verify Database

In terminal, run:

```bash
cd /var/www/iptv-panel

# Check video count AFTER
php artisan tinker --execute="echo 'Videos after: ' . \App\Models\Video::count() . PHP_EOL;"

# Check latest video
php artisan tinker --execute="
\$v = \App\Models\Video::latest()->first();
echo 'Latest: ' . \$v->id . ' - ' . \$v->title . ' - Category: ' . \$v->video_category_id . PHP_EOL;
"
```

**Expected:**
- Video count should be +1 higher than before
- Latest video title should match file you imported
- Category should be 3

---

### STEP 7: Verify in UI

1. Go to: `http://your-server/admin/video-categories/3`
2. You should see the imported video in the list
3. Click it to verify metadata (title, duration, etc.)

---

## 📸 WHAT TO SEND

Create file: `IMPORT_EVIDENCE.txt`

```
════════════════════════════════════════════════════════════════
                    IMPORT FUNCTIONALITY TEST
════════════════════════════════════════════════════════════════

DATE: [today]
TESTED BY: [your name]

BEFORE TEST:
Video count: [paste output]

TEST:
1. Opened: http://your-server/video-categories/3/browse
2. Selected: [filename] checkbox
3. Clicked: Import Selected button
4. Page reloaded: YES / NO

EVIDENCE:

Screenshot 1 - Network tab:
[PASTE SCREENSHOT or describe:]
- URL: /video-categories/3/import
- Method: POST
- Status: 200 or 302

Screenshot 2 - Console output:
[PASTE SCREENSHOT or paste text:]
...

Screenshot 3 - Browser page after reload:
[PASTE SCREENSHOT showing green message]

AFTER TEST:
Video count: [paste output]
Latest video: [paste output]

DATABASE RESULT:
✅ Video created / ❌ Video NOT created

UI VERIFICATION:
✅ Video appears in library / ❌ Video NOT in library

FINAL RESULT:
✅ IMPORT WORKS
❌ IMPORT BROKEN - Error: [describe]

════════════════════════════════════════════════════════════════
```

---

## 🐛 IF SOMETHING BREAKS

### Error: "Method Not Allowed" or "404"

```bash
cd /var/www/iptv-panel
php artisan route:list | grep import
```

Must show: `POST /video-categories/{category}/import`

If not found, tell supervisor.

---

### Error: "419 Token Mismatch"

Check browser console for error about CSRF.

This means form is missing `@csrf` token.

Check that file `/resources/views/admin/video_categories/browse.blade.php` has:

```
<form method="POST" ...>
    @csrf
    ...
</form>
```

---

### Error: Page doesn't reload

1. Check Console tab for red errors
2. Check Network tab to see if POST request was sent
3. If POST shows 500 error, check server log:

```bash
tail -50 /var/www/iptv-panel/storage/logs/laravel.log
```

Copy the error and send to supervisor.

---

### Page reloads but no green message

1. Check that you're viewing the correct page (should show "Imported Videos" or similar)
2. Video might have been imported but message not visible
3. Run database check to see if video was actually created

---

## ✅ SUCCESS CHECKLIST

- [ ] Opened /video-categories/3/browse
- [ ] Selected checkbox next to video
- [ ] Clicked "Import Selected"
- [ ] Page reloaded (didn't just stay on same page)
- [ ] Green success message appeared
- [ ] Video shows "✓ Imported" badge
- [ ] Database video count increased by 1
- [ ] Network tab shows POST with status 200 or 302
- [ ] Console has NO red error messages
- [ ] Screenshots collected

---

## 📬 SUBMIT TO SUPERVISOR

Send:
1. `IMPORT_EVIDENCE.txt` file (with all info above)
2. Screenshots (3-4 screenshots)
3. Server log entries (if any errors)

**DO NOT SAY "it works" without evidence!**

---

**Questions?** Show supervisor the checklist above.
