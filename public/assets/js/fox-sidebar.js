(function () {
    const subButtons = document.querySelectorAll('[data-fox="submenu"]');
    subButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.classList.toggle('is-open');
            const next = btn.nextElementSibling;
            if (next && next.classList.contains('fox-sub')) {
                next.classList.toggle('is-open');
            }
        });
    });

    // Optional: sidebar collapse (simple)
    const toggle = document.querySelector('[data-fox="sidebar-toggle"]');
    const sidebar = document.querySelector('.fox-sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-collapsed');
            // Add collapsed styling if needed
        });
    }
})();
