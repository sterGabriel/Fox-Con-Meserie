# Autostart la reboot (fără comenzi manuale)

## Scop
După reboot, canalele care **trebuiau să fie live** pornesc automat, fără să intri în UI și fără să rulezi comenzi.

## Cum decide sistemul ce pornește
- Pornește automat canalele `enabled=1` care au `status='live'` în DB.
- Dacă procesul ffmpeg nu mai există (PID mort / reboot), canalul este pornit din nou.

## Ce s-a adăugat
- Command: `php artisan channels:autostart`
  - Default: pornește doar canalele care au `status=live` și nu rulează.
  - Opțiuni: `--dry-run`, `--limit=`, `--all-enabled` (atenție: pornește toate canalele enabled).

- Systemd unit (oneshot la boot): `scripts/systemd/iptv-panel-autostart.service`
  - Rulează: `channels:autostart --only-missing`

## Observații
- Pentru VOD 24/7: dacă există TS-uri encodate, se pornește în modul FIFO (fără restart când apar TS-uri noi).
- Dacă nu există TS-uri, face fallback la "encode+loop" (doar ca să nu stea oprit).
