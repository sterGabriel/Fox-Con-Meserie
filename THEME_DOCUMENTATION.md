# 🎨 IPTV Panel - Grafana PRO Enterprise Theme

## Overview

This IPTV Panel now features a **professional, enterprise-grade user interface** inspired by **Grafana Dashboard PRO**. The design system provides a modern, dark-themed aesthetic with exceptional usability and visual appeal.

## 🌟 Key Features

### **Design System**
- **Grafana-inspired dark theme** with professional color palette
- **Comprehensive design tokens** for consistency across all components
- **Modern typography** using Inter font family
- **Smooth animations** and micro-interactions
- **Responsive layout** that adapts to all screen sizes

### **Component Library**
- **Panels & Cards** - Beautiful containers with hover effects
- **Stat Cards** - Eye-catching metrics with color-coded borders
- **Tables** - Professional data grids with hover states
- **Buttons** - Multiple variants (primary, success, danger, warning, ghost)
- **Badges & Status Indicators** - Color-coded status displays
- **Forms** - Clean, modern input fields with focus states
- **Alerts** - Contextual notifications with severity levels
- **Progress Bars** - Animated progress indicators
- **Empty States** - Friendly messages for zero-data scenarios

### **Navigation**
- **Modernized sidebar** with smooth transitions
- **Active state highlighting** with visual feedback
- **Collapsible submenus** for better organization
- **Icon-based navigation** for quick recognition

### **Color Palette**

#### Background Colors
- Primary: `#0b0d12`
- Secondary: `#111318`
- Panel: `#181b1f`

#### Brand Colors
- Primary Blue: `#3b82f6`
- Success Green: `#10b981`
- Warning Amber: `#f59e0b`
- Danger Red: `#ef4444`
- Info Cyan: `#06b6d4`

#### Status Colors
- Online/Streaming: `#10b981` (Green)
- Offline: `#6b7280` (Gray)
- Error: `#ef4444` (Red)
- Warning: `#f59e0b` (Amber)

## 📁 File Structure

```
resources/
├── css/
│   └── app.css                              # Main theme file with design tokens
public/assets/css/
├── grafana-pro-components.css               # Complete component library
├── fox-sidebar-pro.css                      # Modernized sidebar styles
├── fox-base.css                             # Base design tokens
├── fox-topnav.css                           # Top navigation styles
└── fox-subheader.css                        # Sub-header styles

resources/views/
├── layouts/
│   └── panel.blade.php                      # Main layout with theme integration
├── admin/
│   ├── dashboard_overview.blade.php         # Professional dashboard
│   ├── vod_channels/index.blade.php         # VOD channels management
│   └── live_channels/index.blade.php        # Live channels management
```

## 🎯 Usage Examples

### Using Stat Cards

```html
<div class="g-stat-card success">
    <div class="g-stat-label">Active Channels</div>
    <div class="g-stat-value">24</div>
    <div class="g-stat-description">Currently streaming</div>
    <span class="g-stat-trend up">↗ +12% vs last week</span>
</div>
```

### Using Buttons

```html
<!-- Primary Button -->
<button class="g-btn g-btn-primary">Save Changes</button>

<!-- Success Button -->
<button class="g-btn g-btn-success">Start Channel</button>

<!-- Danger Button -->
<button class="g-btn g-btn-danger">Delete</button>

<!-- Ghost Button -->
<button class="g-btn g-btn-ghost">Cancel</button>

<!-- Small Button -->
<button class="g-btn g-btn-primary g-btn-sm">Small Action</button>
```

### Using Badges

```html
<span class="g-badge g-badge-success">RUNNING</span>
<span class="g-badge g-badge-danger">ERROR</span>
<span class="g-badge g-badge-warning">IDLE</span>
<span class="g-badge g-badge-info">QUEUED</span>
```

### Using Status Dots

```html
<span class="g-status-dot online"></span>
<span class="g-status-dot streaming"></span>
<span class="g-status-dot error"></span>
<span class="g-status-dot warning"></span>
<span class="g-status-dot offline"></span>
```

### Using Panels

