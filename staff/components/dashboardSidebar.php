<?php
    // dashboardSidebar.php is the staff sidebar, each link only shows for the roles that can use it
    // customer service sees enquiries and registrations, marketing sees campaigns and gallery, admin sees everything
    // hiding a link is not enough on its own so pages also call requireRole() in helpers/auth.php
    $sidebarRole = $account['role'] ?? ($_SESSION['role'] ?? null);
    $sidebarCounts = $sidebarCounts ?? [];

    // each section has a heading and links with a key, label, page, roles and icon
    $sidebarSections = [
        ['Inbox', [
            ['enquiries',     'Enquiries',     'manageEnquiries.php', ['Admin', 'Customer Service'], 'envelope'],
            ['registrations', 'Registrations', 'registrations.php',   ['Admin', 'Customer Service'], 'person-plus'],
        ]],
        ['Website', [
            ['campaigns', 'Campaigns',        'manageCampaigns.php', ['Admin', 'Marketing'], 'megaphone'],
            ['events',    'Gallery & Events', 'manageEvents.php',    ['Admin', 'Marketing'], 'image'],
            ['content',   'Pages & Site info', 'manageContent.php',  ['Admin'],              'file-earmark-text'],
        ]],
        ['Admin', [
            ['accounts', 'Accounts',     'accounts.php',    ['Admin'], 'person-gear'],
            ['archive',  'Archive',      'archive.php',     ['Admin'], 'archive'],
            ['activity', 'Activity Log', 'activityLog.php', ['Admin'], 'clock-history'],
        ]],
    ];
?>

<aside class="dashboard-sidebar" id="dashboard-sidebar">
    <nav aria-label="Dashboard navigation">
        <!-- dashboard link -->
        <a class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php"
           <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
            <span class="sidebar-icon"><i class="bi bi-house" aria-hidden="true"></i></span>
            <span class="sidebar-label">Dashboard</span>
        </a>

        <!-- sections, skipped when none of their links show for this role -->
        <?php foreach ($sidebarSections as [$heading, $section]) : ?>
            <?php $links = array_filter($section, fn ($link) => $link[3] === null || in_array($sidebarRole, $link[3], true)); ?>
            <?php if ($links) : ?>
                <div class="sidebar-section">
                    <p class="sidebar-heading"><?= htmlspecialchars($heading) ?></p>
                    <?php foreach ($links as [$key, $label, $href, , $icon]) : ?>
                        <a class="sidebar-link <?= $activePage === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($href) ?>"
                           <?= $activePage === $key ? 'aria-current="page"' : '' ?>>
                            <span class="sidebar-icon"><i class="bi bi-<?= $icon ?>" aria-hidden="true"></i></span>
                            <span class="sidebar-label"><?= htmlspecialchars($label) ?></span>
                            <?php if (!empty($sidebarCounts[$key])) : ?>
                                <span class="sidebar-badge"><?= (int) $sidebarCounts[$key] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- dims the page behind the sidebar on mobile, clicking it closes the sidebar -->
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<script src="assets/js/dashboardSidebar.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboardSidebar.js') ?>"></script>
