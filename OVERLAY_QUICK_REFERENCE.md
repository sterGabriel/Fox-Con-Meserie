# Overlay Tab - Quick Reference Guide

## Form Location
**URL**: `/admin/vod-channels/{id}/settings?tab=overlay`  
**File**: `resources/views/admin/vod_channels/settings_tabs/overlay.blade.php`  
**Controller**: `app/Http/Controllers/Admin/LiveChannelController.php` → `updateSettings()`

---

## How to Use

### Logo Overlay
1. Check "Enable Logo"
2. Upload PNG or SVG file
3. Choose position: TL/TR/BL/BR or CUSTOM (then set X/Y)
4. Set width and height in pixels
5. Adjust opacity slider (0-100%)
6. See preview thumbnail

**Database Fields**:
```
overlay_logo_enabled       (boolean, default: false)
overlay_logo_path          (string, file path)
overlay_logo_position      (TL/TR/BL/BR/CUSTOM)
overlay_logo_x             (int, 0-1920, default: 20)
overlay_logo_y             (int, 0-1080, default: 20)
overlay_logo_width         (int, 20-500, default: 150)
overlay_logo_height        (int, 20-500, default: 100)
overlay_logo_opacity       (float, 0-100, default: 80)
```

### Text Overlay
1. Check "Enable Text Overlay"
2. Choose text source:
   - Channel Name (uses dynamic data)
   - Video Title (uses dynamic data)
   - Custom Text (enter static text)
3. Select font family (Arial, Helvetica, Courier, Times)
4. Set font size (12-120px)
5. Pick font color
6. Set background color and padding
7. Choose position and X/Y offsets
8. Adjust opacity values

**Database Fields**:
```
overlay_text_enabled       (boolean, default: false)
overlay_text_content       (channel_name/title/custom)
overlay_text_custom        (string max:255)
overlay_text_font_family   (Arial/Helvetica/Courier/Times)
overlay_text_font_size     (int, 12-120, default: 28)
overlay_text_color         (hex string, default: #FFFFFF)
overlay_text_padding       (int, 0-30, default: 6)
overlay_text_position      (TL/TR/BL/BR/CUSTOM)
overlay_text_x             (int, 0-1920, default: 20)
overlay_text_y             (int, 0-1080, default: 20)
overlay_text_opacity       (float, 0-100, default: 100)
overlay_text_bg_color      (hex string, default: #000000)
overlay_text_bg_opacity    (float, 0-100, default: 60)
```

### Timer/Clock
1. Check "Enable Timer / Clock"
2. Choose timer mode:
   - Real Time (system clock)
   - Elapsed (stream duration)
3. Select time format:
   - HH:mm (14:30)
   - HH:mm:ss (14:30:45)
   - HH:mm:ss.mmm (with milliseconds)
4. Choose position and X/Y offsets
5. Set font size and color
6. Pick style (Normal/Bold/Shadow)
7. Choose background (None/Dark/Colored)
8. Adjust opacity

**Database Fields**:
```
overlay_timer_enabled      (boolean, default: false)
overlay_timer_mode         (realtime/elapsed, default: realtime)
overlay_timer_format       (HH:mm/HH:mm:ss/HH:mm:ss.mmm)
overlay_timer_position     (TL/TR/BL/BR/CUSTOM)
overlay_timer_x            (int, 0-1920, default: 20)
overlay_timer_y            (int, 0-1080, default: 20)
overlay_timer_font_size    (int, 12-100, default: 24)
overlay_timer_color        (hex string, default: #FFFFFF)
overlay_timer_style        (normal/bold/shadow, default: normal)
overlay_timer_bg           (none/dark/colored, default: none)
overlay_timer_opacity      (float, 0-100, default: 100)
```

### Safe Margins
1. Use slider to set margin (0-50px)
2. Applies to all overlay elements
3. Ensures compatibility with legacy displays

**Database Field**:
```
overlay_safe_margin        (int, 0-50, default: 30)
```

### FFmpeg Filter Preview
1. Adjust any overlay controls above
2. Filter preview auto-updates
3. Click "Copy Filter Command" to copy to clipboard
4. Use in FFmpeg encoding

---

## Positioning Guide

### Preset Positions (4 Corners)
- **TL** = Top Left (20px from each edge)
- **TR** = Top Right (20px from each edge)
- **BL** = Bottom Left (20px from each edge)
- **BR** = Bottom Right (20px from each edge)

