<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# 📡 IPTV Dashboard - Professional Streaming Management

**IPTV Panel** is a comprehensive Laravel-based dashboard for managing 24/7 TV channels, VOD playlists, and LIVE streaming with professional-grade encoding profiles.

## ✨ Features

### 🎬 LIVE Streaming
- **Pre-configured Encoding Profiles** (576p, 720p, 1080p, Low CPU)
- **24/7 Playlist Looping** with concat demuxer
- **MPEGTS Container Format** for TV streaming
- **Constant Bitrate (CBR)** for stream stability
- **Manual Override** for advanced users

### 📺 Channel Management
- VOD playlist management with drag-drop reordering
- Video library with auto-sync from file system
- Stream metadata probing (ffprobe integration)
- Logo uploads and channel branding
- Overlay support (title, timer)

### ⚙️ Encoding System
- FFmpeg command preview and customization
- Encoding job queue with status tracking
- Profile-based command generation
- Manual bitrate and preset override
- Stored ffmpeg commands in database

### 🎯 Dashboard & Monitoring
- Operational status dashboard with alerts
- Alert severity system (CRITICAL/WARNING/OK)
- KPI metrics (channels, videos, jobs)
- Quick action buttons
- Dark theme (Grafana-inspired)

## 🚀 Quick Start

1. **Settings**: Select LIVE profile for channel
2. **Playlist**: Add and order videos
3. **Preview**: See FFmpeg command
4. **Queue**: Create encoding jobs
5. **Stream**: Videos play infinitely (24/7)

## 📖 Documentation

- **[LIVE Streaming Guide](./LIVE_STREAMING_GUIDE.md)** - Complete feature documentation
- **[Task 3B Report](./TASK_3B_COMPLETION_REPORT.md)** - Implementation details
- **[API Endpoints](#api-endpoints)** - Route documentation

## 🛠️ Tech Stack

- **Framework**: Laravel 11.31
- **PHP**: 8.4.15
- **Database**: MySQL
- **CSS**: Tailwind CSS (dark theme)
- **JavaScript**: Vanilla JS + SortableJS (drag-drop)
- **Encoding**: FFmpeg + ffprobe integration

## 📋 Project Status

- ✅ Security & Authentication
- ✅ Dashboard & Monitoring
- ✅ VOD Playlist Management
- ✅ Encoding Infrastructure (11 profiles)
- ✅ **LIVE Streaming UI Integration** (3B - COMPLETE)
- ⏳ Job Execution & Background Processing

---

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