```html
<div class="g-panel">
    <div class="g-panel-header">
        <div>
            <h3 class="g-panel-title">Panel Title</h3>
            <p class="g-panel-subtitle">Description text</p>
        </div>
        <button class="g-btn g-btn-primary g-btn-sm">Action</button>
    </div>
    <div class="g-panel-body">
        <!-- Content here -->
    </div>
</div>
```

### Using Tables

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

### Using Forms

```html
<div class="g-form-group">
    <label class="g-label">Channel Name</label>
    <input type="text" class="g-input" placeholder="Enter channel name">
    <span class="g-help-text">Choose a unique name for your channel</span>
</div>
```

### Using Alerts

```html
<div class="g-alert g-alert-success">
    Operation completed successfully!
</div>

<div class="g-alert g-alert-warning">
    Please review your settings before continuing.
</div>

<div class="g-alert g-alert-danger">
    An error occurred. Please try again.
</div>
```

## 🎨 Design Principles

1. **Consistency** - All components follow the same design language
2. **Clarity** - Information hierarchy is clear and intentional
3. **Feedback** - Interactive elements provide visual feedback
4. **Accessibility** - High contrast ratios and focus states
5. **Performance** - Optimized animations and transitions
6. **Scalability** - Modular components that can be easily extended

## 🚀 Quick Start

### Build Assets
```bash
cd /var/www/iptv-panel
npm run build
```

### Development Mode
```bash
npm run dev
```

## 📊 Dashboard Features

The new dashboard includes:

- **System Status Cards** - Real-time metrics with color-coded borders
- **Resource Monitoring** - CPU, RAM, Disk, Network with progress bars
- **Health Alerts** - Prioritized list of issues requiring attention
- **Channel Activity** - Recent changes and updates
- **Quick Actions** - Easy access to common tasks

## 🎯 Updated Pages

### ✅ Dashboard Overview
- Modern stat cards with color-coded indicators
- System resource monitoring with progress bars
- Health alerts table with severity levels
- Recent channel activity timeline

### ✅ VOD Channels
- Professional channel listing with logos
- Real-time status indicators
- Action buttons with icons
- Dropdown menus for bulk actions
- Empty state messaging

### ✅ Live Channels
- Clean table layout
- Status badges
- Action buttons
- Responsive design

## 🔧 Customization

### Changing Colors

Edit `/var/www/iptv-panel/resources/css/app.css`:

```css
:root {
    --g-brand-primary: #your-color;
    --g-brand-success: #your-color;
    /* etc... */
}
```

### Adding New Components

All component styles are in `/var/www/iptv-panel/public/assets/css/grafana-pro-components.css`

Follow the existing patterns for consistency.

## 📱 Responsive Design

The theme is fully responsive with breakpoints at:
- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: < 768px

## 🌙 Dark Theme

The entire interface uses a professional dark theme optimized for:
- **Reduced eye strain** during long sessions
- **Better contrast** for data visualization
- **Modern aesthetic** that looks professional
- **Focus on content** with minimal distractions

## ⚡ Performance

- **Optimized CSS** with minimal overhead
- **Hardware-accelerated animations** for smooth transitions
- **Lazy loading** for images and heavy content
- **Efficient selectors** for fast rendering

## 🎓 Best Practices

1. **Always use design tokens** (CSS variables) instead of hardcoded values
2. **Follow the component structure** for consistency
3. **Test on multiple screen sizes** before deploying
4. **Use semantic HTML** for better accessibility
5. **Provide appropriate hover states** for all interactive elements

## 📝 Component Class Naming Convention

- `g-*` - Grafana PRO component classes
- `fox-*` - Legacy Fox components (being phased out)
- `g-panel-*` - Panel-related classes
- `g-btn-*` - Button variants
- `g-badge-*` - Badge variants
- `g-stat-*` - Stat card classes

## 🔮 Future Enhancements

- [ ] Dark/Light theme toggle
- [ ] Additional chart components
- [ ] Advanced data visualization
- [ ] Custom theme builder
- [ ] Component playground
- [ ] Accessibility improvements
- [ ] Animation library expansion

## 📞 Support

For questions or issues related to the theme, please refer to the main project documentation or contact the development team.

---

**Built with ❤️ for professional IPTV management**

*Enterprise-grade design meets powerful functionality*
