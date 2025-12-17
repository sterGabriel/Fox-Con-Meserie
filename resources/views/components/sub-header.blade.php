<div class="fox-subheader">
  <div class="fox-subheader-left">
    <span class="fox-subheader-label">>> Server</span>
    <select class="fox-server-select" onchange="onServerChange(this.value)">
      <option value="1" selected>Server 1</option>
      <option value="2">Server 2</option>
      <option value="3">Server 3</option>
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
  console.log('Server changed to:', serverId);
  // TODO: Load server data
  location.href = '?server=' + serverId;
}

function onRestartServer() {
  if (confirm('Restart server?')) {
    console.log('Restarting server...');
    // TODO: API call to restart
  }
}
</script>
