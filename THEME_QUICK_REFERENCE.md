# 🎨 Quick Visual Reference - IPTV Panel Theme

## Color Palette

### Primary Colors
```
Primary Blue:    #3b82f6  ███████  Main actions, links
Success Green:   #10b981  ███████  Success states, online status  
Warning Amber:   #f59e0b  ███████  Warnings, caution states
Danger Red:      #ef4444  ███████  Errors, delete actions
Info Cyan:       #06b6d4  ███████  Information, help
```

### Background Colors
```
BG Primary:      #0b0d12  ███████  Main background
BG Secondary:    #111318  ███████  Content areas
Panel BG:        #181b1f  ███████  Cards, panels
Panel Header:    #0f1114  ███████  Panel headers
```

### Text Colors
```
Text Primary:    #e5e7eb  ███████  Main text
Text Secondary:  #9ca3af  ███████  Labels, subtitles
Text Muted:      #6b7280  ███████  Helper text
Text Disabled:   #4b5563  ███████  Disabled state
```

---

## Component Quick Reference

### Buttons
```html
<!-- Primary -->
<button class="g-btn g-btn-primary">Primary Action</button>

<!-- Success -->
<button class="g-btn g-btn-success">Start/Confirm</button>

<!-- Danger -->
<button class="g-btn g-btn-danger">Delete/Stop</button>

<!-- Warning -->
<button class="g-btn g-btn-warning">Caution Action</button>

<!-- Ghost -->
<button class="g-btn g-btn-ghost">Secondary</button>

<!-- Small -->
<button class="g-btn g-btn-primary g-btn-sm">Small</button>

<!-- Icon Only -->
<button class="g-btn g-btn-icon g-btn-primary">⚙</button>
```

### Badges
```html
<span class="g-badge g-badge-primary">INFO</span>
<span class="g-badge g-badge-success">ONLINE</span>
<span class="g-badge g-badge-warning">PENDING</span>
<span class="g-badge g-badge-danger">ERROR</span>
<span class="g-badge g-badge-info">QUEUED</span>
<span class="g-badge g-badge-neutral">OFFLINE</span>
```

### Status Dots
```html
<span class="g-status-dot online"></span>      <!-- Green, pulsing -->
<span class="g-status-dot streaming"></span>   <!-- Blue, animated -->
<span class="g-status-dot error"></span>       <!-- Red, steady -->
<span class="g-status-dot warning"></span>     <!-- Amber, steady -->
<span class="g-status-dot offline"></span>     <!-- Gray, steady -->
```

### Stat Cards
```html
<div class="g-stat-card">            <!-- Default (blue left border) -->
<div class="g-stat-card success">    <!-- Green left border -->
<div class="g-stat-card warning">    <!-- Amber left border -->
<div class="g-stat-card danger">     <!-- Red left border -->
<div class="g-stat-card info">       <!-- Cyan left border -->
```

### Alerts
```html
<div class="g-alert g-alert-success">Success message</div>
<div class="g-alert g-alert-warning">Warning message</div>
<div class="g-alert g-alert-danger">Error message</div>
<div class="g-alert g-alert-info">Info message</div>
```

### Progress Bars
```html
<div class="g-progress">
    <div class="g-progress-bar" style="width: 75%;"></div>
    <div class="g-progress-bar success" style="width: 75%;"></div>
    <div class="g-progress-bar warning" style="width: 75%;"></div>
    <div class="g-progress-bar danger" style="width: 75%;"></div>
</div>
```

---

## Layout Helpers

### Grid System
```html
<div class="g-grid g-grid-2">  <!-- 2 columns -->
<div class="g-grid g-grid-3">  <!-- 3 columns -->
<div class="g-grid g-grid-4">  <!-- 4 columns -->
<div class="g-grid g-grid-6">  <!-- 6 columns -->
```

### Flexbox Utilities
```html
<div class="g-flex">                    <!-- Display: flex -->
<div class="g-flex g-flex-col">         <!-- Column direction -->
<div class="g-flex g-items-center">     <!-- Align center -->
<div class="g-flex g-justify-between">  <!-- Space between -->
<div class="g-flex g-justify-center">   <!-- Center -->
<div class="g-flex g-gap-sm">           <!-- Small gap -->
<div class="g-flex g-gap-md">           <!-- Medium gap -->
<div class="g-flex g-gap-lg">           <!-- Large gap -->
```

### Spacing
```html
<div class="g-mb-sm">   <!-- Margin bottom small -->
<div class="g-mb-md">   <!-- Margin bottom medium -->
<div class="g-mb-lg">   <!-- Margin bottom large -->
<div class="g-mb-xl">   <!-- Margin bottom extra large -->

<div class="g-mt-sm">   <!-- Margin top small -->
<div class="g-mt-md">   <!-- Margin top medium -->
<div class="g-mt-lg">   <!-- Margin top large -->
```

---

## Typography

### Headings
```css
font-size: 32px  /* Page title */
font-size: 24px  /* Section title */
font-size: 18px  /* Subsection */
font-size: 16px  /* Card title */
font-size: 14px  /* Body text */
font-size: 12px  /* Small text */
font-size: 11px  /* Labels */
```

