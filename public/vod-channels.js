// State
const state = {
    serverId: "1",
    pageSize: 60,
    search: "",
    rows: [],
    pendingDeleteId: null,
};

const $ = (id) => document.getElementById(id);

function openModal(modalId) {
    $("modalBackdrop").hidden = false;
    $(modalId).hidden = false;
}
function closeModal(modalId) {
    $("modalBackdrop").hidden = true;
    $(modalId).hidden = true;
    state.pendingDeleteId = null;
}

function closeAllDropdowns() {
    document.querySelectorAll(".dropdown-menu").forEach(m => (m.style.display = "none"));
}

function toggleDropdown(menuEl) {
    const isOpen = menuEl.style.display === "block";
    closeAllDropdowns();
    menuEl.style.display = isOpen ? "none" : "block";
}

function renderKpis(kpi) {
    $("kpiTotalChannels").textContent = kpi.totalChannels ?? 0;
    $("kpiActiveChannels").textContent = kpi.activeChannels ?? 0;
    $("kpiPassiveChannels").textContent = kpi.passiveChannels ?? 0;
    $("kpiTotalVideo").textContent = kpi.totalVideo ?? 0;
    $("kpiTotalSpace").textContent = kpi.totalSpace ?? "0";
    $("kpiFreeSpace").textContent = kpi.freeSpace ?? "0";
}

function rowHtml(row) {
    const statusDot = row.statusOk ? "dot-green" : "dot-red";

    // If disabled: hide stop button
    const stopBtn = row.isDisabled
        ? ""
        : `<button class="icon-btn stop" data-act="row-stop" title="Stop">■</button>`;

    return `
    <tr data-id="${row.id}">
      <td class="name-cell">
        <div class="name-main">${escapeHtml(row.name)}</div>
      </td>

      <td>
        <div class="pill-row">
          <span class="pill pill-blue">${row.transcodingA ?? 0}</span>
          <span class="pill pill-blue">${row.transcodingB ?? 0}</span>
          <span class="pill pill-pink">${row.transcodingC ?? 0}</span>
        </div>
      </td>

      <td><span class="pill pill-yellow" title="${escapeHtml(row.playing ?? "")}">${escapeHtml(row.playing ?? "-")}</span></td>
      <td><span class="pill pill-gray">${escapeHtml(row.bitrate ?? "-")}</span></td>
      <td><span class="pill pill-gray">${escapeHtml(row.uptime ?? "-")}</span></td>
      <td><span class="dot ${statusDot}"></span></td>
      <td><span class="epg-tag">${escapeHtml(row.epg ?? "EPG")}</span></td>
      <td class="mono">${escapeHtml(row.size ?? "-")}</td>
      <td class="mono">${escapeHtml(row.totalTime ?? "-")}</td>

      <td>
        <div class="dropdown">
          <button class="dropdown-btn" data-act="events-toggle">
            Actions <span class="caret">▼</span>
          </button>
          <div class="dropdown-menu">
            <button data-act="ev-create-video">Create Video</button>
            <button data-act="ev-edit-playlist">Edit Playlist (${row.playlistCount ?? 0})</button>
            <button data-act="ev-edit-video-epg">Edit Video Epg</button>
            <button data-act="ev-epg-link">Channel Epg Link</button>
            <button data-act="ev-converted-videos">Converted Videos (${row.convertedCount ?? 0})</button>
            <button data-act="ev-send-message">Send Message</button>
            <button data-act="ev-error-videos">Error Videos (${row.errorCount ?? 0})</button>
          </div>
        </div>
      </td>

      <td>
        <div class="actions-3">
          ${stopBtn}
          <button class="icon-btn edit" data-act="row-edit" title="Edit">✎</button>
          <button class="icon-btn del" data-act="row-delete" title="Delete">✕</button>
        </div>
      </td>
    </tr>
  `;
}

function renderTable(rows) {
    $("vodTableBody").innerHTML = rows.map(rowHtml).join("");
}

