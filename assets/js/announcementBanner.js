// announcementBanner.js hides the announcements a visitor has closed, see includes/components/announcementBanner.php
// the closed ones are remembered in this browser by a key that changes when the announcement is edited

(() => {
    const STORAGE_KEY = 'closedAnnouncements';
    const banners = [...document.querySelectorAll('.site-announcement')];

    // storage can be blocked in private windows so every read and write is wrapped
    let closed = [];
    try {
        closed = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    } catch (error) {
        closed = [];
    }

    // forget announcements that no longer show so the list does not keep growing
    const current = banners.map((banner) => banner.dataset.key);
    closed = closed.filter((key) => current.includes(key));

    const save = () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(closed));
        } catch (error) {
            // nothing to do, the banner just shows again next visit
        }
    };

    banners.forEach((banner) => {
        if (closed.includes(banner.dataset.key)) {
            banner.remove();
            return;
        }

        banner.querySelector('.site-announcement-close').addEventListener('click', () => {
            closed.push(banner.dataset.key);
            save();
            banner.remove();
        });
    });

    save();
})();
