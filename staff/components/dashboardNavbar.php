<?php
    // dashboardNavbar.php is the shared staff top navigation; each link only shows for roles that can use it
    // staff see enquiries, registrations, campaigns and gallery, admin sees everything
    // hiding a link is not enough on its own so pages also call requireRole() in helpers/auth.php
    $navbarRole = $account['role'] ?? ($_SESSION['role'] ?? null);
    $navbarCounts = $navbarCounts ?? [];
    $navbarFloating = $navbarFloating ?? false;

    // each link has a key, label, page, roles and icon, null roles means everyone
    $navbarLinks = [
        ['dashboard',     'Dashboard',         'index.php',           null,               'house'],
        ['enquiries',     'Enquiries',         'manageEnquiries.php', ['Admin', 'Staff'], 'envelope'],
        ['registrations', 'Registrations',     'registrations.php',   ['Admin', 'Staff'], 'person-plus'],
        ['campaigns',     'Campaigns',         'manageCampaigns.php', ['Admin', 'Staff'], 'megaphone'],
        ['events',        'Gallery & Events',  'manageEvents.php',    ['Admin', 'Staff'], 'image'],
        ['content',       'Pages & Site info', 'manageContent.php',   ['Admin'],          'file-earmark-text'],
        ['announcements', 'Announcements',     'announcements.php',   ['Admin'],          'broadcast'],
        ['accounts',      'Accounts',          'accounts.php',        ['Admin'],          'person-gear'],
        ['archive',       'Archive',           'archive.php',         ['Admin'],          'archive'],
        ['activity',      'Activity Log',      'activityLog.php',     ['Admin'],          'clock-history'],
    ];
    $navbarLinks = array_values(array_filter($navbarLinks, fn ($link) => $link[3] === null || in_array($navbarRole, $link[3], true)));

    // keep the primary links visible in the shared top navigation
    $navbarPrimaryCount = $navbarFloating ? ($navbarRole === 'Admin' ? 4 : 5) : 3;
    $navbarExtra = count($navbarLinks) > ($navbarFloating ? 5 : 4)
        ? array_slice($navbarLinks, $navbarPrimaryCount)
        : [];
    // Floating dashboard navs put every role-available destination in the mobile three-dot menu.
    $navbarExtraKeys = array_column($navbarExtra, 0);
    $navbarMoreLinks = $navbarFloating ? $navbarLinks : $navbarExtra;
?>

<nav class="dashboard-navbar<?= $navbarFloating ? ' dashboard-navbar-floating' : '' ?>" aria-label="Dashboard navigation">
    <ul class="navbar-links">
        <?php foreach ($navbarLinks as $i => [$key, $label, $href, , $icon]) : ?>
            <li class="<?= $navbarExtra && $i >= $navbarPrimaryCount ? 'navbar-extra' : '' ?>">
                <a class="navbar-link <?= $activePage === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($href) ?>"
                   <?= $activePage === $key ? 'aria-current="page"' : '' ?>>
                    <span class="navbar-icon">
                        <i class="bi bi-<?= $icon ?>" aria-hidden="true"></i>
                        <?php if (!empty($navbarCounts[$key])) : ?>
                            <span class="navbar-badge"><?= (int) $navbarCounts[$key] ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="navbar-label"><?= htmlspecialchars($label) ?></span>
                </a>
            </li>
        <?php endforeach; ?>

        <!-- More menu keeps additional role-specific destinations accessible -->
        <?php if ($navbarFloating || $navbarExtra) : ?>
            <?php $extraActive = in_array($activePage, array_column($navbarMoreLinks, 0), true); ?>
            <li class="navbar-more dropup<?= $navbarExtra ? '' : ' navbar-more-mobile-only' ?>">
                <button type="button" class="navbar-link <?= $extraActive ? 'active' : '' ?>"
                        data-bs-toggle="dropdown" aria-label="Open dashboard menu" aria-haspopup="true" aria-expanded="false">
                    <span class="navbar-icon"><i class="bi bi-three-dots" aria-hidden="true"></i></span>
                    <span class="navbar-label">More</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end navbar-more-menu">
                    <?php foreach ($navbarMoreLinks as [$key, $label, $href, , $icon]) : ?>
                        <?php $mobileOnly = $navbarFloating && !in_array($key, $navbarExtraKeys, true); ?>
                        <li>
                            <a class="dropdown-item<?= $mobileOnly ? ' navbar-mobile-only' : '' ?> <?= $activePage === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($href) ?>"
                               <?= $activePage === $key ? 'aria-current="page"' : '' ?>>
                                <i class="bi bi-<?= $icon ?>" aria-hidden="true"></i>
                                <?= htmlspecialchars($label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
</nav>
