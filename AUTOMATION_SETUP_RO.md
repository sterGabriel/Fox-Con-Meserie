# Automatizare completă (server) — IPTV Panel

Scop: totul să ruleze singur, fără să depindă de UI deschis.

## 1) Cerințe
- PHP 8.4 (în repo folosim `/usr/bin/php8.4` în systemd)
- FFmpeg instalat și în PATH (ex: `/usr/bin/ffmpeg`)
- Permisiuni corecte pe `storage/` (scriere pentru userul care rulează serviciile)

## 2) Scheduler (obligatoriu)
Scheduler-ul rulează:
- `encoding:monitor` (la fiecare minut) — marchează job-uri terminate și pornește automat următorul job queued per canal
- `videos:sync-metadata` (hourly)

Recomandare: `schedule:work` ca systemd service.
Fișier: `scripts/systemd/iptv-panel-schedule.service`

### Instalare
```bash
sudo cp /var/www/iptv-panel/scripts/systemd/iptv-panel-schedule.service /etc/systemd/system/iptv-panel-schedule.service
sudo systemctl daemon-reload
sudo systemctl enable --now iptv-panel-schedule.service
```

### Verificare
```bash
sudo systemctl status iptv-panel-schedule.service
sudo journalctl -u iptv-panel-schedule.service -f
```

## 3) Queue worker (recomandat)
Dacă folosești job-uri Laravel în queue (driver `database`), pornește și worker.
Fișier: `scripts/systemd/iptv-panel-queue.service`

### Instalare
```bash
sudo cp /var/www/iptv-panel/scripts/systemd/iptv-panel-queue.service /etc/systemd/system/iptv-panel-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now iptv-panel-queue.service
```

### Verificare
```bash
sudo systemctl status iptv-panel-queue.service
sudo journalctl -u iptv-panel-queue.service -f
```

## 4) Streaming fără restart (automat)
Când pornești canalul în modul 24/7 looping (din UI):
- engine pornește FFmpeg + feeder automat
- feeder-ul scrie TS bytes în FIFO `storage/app/streams/{channel}/play_stream.fifo`
- dacă apare un TS nou în `storage/app/streams/{channel}/video_{playlist_item_id}.ts`, el intră în rotație la următorul ciclu fără restart

## 5) Cron (alternativ, dacă nu vrei schedule:work)
Poți folosi cron clasic, dar e mai puțin robust decât systemd.
```bash
* * * * * cd /var/www/iptv-panel && /usr/bin/php8.4 artisan schedule:run >> /dev/null 2>&1
```

## 6) Note importante
- În fișierele systemd, schimbă `User=`/`Group=` dacă nu rulezi ca `www-data`.
- Dacă `php` nu e `/usr/bin/php8.4`, schimbă `ExecStart`.
- Permisiuni: `storage/` și `bootstrap/cache` trebuie să fie writable.
