# Redis (local) — setup și utilizare în IPTV Panel

## Scop
Redis rulează pe același server și este folosit pentru:
- queue (mai rapid decât database)
- cache
- locks (evită porniri duble / race conditions)

## Instalare (server)
```bash
sudo apt-get update
sudo apt-get install -y redis-server
sudo systemctl enable --now redis-server.service
redis-cli ping
```

## Setări Laravel (.env)
Recomandat:
```dotenv
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

## Verificare
- Queue worker: `systemctl status iptv-panel-queue.service`
- Scheduler: `systemctl status iptv-panel-schedule.service`
- Redis: `systemctl status redis-server.service`

## Note
- Folosim `predis/predis` (Composer), deci nu depindem de extensia PHP `redis`.
- Redis e local (127.0.0.1), nu e expus public.
