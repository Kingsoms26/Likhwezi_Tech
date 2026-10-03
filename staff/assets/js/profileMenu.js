// profileMenu.js runs the menu in components/profileMenu.php

// open and close the settings menu and profile popover, only one is open at a time
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('settings-menu-toggle');
    const menu = document.getElementById('settings-menu');
    const profile = document.getElementById('profile-popover');
    const openProfile = document.getElementById('profile-popover-open');

    const show = (panel) => {
        menu.hidden = panel !== menu;
        if (profile) profile.hidden = panel !== profile;
        toggle.setAttribute('aria-expanded', panel ? 'true' : 'false');
        if (panel) panel.querySelector('button, a').focus();
    };

    const isOpen = () => !menu.hidden || (profile && !profile.hidden);

    toggle.addEventListener('click', () => show(isOpen() ? null : menu));

    if (profile) {
        openProfile.addEventListener('click', () => show(profile));
        document.getElementById('profile-popover-back').addEventListener('click', () => show(menu));
        document.getElementById('profile-popover-close').addEventListener('click', () => {
            show(null);
            toggle.focus();
        });
    } else {
        // no account loaded so my profile goes to the full page instead
        openProfile.addEventListener('click', () => { window.location.href = 'profile.php'; });
    }

    // close when clicking outside the menu or pressing escape
    document.addEventListener('click', (event) => {
        if (isOpen() && !event.target.closest('.profile-menu')) show(null);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            show(null);
            toggle.focus();
        }
    });
});
