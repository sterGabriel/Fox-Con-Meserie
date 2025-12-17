# ✅ ACCEPTANCE CHECKLIST - Create Video Page (10 min)

**Scop:** Verifica în 2 minute dacă pagina e **1:1 cu Fox Codec**

**Cum se folosește:** Deschide pagina în browser, bifează din listă pe măsură ce verifici.

---

## 🎯 CHECKLIST (10 puncte)

### 1️⃣ Layout & Channel Info
- [ ] Pagina are 2 coloane (stânga form / dreapta videos + jobs)
- [ ] Sus dreapta: **Channel Name + Logo + Resolution** (ex: "📺 MEGA TV 2024 | 1920x1080")
- [ ] Totul e aerisit (spacing OK, nu e înghestuito)

### 2️⃣ Bitrate Dropdown
- [ ] Dropdown "Video Bitrate (Manual)" are **exact 7 opțiuni:**
  - [ ] 1000 kbps
  - [ ] 1500 kbps (Default)
  - [ ] 2000 kbps
  - [ ] 2500 kbps
  - [ ] 3000 kbps
  - [ ] 3500 kbps
  - [ ] 4000 kbps (Standard)

### 3️⃣ Videos Table (dreapta sus)
- [ ] Tabel are coloane: **Checkbox | # | Title·Duration·Size | Actions**
- [ ] Checkbox "Select all" bifează toți videourile
- [ ] "Select" button setează video-ul (apare "Selected: [name]" stânga sus)
- [ ] "Watch" deschide modal cu video player
- [ ] "Delete" șterge video din tabel

### 4️⃣ Category Filter
- [ ] Dropdown "Video Category" funcționează
- [ ] La selectare categorie → tabelul se **reîncarcă cu videos din acea categorie**
- [ ] Dacă categorie e goală → mesaj "No videos in this category"

### 5️⃣ Create Video Button
- [ ] Buton este **RED** ("btn btn-danger")
- [ ] Text: "Create Video"
- [ ] Dacă NU e video selectat → **DISABLED** (nu se poate apăsa) ❌
- [ ] Dacă e video selectat → **ACTIVE** (se poate apăsa) ✅
- [ ] La click → creează job în "Test Video" section

### 6️⃣ Test Video Section (dreapta jos)
- [ ] Tabel cu coloane: **Name | Text | Codec | Bitrate | Status | Actions**
- [ ] Status are **icon dot** (colored circle) + text:
  - [ ] 🟡 **pending** (amber dot, static)
  - [ ] 🔵 **running** (blue dot, pulsing)
  - [ ] 🟢 **done/completed** (green dot, static)
  - [ ] 🔴 **error/failed** (red dot, static)
  - [ ] 🟣 **test_running** (purple dot, pulsing)

### 7️⃣ Job Actions (Test & Delete)
- [ ] Buton "Test" → creează test job (status → "test_running")
- [ ] Buton "X" (delete) → șterge job din tabel
- [ ] După action → tabelul se reîncarcă automat

### 8️⃣ Bulk Actions (butoane jos dreapta)
- [ ] Buton **"Convert All Videos"** (GREEN)
  - [ ] DISABLED dacă NU e video selectat din tabel
  - [ ] ACTIVE dacă sunt videos bifate
  - [ ] La click → creează joburi pentru TOȚI videourile bifate
  - [ ] Dialog confirm: "Convert X selected videos?"

- [ ] Buton **"Delete All Videos"** (RED)
  - [ ] La click → șterge TOATE joburile din "Test Video" section
  - [ ] Dialog confirm: "Delete ALL jobs?"

### 9️⃣ Auto-Refresh & Status Polling
- [ ] Jobs se **actualizează automat** la **5 secunde** (fără refresh manual)
- [ ] Status icon se schimbă live (ex: pending → running → done)
- [ ] Dacă worker creează output_path → se vede în payload

### 🔟 Form Settings (stânga, complet)
- [ ] Codec dropdown: H.264 / H.265
- [ ] Preset dropdown: Disabled / ultrafast / veryfast / faster / medium / slow
- [ ] CRF slider (dacă enabled)
- [ ] Logo toggle + position (Top Left / Top Right / etc) + opacity slider
- [ ] Text toggle + overlay settings + position + opacity
- [ ] Text background (box color, padding, opacity)
- [ ] **Totul e dark-themed** (input background #0b1220, text #e5e7eb)

---

## ⏱️ TIMP: ~10 minute

- Setup: 1 min
- Layout + info: 1 min
- Bitrate: 1 min
- Videos table: 2 min
- Category filter: 1 min
- Create Video + Test: 2 min
- Bulk actions: 1 min
- Status polling: 1 min

---

## 🚨 BLOCKERS (daca vrei sa respingi)

| Issue | Fix |
|-------|-----|
| **Bitrate NU are 7 opțiuni** | ❌ Reject |
| **Video tabel NU are checkbox** | ❌ Reject |
| **Create Video NU e disabled fără video** | ❌ Reject |
| **Status icons NU se animează** | ⚠️ Warning (minor) |
| **Polling NU se reîncarcă la 5 sec** | ❌ Reject |
| **Bulk actions nu crează jobs** | ❌ Reject |

---

## ✅ VERDICT

**OKif:**
- [ ] Minim 8/10 puncte bifate
- [ ] Toți BlockerS sunt rezolvați
- [ ] Styling e FOX-like (dark theme)
- [ ] NU sunt JS errors în console (F12)

**APPROVE** ✅ → Ready for production

**REJECT** ❌ → Return to dev

---

## 📞 REPORT TEMPLATE

Dacă e vreun bug, raportează exact:

```
Punct: [1-10]
Descriere: [Ce nu merge]
Expected: [Cum ar trebui]
Actual: [Ce se întâmplă]
Browser console error: [Da-NU / ce error?]
```

---

**Dată**: 2025-12-16  
**Version**: 1.0 (Fox Codec 1:1)  
**Supervisor**: [Nume]
