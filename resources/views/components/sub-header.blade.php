@php
  $serverId = request('server_id') ?? 'MQ';
  $servers = [
    'MQ' => 'Server 1',
    'Mg' => 'Server 2',
    'Mw' => 'Server 3',
  ];
@endphp

<div class="fox-subheader">
  <div class="fox-subheader-left">
    <span class="fox-subheader-label">>> Server</span>
    <select class="fox-server-select" onchange="onServerChange(this.value)">
      @foreach($servers as $value => $label)
        <option value="{{ $value }}" {{ $serverId === $value ? 'selected' : '' }}>{{ $label }}</option>
      @endforeach
    </select>
  </div>

  <div class="fox-subheader-right">
    <button class="fox-restart-btn" onclick="onRestartServer()">
      🔄 Restart
    </button>
  </div>
</div>

<script>
function onServerChange(serverId) {
  const url = new URL(window.location.href);
  url.searchParams.set('server_id', serverId);
  window.location.href = url.toString();
}

function onRestartServer() {
  if (confirm('Restart server?')) {
    // TODO: API call to restart
    console.log('Restarting server...');
  }
}
</script>
