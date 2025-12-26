<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Player' }}</title>
  <style>
    html, body { height: 100%; margin: 0; background: #0b1220; color: #fff; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    .wrap { height: 100%; display: flex; flex-direction: column; }
    .top { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,.10); display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .title { font-weight: 800; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .btn { background: rgba(255,255,255,.12); color: #fff; border: 0; padding: 8px 10px; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 12px; }
    .main { flex: 1; display:flex; align-items:center; justify-content:center; padding: 10px; }
    video { width: 100%; height: 100%; max-height: calc(100vh - 56px); background: #000; border-radius: 10px; }
    .msg { padding: 14px; color: rgba(255,255,255,.85); font-size: 13px; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div class="title">{{ $title ?? 'Player' }}</div>
    <div>
      <button class="btn" type="button" onclick="window.close()">Close</button>
    </div>
  </div>

  <div class="main">
    @if(!empty($error))
      <div class="msg">{{ $error }}</div>
    @else
      <video id="v" controls playsinline preload="metadata"></video>
    @endif
  </div>
</div>

@if(empty($error) && !empty($tsUrl))
  <script src="{{ asset('vendor/mpegts/mpegts.js') }}"></script>
  <script>
  (function(){
    const url = @json($tsUrl);
    const video = document.getElementById('v');
    if (!video) return;

    function playNative(){
      video.src = url;
      video.load();
      const p = video.play();
      if (p && typeof p.catch === 'function') p.catch(() => {});
    }

    if (window.mpegts && typeof window.mpegts.isSupported === 'function' && window.mpegts.isSupported()) {
      const p = window.mpegts.createPlayer({ type: 'mpegts', url: url, isLive: false }, { enableWorker: true, lazyLoad: false });
      p.attachMediaElement(video);
      p.load();
      const pr = video.play();
      if (pr && typeof pr.catch === 'function') pr.catch(() => {});
      return;
    }

    playNative();
  })();
  </script>
@endif
</body>
</html>
