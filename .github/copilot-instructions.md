# Copilot Instructions for IPTV Panel

## Project Overview
- **IPTV Panel** is a Laravel 11 app for managing 24/7 TV channels, VOD playlists, and live streaming with advanced encoding (FFmpeg) and monitoring.
- Key features: channel management, playlist reordering, encoding job queue, operational dashboard, and overlay/branding support.

## Architecture & Key Components
- **app/Models/**: Eloquent models for channels, videos, jobs, etc.
- **app/Services/**: Business logic for encoding, playlist management, and integration with FFmpeg/ffprobe.
- **app/Http/**: Controllers (API/UI), middleware, and request validation.
- **resources/views/**: Blade templates for dashboard and management UIs.
- **routes/web.php**: Main web routes; **routes/auth.php** for authentication.
- **public/**: Entry point (index.php), static assets, and stream endpoints.
- **config/**: Laravel and custom configuration (encoding, services).

## Developer Workflows
- **Run the app**: `php artisan serve` (or use a web server pointing to public/)
- **Run tests**: `php artisan test` or `vendor/bin/phpunit`
- **Build assets**: `npm run build` (uses Vite + Tailwind CSS)
- **Queue jobs**: Encoding jobs are queued via dashboard or API, processed by Laravel queue workers.
- **Debug encoding**: FFmpeg commands are previewed in the UI and stored in the DB for troubleshooting.

## Project-Specific Patterns
- **Encoding Profiles**: Defined in DB/config, selected per channel, and used to generate FFmpeg commands.
- **Playlist Looping**: Uses concat demuxer for infinite video playback.
- **Status Dashboard**: Centralized alerts (CRITICAL/WARNING/OK) and KPIs.
- **Manual Overrides**: Advanced users can edit FFmpeg commands before job execution.
- **Drag-drop**: SortableJS for playlist reordering in the UI.

## Integration Points
- **FFmpeg/ffprobe**: Invoked via PHP (see Services), output parsed for metadata and job status.
- **Database**: MySQL, Eloquent ORM.
- **Queue**: Laravel queue system for encoding jobs.

## Conventions
- Use Eloquent for all DB access.
- Use Blade for UI, Tailwind for styling.
- Place business logic in Services, not Controllers.
- Use config files for environment-specific settings.
- Follow Laravel's directory structure and naming.

## References
- See `LIVE_STREAMING_GUIDE.md` for streaming/encoding details.
- See `TASK_3B_COMPLETION_REPORT.md` for implementation notes.
- See `routes/web.php` and `app/Services/` for main logic flows.

---
For questions or unclear patterns, review the above files or ask for clarification.
