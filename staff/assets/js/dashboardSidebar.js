// dashboardSidebar.js opens and closes the sidebar in components/dashboardSidebar.php

// open and close the sidebar
(() => {
    const shell = document.querySelector('.dashboard');
    const toggle = document.getElementById('sidebar-toggle');
    const mobile = window.matchMedia('(max-width: 780px)');
    const storageKey = 'dashboardSidebarCollapsed';

    // remember if the sidebar was collapsed
    try {
        if (localStorage.getItem(storageKey) === '1') shell.classList.add('sidebar-collapsed');
    } catch (e) {}

    const isOpen = () => mobile.matches
        ? shell.classList.contains('sidebar-open')
        : !shell.classList.contains('sidebar-collapsed');

    const sync = () => toggle.setAttribute('aria-expanded', isOpen() ? 'true' : 'false');

    const closeMobile = () => {
        shell.classList.remove('sidebar-open');
        sync();
    };

    // on mobile the button opens the sidebar, on desktop it collapses it
    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            shell.classList.toggle('sidebar-open');
        } else {
            const collapsed = shell.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (e) {}
        }
        sync();
    });

    document.getElementById('sidebar-backdrop').addEventListener('click', closeMobile);

    // escape closes the sidebar on mobile
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobile.matches && isOpen()) closeMobile();
    });

    mobile.addEventListener('change', closeMobile);
    sync();
})();