### Font Weights
```css
font-weight: 300  /* Light */
font-weight: 400  /* Regular */
font-weight: 500  /* Medium */
font-weight: 600  /* Semibold */
font-weight: 700  /* Bold */
font-weight: 800  /* Extra bold */
font-weight: 900  /* Black */
```

---

## Animations

### Available Animations
```html
<div class="animate-fadeIn">    <!-- Fade in -->
<div class="animate-slideDown">  <!-- Slide from top -->
<div class="animate-slideUp">    <!-- Slide from bottom -->
<div class="animate-scaleIn">    <!-- Scale up -->
<div class="animate-pulse">      <!-- Pulsing -->
```

---

## Common Patterns

### Page Header
```html
<div class="g-flex g-items-center g-justify-between g-mb-xl">
    <div>
        <h1 style="font-size: 32px; font-weight: 800; color: var(--g-text-primary); margin: 0; margin-bottom: 8px;">
            Page Title
        </h1>
        <p style="font-size: 14px; color: var(--g-text-muted); margin: 0;">
            Page description
        </p>
    </div>
    <button class="g-btn g-btn-primary">Action</button>
</div>
```

### Panel with Header
```html
<div class="g-panel">
    <div class="g-panel-header">
        <div>
            <h3 class="g-panel-title">Panel Title</h3>
            <p class="g-panel-subtitle">Description</p>
        </div>
        <button class="g-btn g-btn-primary g-btn-sm">Action</button>
    </div>
    <div class="g-panel-body">
        Content here
    </div>
</div>
```

### Stat Card
```html
<div class="g-stat-card success">
    <div class="g-stat-label">Label</div>
    <div class="g-stat-value">123</div>
    <div class="g-stat-description">Description text</div>
    <span class="g-stat-trend up">↗ +12%</span>
</div>
```

### Table
```html
<div class="g-table-container">
    <table class="g-table">
        <thead>
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Data 1</td>
                <td>Data 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

### Form Group
```html
<div class="g-form-group">
    <label class="g-label">Field Label</label>
    <input type="text" class="g-input" placeholder="Enter value">
    <span class="g-help-text">Helper text</span>
</div>
```

### Empty State
```html
<div class="g-empty-state">
    <div class="g-empty-icon">📊</div>
    <div class="g-empty-title">No Data Found</div>
    <div class="g-empty-description">Description text</div>
    <button class="g-btn g-btn-primary" style="margin-top: 16px;">
        Create First Item
    </button>
</div>
```

---

## CSS Variables Reference

### Colors
```css
var(--g-bg-primary)           /* #0b0d12 */
var(--g-bg-secondary)         /* #111318 */
var(--g-panel-bg)             /* #181b1f */
var(--g-panel-border)         /* rgba(148, 163, 184, 0.12) */
var(--g-text-primary)         /* #e5e7eb */
var(--g-text-secondary)       /* #9ca3af */
var(--g-text-muted)           /* #6b7280 */
var(--g-brand-primary)        /* #3b82f6 */
var(--g-brand-success)        /* #10b981 */
var(--g-brand-warning)        /* #f59e0b */
var(--g-brand-danger)         /* #ef4444 */
```

### Spacing
```css
var(--g-space-xs)    /* 4px */
var(--g-space-sm)    /* 8px */
var(--g-space-md)    /* 16px */
var(--g-space-lg)    /* 24px */
var(--g-space-xl)    /* 32px */
var(--g-space-2xl)   /* 48px */
```

### Border Radius
```css
var(--g-radius-sm)   /* 4px */
var(--g-radius-md)   /* 8px */
var(--g-radius-lg)   /* 12px */
var(--g-radius-xl)   /* 16px */
```

### Shadows
```css
var(--g-shadow-sm)   /* Subtle shadow */
var(--g-shadow-md)   /* Medium shadow */
var(--g-shadow-lg)   /* Large shadow */
var(--g-shadow-xl)   /* Extra large shadow */
```

### Transitions
```css
var(--g-transition-fast)   /* 150ms ease-in-out */
var(--g-transition-base)   /* 250ms ease-in-out */
var(--g-transition-slow)   /* 350ms ease-in-out */
```

---

## Emojis for Icons

```
🏠 Home/Dashboard
📺 Channels/TV
📁 Folders/Files
⚙️ Settings/Config
🔴 Live/Recording
📊 Charts/Analytics
📋 Playlists
🎬 Videos
🔍 Search
➕ Add/Create
✏️ Edit
🗑️ Delete
⚡ Quick/Fast
💬 Messages
📥 Download
📤 Upload
✅ Success/Complete
⚠️ Warning
❌ Error/Failed
ℹ️ Information
🔒 Locked/Secure
🔓 Unlocked
⏸️ Pause
▶️ Play/Start
⏹️ Stop
⏭️ Skip
🔄 Refresh/Reload
```

---

## Tips

1. **Always use CSS variables** for colors, spacing, etc.
2. **Follow the g-* naming convention** for all custom components
3. **Use semantic HTML** (header, main, section, article, etc.)
4. **Provide hover states** for all interactive elements
5. **Add aria-labels** for accessibility
6. **Test on mobile** before deployment
7. **Use the grid system** for responsive layouts
8. **Animations should be subtle** and purposeful
9. **High contrast is mandatory** for accessibility
10. **Consistency is key** - use existing components

---

**Quick Tip**: All components are in `public/assets/css/grafana-pro-components.css` - refer to that file for complete implementation details!