### Custom Positioning
- Select **CUSTOM** from position dropdown
- Enter exact X coordinate (0-1920px)
- Enter exact Y coordinate (0-1080px)
- Allows pixel-perfect placement

### Safe Margin Application
Safe margin value applies as minimum distance from screen edges for all overlays.
- If safe margin = 30px, overlays won't appear closer than 30px to any edge
- Useful for displays with overscan

---

## Color Selection

### Format: Hex Colors
All color inputs use HTML5 color picker:
- Click color box to open picker
- Select desired color
- Stores as hex (#RRGGBB format)

### Common Colors
```
#FFFFFF = White
#000000 = Black
#FF0000 = Red
#00FF00 = Green
#0000FF = Blue
#FFFF00 = Yellow
```

---

## Validation Rules

### File Upload
- Accepted: PNG, SVG
- Max size: Server default (usually 2MB)
- Stored in: `storage/private/logos/channels/{channel_id}/`

### Numeric Inputs
- X/Y: Integer 0-1920 / 0-1080
- Width/Height: Integer 20-500px
- Font Size: Integer 12-120px (timer: 12-100px)
- Padding: Integer 0-30px
- Safe Margin: Integer 0-50px
- Opacity: Float 0-100

### Dropdown Values
- Position: TL, TR, BL, BR, CUSTOM
- Font: Arial, Helvetica, Courier, Times
- Timer Mode: realtime, elapsed
- Timer Format: HH:mm, HH:mm:ss, HH:mm:ss.mmm
- Timer Style: normal, bold, shadow
- Timer Background: none, dark, colored
- Text Source: channel_name, title, custom

---

## Example Configurations

### Config 1: Professional News Broadcast
```
Logo: Top Right corner, 150x100px, 100% opacity
  └─ Channel logo

Text: Bottom Left custom, 24pt white, dark background
  └─ "LIVE NEWS UPDATE"

Timer: Top Right, HH:mm:ss format, white bold
  └─ Real-time clock

Safe Margin: 30px
```

### Config 2: Sports Event Stream
```
Logo: Top Left corner, 120x80px, 85% opacity
  └─ Network logo

Text: Bottom center custom, 32pt yellow, semi-transparent black
  └─ Dynamic score display

Timer: Bottom right, HH:mm format, white shadow
  └─ Elapsed time counter

Safe Margin: 20px
```

### Config 3: Music Event
```
Logo: Top left custom (X:30, Y:40), 100x100px
  └─ Event logo

Text: Custom "LIVE FROM VENUE", 28pt white
  └─ Position: Center bottom (custom X:960, Y:1000)

Timer: Disabled for music events

Safe Margin: 40px (for TV compatibility)
```

---

## Troubleshooting

### Logo Not Showing
- Verify file was uploaded (check "Current: filename")
- Check file format (PNG or SVG only)
- Verify file size < 2MB
- Check position values are not negative

### Text Not Visible
- Check "Enable Text Overlay" is checked
- Verify font color is visible on background
- Check opacity isn't set to 0%
- Ensure position isn't off-screen

### Timer Shows Wrong Time
- Check timer mode: realtime = system clock, elapsed = stream time
- Verify format matches available options
- Check opacity isn't 0%

### Filter Preview Not Updating
- Click "Refresh Preview" button
- Save form to apply changes
- Check browser console for JavaScript errors

---

## Form Persistence

All form values persist using Laravel's `old()` helper:
- If form has validation errors, values stay in fields
- User can correct and resubmit
- No data is lost on validation failure

---

## Save & Deploy

1. Fill out all overlay sections
2. Click "Save Settings" button
3. Form validates all required fields
4. If valid: saves to database, redirects to success
5. If invalid: shows errors, keeps form data
6. To use overlays: generate FFmpeg command using preview filter

---

## Related Files

- **Form**: `resources/views/admin/vod_channels/settings_tabs/overlay.blade.php` (462 lines)
- **Controller**: `app/Http/Controllers/Admin/LiveChannelController.php` (updateSettings method)
- **Database**: `database/migrations/2025_12_15_130000_add_missing_overlay_columns.php`
- **Model**: `app/Models/LiveChannel.php`

---

## Support

For issues or questions:
1. Check OVERLAY_FIX_REPORT.md (technical details)
2. Check TASK_5B_OVERLAY_COMPLETION.md (comprehensive guide)
3. Review form validation in controller
4. Check browser console for JavaScript errors

---

**Last Updated**: 2024-12-15  
**Status**: Production Ready ✅
