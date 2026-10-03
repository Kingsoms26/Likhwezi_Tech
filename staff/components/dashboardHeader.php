<?php
    // dashboardHeader.php is the header on every staff page
    // contains the logo, the notifications bell and the profile menu
    // $notifications comes from components/dashboard.php
    $notifications = $notifications ?? [];
?>

<header class="dashboard-header">
    <!-- logo, also opens and closes the sidebar -->
    <button type="button" class="dashboard-brand" id="sidebar-toggle"
            aria-controls="dashboard-sidebar" aria-expanded="true" aria-label="Toggle navigation">
        <img src="assets/images/logo-branding.jpeg" alt="">
        <span class="company-name">
            <span>Likhwezi</span>
            <span>Technologies</span>
        </span>
    </button>

    <!-- notifications and profile menu -->
    <div class="dashboard-header-actions">

        <!-- notifications button -->
        <div class="dashboard-notifications">
            <button type="button" class="header-icon-button notifications-toggle"
                    aria-label="Notifications" aria-haspopup="true" aria-expanded="false"
                    aria-controls="notifications-dropdown">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5.5-6.84V3.5a1.5 1.5 0 0 0-3 0v.66A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z"/>
                </svg>
                <?php if ($notifications) : ?>
                    <span class="notification-badge"><?= count($notifications) > 9 ? '9+' : count($notifications) ?></span>
                <?php endif; ?>
            </button>

            <!-- notifications dropdown, the script below keeps it up to date without a reload
                 tapping a notification or clear all hides it through dismissNotifications.php -->
            <div class="notifications-dropdown" id="notifications-dropdown" hidden
                 data-csrf="<?= htmlspecialchars(csrfToken()) ?>">
                <div class="notifications-header">
                    <p class="popover-label notifications-heading">Notifications</p>
                    <button type="button" class="notifications-clear"<?= $notifications ? '' : ' hidden' ?>>Clear all</button>
                </div>

                <p class="notifications-empty"<?= $notifications ? ' hidden' : '' ?>>You're all caught up.</p>

                <ul class="notifications-list"<?= $notifications ? '' : ' hidden' ?>>
                    <?php foreach ($notifications as $note) : ?>
                        <?php $noteTag = $note['link'] ? 'a' : 'button'; ?>
                        <li class="notification-item notification-<?= htmlspecialchars($note['level']) ?>"
                            data-id="<?= htmlspecialchars($note['id']) ?>">
                            <<?= $noteTag ?> class="notification-body"<?= $note['link'] ? ' href="' . htmlspecialchars($note['link']) . '"' : ' type="button"' ?>>
                                <span class="notification-title"><?= htmlspecialchars($note['title']) ?></span>
                                <span class="notification-detail"><?= htmlspecialchars($note['detail']) ?></span>
                            </<?= $noteTag ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- profile menu -->
        <?php include __DIR__ . '/profileMenu.php'; ?>

    </div>
</header>

<script>
    // open and close the notifications dropdown
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.querySelector('.notifications-toggle');
        const dropdown = document.getElementById('notifications-dropdown');

        const setOpen = (open) => {
            dropdown.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', () => setOpen(dropdown.hidden));

        // close when clicking outside, this still works when the clicked item was just removed
        document.addEventListener('click', (event) => {
            const inside = event.composedPath().includes(dropdown.parentElement);
            if (!dropdown.hidden && !inside) setOpen(false);
        });

        // escape closes the dropdown
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dropdown.hidden) {
                setOpen(false);
                toggle.focus();
            }
        });

        const list = dropdown.querySelector('.notifications-list');
        const clearButton = dropdown.querySelector('.notifications-clear');
        const empty = dropdown.querySelector('.notifications-empty');

        // notifications dismissed on this page so a slow poll cannot bring them back
        const dismissedHere = new Set();

        // update the badge, clear all and the empty message to match the number of notifications
        const syncCount = (pulse) => {
            const count = list.querySelectorAll('.notification-item').length;
            let badge = toggle.querySelector('.notification-badge');

            if (count && !badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                toggle.append(badge);
            }
            if (badge) {
                if (count) badge.textContent = count > 9 ? '9+' : count;
                else badge.remove();
            }
            if (badge && count && pulse) {
                badge.classList.remove('is-new');
                void badge.offsetWidth; // restart the animation
                badge.classList.add('is-new');
            }

            toggle.setAttribute('aria-label', count ? `Notifications (${count} new)` : 'Notifications');
            list.hidden = !count;
            clearButton.hidden = !count;
            empty.hidden = count > 0;
        };

        // build one notification the same way as the php above
        const buildItem = (note) => {
            const item = document.createElement('li');
            item.className = 'notification-item notification-' + note.level;
            item.dataset.id = note.id;

            const body = document.createElement(note.link ? 'a' : 'button');
            body.className = 'notification-body';
            if (note.link) body.href = note.link;
            else body.type = 'button';

            const title = document.createElement('span');
            title.className = 'notification-title';
            title.textContent = note.title;

            const detail = document.createElement('span');
            detail.className = 'notification-detail';
            detail.textContent = note.detail;

            body.append(title, detail);
            item.append(body);
            return item;
        };

        // redraw the list only if something changed
        const render = (notes) => {
            notes = notes.filter((note) => !dismissedHere.has(note.id));

            const current = [...list.querySelectorAll('.notification-item')].map((item) => item.dataset.id);
            if (current.join() === notes.map((note) => note.id).join()) return;

            // keep focus on the same notification while moving through the list
            const focusedID = document.activeElement?.closest?.('.notification-item')?.dataset.id;
            const hasNew = notes.some((note) => !current.includes(note.id));

            list.replaceChildren(...notes.map(buildItem));
            if (focusedID) list.querySelector(`[data-id="${focusedID}"] .notification-body`)?.focus();
            syncCount(hasNew);
        };

        // tell the server then remove the notifications straight away
        // keepalive lets the request finish when the tap also opens another page
        const dismiss = (items) => {
            const body = new FormData();
            body.append('csrfToken', dropdown.dataset.csrf);
            items.forEach((item) => {
                body.append('ids[]', item.dataset.id);
                dismissedHere.add(item.dataset.id);
            });
            fetch('dismissNotifications.php', { method: 'POST', body, keepalive: true })
                .catch(() => {});

            items.forEach((item) => item.remove());
            syncCount(false);
        };

        // clear all or tap one notification
        dropdown.addEventListener('click', (event) => {
            if (event.target.closest('.notifications-clear')) {
                dismiss([...list.querySelectorAll('.notification-item')]);
                return;
            }
            const item = event.target.closest('.notification-item');
            if (item) dismiss([item]);
        });

        // check for new notifications every 10 seconds while the tab is open and when the user comes back to it
        // stops if the session has ended
        const POLL_MS = 10000;
        let timer = null;
        let stopped = false;

        const poll = async () => {
            clearTimeout(timer);
            if (stopped || document.hidden) return;

            try {
                const response = await fetch('notifications.php', {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });

                if (response.status === 401) {
                    stopped = true;
                    return;
                }

                const type = response.headers.get('Content-Type') || '';
                if (response.ok && type.includes('application/json')) {
                    render((await response.json()).notifications || []);
                }
            } catch (error) {
                // offline or the server is busy so try again next time
            }

            timer = setTimeout(poll, POLL_MS);
        };

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) poll();
        });

        // opening the dropdown also checks so it is always up to date
        toggle.addEventListener('click', () => {
            if (!dropdown.hidden) poll();
        });

        timer = setTimeout(poll, POLL_MS);
    });
</script>