function escapeHtml(s) {
    return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

// Load page data from API
async function loadPage() {
    try {
        const params = new URLSearchParams({
            serverId: state.serverId,
            pageSize: state.pageSize,
            search: state.search,
        });

        const res = await fetch(`/api/vod/channels?${params}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        renderKpis(data.kpi);
        renderTable(data.rows);
    } catch (error) {
        console.error("Failed to load page:", error);
        renderTable([]);
    }
}

// Handle all actions
async function handleAction(act, rowId) {
    const serverId = state.serverId;

    try {
        switch (act) {
            // Toolbar
            case "stop-all":
                await fetch(`/api/vod/server/${serverId}/stop-all`, { method: "POST" });
                return loadPage();

            case "start-all":
                await fetch(`/api/vod/server/${serverId}/start-all`, { method: "POST" });
                return loadPage();

            case "channels-epg":
                location.href = `/vod/channels-epg?serverId=${serverId}`;
                return;

            case "fast-channel":
                location.href = `/vod/fast-channel?serverId=${serverId}`;
                return;

            case "send-message":
                openModal("modalSendMessage");
                return;

            // Row actions
            case "row-stop":
                await fetch(`/api/vod/channels/${rowId}/stop`, { method: "POST" });
                return loadPage();

            case "row-edit":
                location.href = `/vod-channels/${rowId}/edit?serverId=${serverId}`;
                return;

            case "row-delete":
                state.pendingDeleteId = rowId;
                $("deleteText").textContent = "Delete this channel?";
                openModal("modalConfirmDelete");
                return;

            // Events dropdown
            case "ev-create-video":
                location.href = `/vod/create-video?serverId=${serverId}&channelId=${rowId}`;
                return;

            case "ev-edit-playlist":
                location.href = `/vod/playlists?serverId=${serverId}&channelId=${rowId}`;
                return;

            case "ev-edit-video-epg":
                location.href = `/vod/video-epg?serverId=${serverId}&channelId=${rowId}`;
                return;

            case "ev-epg-link":
                const res = await fetch(`/api/vod/channels/${rowId}/epg-link`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const { url } = await res.json();
                navigator.clipboard.writeText(url);
                alert("EPG link copied to clipboard!");
                return;

            case "ev-converted-videos":
                location.href = `/vod/converted?serverId=${serverId}&channelId=${rowId}`;
                return;

            case "ev-error-videos":
                location.href = `/vod/errors?serverId=${serverId}&channelId=${rowId}`;
                return;
        }
    } catch (error) {
        console.error("Action failed:", error);
        alert("Error: " + error.message);
    }
}

function bindEvents() {
    // Toolbar
    $("btnStopAll").addEventListener("click", () => handleAction("stop-all"));
    $("btnStartAll").addEventListener("click", () => handleAction("start-all"));
    $("btnChannelsEpg").addEventListener("click", () => handleAction("channels-epg"));
    $("btnFastChannel").addEventListener("click", () => handleAction("fast-channel"));
    $("btnSendMessage").addEventListener("click", () => handleAction("send-message"));

    // New channel
    $("btnNewChannel").addEventListener("click", () => {
        location.href = `/vod-channels/create?serverId=${state.serverId}`;
    });

    // Server change
    $("serverSelect").addEventListener("change", (e) => {
        state.serverId = e.target.value;
        loadPage();
    });

    // Page size
    $("pageSize").addEventListener("change", (e) => {
        state.pageSize = Number(e.target.value);
        loadPage();
    });

    // Search
    $("searchInput").addEventListener("input", (e) => {
        state.search = e.target.value.trim();
        loadPage();
    });

    // Table delegated clicks
    $("vodTable").addEventListener("click", (e) => {
        const tr = e.target.closest("tr");
        const rowId = tr?.dataset?.id;
        const act = e.target?.dataset?.act;

        // Dropdown toggle
        if (act === "events-toggle") {
            const menu = e.target.closest(".dropdown")?.querySelector(".dropdown-menu");
            if (menu) toggleDropdown(menu);
            return;
        }

        // Button click
        if (act && rowId) {
            closeAllDropdowns();
            handleAction(act, rowId);
        }
    });

    // Click outside closes dropdown
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".dropdown")) closeAllDropdowns();
    });

    // Modals
    $("modalBackdrop").addEventListener("click", () => {
        closeModal("modalConfirmDelete");
        closeModal("modalSendMessage");
    });

    $("btnCancelDelete").addEventListener("click", () => closeModal("modalConfirmDelete"));
    $("btnConfirmDelete").addEventListener("click", async () => {
        const id = state.pendingDeleteId;
        if (!id) return closeModal("modalConfirmDelete");
        try {
            await fetch(`/api/vod/channels/${id}`, { method: "DELETE" });
            closeModal("modalConfirmDelete");
            loadPage();
        } catch (error) {
            console.error("Delete failed:", error);
            alert("Delete failed: " + error.message);
        }
    });

    $("btnCancelMessage").addEventListener("click", () => closeModal("modalSendMessage"));
    $("btnConfirmMessage").addEventListener("click", async () => {
        const msg = $("messageText").value.trim();
        if (!msg) return alert("Message cannot be empty");
        try {
            await fetch(`/api/vod/server/${state.serverId}/send-message`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ message: msg }),
            });
            $("messageText").value = "";
            closeModal("modalSendMessage");
            loadPage();
        } catch (error) {
            console.error("Send failed:", error);
            alert("Send failed: " + error.message);
        }
    });
}

// Initialize
bindEvents();
loadPage();
