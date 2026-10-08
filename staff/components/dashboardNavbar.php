<?php
    // dashboardNavbar.php is the staff navbar along the bottom of the screen, each link only shows for the roles that can use it
    // customer service sees enquiries and registrations, marketing sees campaigns and gallery, admin sees everything
    // hiding a link is not enough on its own so pages also call requireRole() in helpers/auth.php
    $navbarRole = $account['role'] ?? ($_SESSION['role'] ?? null);
    $navbarCounts = $navbarCounts ?? [];

    // each link has a key, label, page, roles and icon, null roles means everyone
    $navbarLinks = [
        ['dashboard',     'Dashboard',         'index.php',           null,                          'house'],
        ['enquiries',     'Enquiries',         'manageEnquiries.php', ['Admin', 'Customer Service'], 'envelope'],
        ['registrations', 'Registrations',     'registrations.php',   ['Admin', 'Customer Service'], 'person-plus'],
        ['campaigns',     'Campaigns',         'manageCampaigns.php', ['Admin', 'Marketing'],        'megaphone'],
        ['events',        'Gallery & Events',  'manageEvents.php',    ['Admin', 'Marketing'],        'image'],
        ['content',       'Pages & Site info', 'manageContent.php',   ['Admin'],                     'file-earmark-text'],
        ['announcements', 'Announcements',     'announcements.php',   ['Admin'],                     'broadcast'],
        ['accounts',      'Accounts',          'accounts.php',        ['Admin'],                     'person-gear'],
        ['archive',       'Archive',           'archive.php',         ['Admin'],                     'archive'],
        ['activity',      'Activity Log',      'activityLog.php',     ['Admin'],                     'clock-history'],
    ];
    $navbarLinks = array_values(array_filter($navbarLinks, fn ($link) => $link[3] === null || in_array($navbarRole, $link[3], true)));

    // on small screens only the first three links fit, the rest go in the more menu
    $navbarExtra = count($navbarLinks) > 4 ? array_slice($navbarLinks, 3) : [];
?>

<nav class="dashboard-navbar" aria-label="Dashboard navigation">
    <ul class="navbar-links">
        <?php foreach ($navbarLinks as $i => [$key, $label, $href, , $icon]) : ?>
            <li class="<?= $navbarExtra && $i >= 3 ? 'navbar-extra' : '' ?>">
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

        <!-- more menu, only shows on small screens -->
        <?php if ($navbarExtra) : ?>
            <?php $extraActive = in_array($activePage, array_column($navbarExtra, 0), true); ?>
            <li class="navbar-more dropup">
                <button type="button" class="navbar-link <?= $extraActive ? 'active' : '' ?>"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="navbar-icon"><i class="bi bi-three-dots" aria-hidden="true"></i></span>
                    <span class="navbar-label">More</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end navbar-more-menu">
                    <?php foreach ($navbarExtra as [$key, $label, $href, , $icon]) : ?>
                        <li>
                            <a class="dropdown-item <?= $activePage === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($href) ?>"
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
