# Raport (26.12.2025) — Redare dinamică fără restart (FIFO stream)

## Obiectiv
- Canalul NU se oprește.
- Când un TS nou este gata (după encodare), el intră automat în rotație și se va reda la finalul ciclului curent.
- EPG rămâne dinamic (endpoint-ul existent generează mereu 7 zile).

## Problema întâlnită
- Abordarea "concat list în FIFO" (scriere de linii `file '...ts'` într-un FIFO citit de `-f concat`) nu este stabilă pentru "stream continuu": FFmpeg tinde să aștepte EOF pe inputul de tip listă înainte să înceapă redarea.

## Soluția implementată (recomandată)
- FFmpeg citește un flux TS continuu dintr-un FIFO (bytes), iar un feeder concatenează fișierele `.ts` în ordinea playlist-ului.
- Avantaj: FFmpeg rămâne pornit, iar fișierele TS noi apar în următorul ciclu fără restart.

## Modificări în cod
- Comandă nouă: `channel:feed-stream` în [app/Console/Commands/FeedChannelStream.php](app/Console/Commands/FeedChannelStream.php)
  - Deschide `storage/app/streams/{channel}/play_stream.fifo` și scrie bytes din `video_{playlist_item_id}.ts` în buclă.
  - La fiecare ciclu re-citește playlist-ul și include fișiere TS noi.

- Engine: [app/Services/ChannelEngineService.php](app/Services/ChannelEngineService.php)
  - `generatePlayCommandFromFilesFifo()` folosește acum `play_stream.fifo` ca input.
  - La start pornește feeder-ul în fundal (`channel:feed-stream`).
  - La stop oprește feeder-ul înainte de a opri FFmpeg.

- Start looping (UI/API): [app/Http/Controllers/Admin/LiveChannelController.php](app/Http/Controllers/Admin/LiveChannelController.php)
  - Pornirea canalului în looping folosește FIFO mode când există TS-uri.

- Monitor coadă encodare: [app/Console/Commands/MonitorEncodingJobs.php](app/Console/Commands/MonitorEncodingJobs.php)
  - Marchează job-uri `running` ca `done/failed` pe baza PID + progress file.
  - Pornește automat următorul job `queued` per canal (ca să nu depindă de UI polling).

- Scheduler: [routes/console.php](routes/console.php)
  - `encoding:monitor` este acum programat să ruleze la fiecare minut.

## Cum rulează în producție
1) Scheduler (obligatoriu):
   - Cron: `* * * * * php /var/www/iptv-panel/artisan schedule:run >> /dev/null 2>&1`
   - sau `php artisan schedule:work` într-un service.
2) Pornești canalul din UI ca "24/7 looping".
   - Engine pornește FFmpeg + feeder automat.
3) Când un nou TS apare în `storage/app/streams/{channel}/video_{playlist_item_id}.ts`, acesta intră în rotație la următorul ciclu.

## Observații
- La concatenarea TS-urilor pot apărea avertismente de timestamp (non-monotonic DTS) dacă fișierele TS nu sunt perfect uniforme. Pentru encodările reale, de regulă este acceptabil; dacă e nevoie, se poate aplica o normalizare de timestamps în comanda FFmpeg (fără schimbare de UX).
