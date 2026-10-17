<?php
    // dashboardHeader.php is the header on every staff page
    // contains the workspace label, notifications bell and profile menu
    // $notifications comes from components/dashboard.php
    $notifications = $notifications ?? [];
?>

<header class="dashboard-header">
    <div class="dashboard-header-copy">
        <?php if (($activePage ?? '') === 'dashboard') : ?>
            <h1><?= htmlspecialchars($dashboardGreeting . ', ' . $dashboardName) ?></h1>
            <p><?= htmlspecialchars(date('l, j F Y')) ?></p>
        <?php else : ?>
            <div class="dashboard-header-context">
                <span class="header-context-mark" aria-hidden="true"></span>
                <span>Staff workspace</span>
                <span class="header-context-divider">/</span>
                <span class="header-context-role"><?= htmlspecialchars($account['role'] ?? 'Staff') ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-header-actions">
        <!-- Notifications and profile menu stay available throughout the portal. -->
        <div class="dashboard-notifications">
            <button type="button" class="header-icon-button notifications-toggle"
                    aria-label="Notifications" aria-haspopup="true" aria-expanded="false"
                    aria-controls="notifications-dropdown">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 0 0-5.5-6.84V3.5a1.5 1.5 0 0 0-3 0v.66A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2Z"/>
                </svg>
                <?php if ($notifications) : ?>
                    <span class="notification-badge"><?= count($notifications) > 9 ? '9+' : count($notifications) ?></span>
                <?php endif; ?>
            </button>

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
                        <li class="notification-item notification-<?= htmlspecialchars($note['level']) ?>" data-id="<?= htmlspecialchars($note['id']) ?>">
                            <<?= $noteTag ?> class="notification-body"<?= $note['link'] ? ' href="' . htmlspecialchars($note['link']) . '"' : ' type="button"' ?>>
                                <span class="notification-title"><?= htmlspecialchars($note['title']) ?></span>
                                <span class="notification-detail"><?= htmlspecialchars($note['detail']) ?></span>
                            </<?= $noteTag ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php include __DIR__ . '/profileMenu.php'; ?>
    </div>
</header>

<script src="assets/js/dashboardHeader.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboardHeader.js') ?>"></script>
