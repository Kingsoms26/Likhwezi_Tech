<?php
    // adminDashboard.php is the admin dashboard
    // contains the summary cards, open enquiries, registrations by interest and campaign progress

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    require __DIR__ . '/helpers/dashboardData.php';

    // fetch the enquiries, campaigns, funds and registrations
    $stats = getDashboardStats($conn);
    $enquiryCounts = getEnquiryCounts($conn);
    $registrationStats = getRegistrationStats($conn);
    $eventCounts = getUpcomingEventCounts($conn);
    $upcomingEvents = getUpcomingEvents($conn, 1);

    $pageTitle = 'Dashboard';
    $activePage = 'dashboard';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<!-- admin content, the wrapper keeps the admin css scoped to this page -->
<div class="admin-dashboard">

    <!-- Four compact headline metrics, following the reference dashboard's top strip. -->
    <section class="dashboard-cards row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-0" aria-label="Key workspace metrics">
        <?php
        $cardTitle = 'Active Enquiries';
        $cardValue = $stats['activeEnquiries'];
        $cardMeta = $enquiryCounts['new'] . ' new, ' . $enquiryCounts['contacted'] . ' contacted';
        $cardClass = 'enquiries-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
        <?php
        $cardTitle = 'New Registrations';
        $cardValue = $registrationStats['lastSevenDays'];
        $cardMeta = 'In the last 7 days';
        $cardClass = 'registrations-recent-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
        <?php
        $cardTitle = 'Active Campaigns';
        $cardValue = $stats['activeCampaigns'];
        $cardMeta = 'Currently active';
        $cardClass = 'campaigns-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
        <?php
        $cardTitle = 'Total Funds Raised';
        $cardValue = 'R' . number_format($stats['fundsRaised'], 2);
        $cardMeta = 'Across all campaigns';
        $cardClass = 'donations-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
    </section>

    <?php
        $spotlightSlides = [
            [
                'eyebrow' => 'ENQUIRIES',
                'title' => 'Keep every conversation moving.',
                'description' => 'Review open requests, check their status and make sure nothing important gets buried.',
                'value' => (string) $stats['activeEnquiries'],
                'label' => 'open enquiries',
                'href' => 'manageEnquiries.php',
                'action' => 'Review enquiries',
                'icon' => 'envelope-paper',
                'tone' => 'enquiries'
            ],
            [
                'eyebrow' => 'CAMPAIGNS',
                'title' => 'See which campaigns need attention.',
                'description' => 'Monitor active fundraising work and open the campaign overview to review progress.',
                'value' => (string) $stats['activeCampaigns'],
                'label' => 'active campaigns',
                'href' => 'manageCampaigns.php',
                'action' => 'View campaigns',
                'icon' => 'bullseye',
                'tone' => 'campaigns'
            ],
            [
                'eyebrow' => 'REGISTRATIONS',
                'title' => 'See who joined this week.',
                'description' => 'Review participant sign-ups and programme interests before planning the next steps.',
                'value' => (string) $registrationStats['lastSevenDays'],
                'label' => 'registrations this week',
                'href' => 'registrations.php',
                'action' => 'View registrations',
                'icon' => 'person-plus',
                'tone' => 'registrations'
            ]
        ];
        $spotlightEnquiryStats = $enquiryCounts;
        // Recent activity is global for Admins and restricted to the signed-in account for Staff.
        $activityFilters = [
            'account' => ($account['role'] ?? '') === 'Admin' ? 0 : (int) ($account['accountID'] ?? 0),
            'category' => '', 'search' => '', 'from' => '', 'to' => '', 'page' => 1,
        ];
        $recentActivity = getActivityLog($conn, $activityFilters, 5)['rows'];
        $nextEvent = $upcomingEvents[0] ?? null;
        $featuredCampaign = $stats['campaigns'][0] ?? null;
        $dashboardFeatureCards = [
            [
                'key' => 'enquiries', 'title' => 'Enquiries', 'eyebrow' => 'INBOX',
                'value' => (string) $stats['activeEnquiries'], 'unit' => 'open',
                'summary' => $enquiryCounts['new'] . ' new · ' . $enquiryCounts['contacted'] . ' contacted',
                'icon' => 'envelope', 'visual' => 'enquiries', 'href' => 'manageEnquiries.php', 'action' => 'View enquiries',
                'segments' => [
                    ['key' => 'new', 'label' => 'New', 'value' => (int) $enquiryCounts['new']],
                    ['key' => 'contacted', 'label' => 'Contacted', 'value' => (int) $enquiryCounts['contacted']],
                    ['key' => 'closed', 'label' => 'Resolved', 'value' => (int) $enquiryCounts['closed']],
                ],
            ],
            [
                'key' => 'events', 'title' => 'Upcoming Events', 'eyebrow' => 'CALENDAR',
                'value' => (string) $eventCounts['upcoming'], 'unit' => 'scheduled',
                'summary' => $nextEvent ? 'Keep the next event on your radar.' : 'No upcoming events scheduled.',
                'detail' => $nextEvent['name'] ?? '', 'detailMeta' => $nextEvent ? (($nextEvent['eventDate'] ? date('j M Y', strtotime($nextEvent['eventDate'])) : 'Date to be confirmed')) : '',
                'icon' => 'calendar-event', 'visual' => 'events', 'href' => 'manageEvents.php', 'action' => 'Manage events',
            ],
            [
                'key' => 'registrations', 'title' => 'Registrations', 'eyebrow' => 'COMMUNITY',
                'value' => (string) $registrationStats['total'], 'unit' => 'total',
                'summary' => $registrationStats['lastSevenDays'] . ' received in the last 7 days.',
                'recentValue' => (string) $registrationStats['lastSevenDays'],
                'icon' => 'people', 'visual' => 'registrations', 'href' => 'registrations.php', 'action' => 'View registrations',
            ],
            [
                'key' => 'campaigns', 'title' => 'Campaign Progress', 'eyebrow' => 'FUNDRAISING',
                'value' => (string) $stats['activeCampaigns'], 'unit' => 'active campaigns',
                'summary' => $featuredCampaign ? 'Your leading campaign, at a glance.' : 'No active campaigns to chart.',
                'progressLabel' => $featuredCampaign['name'] ?? '', 'progress' => $featuredCampaign['percentage'] ?? 0,
                'icon' => 'graph-up-arrow', 'visual' => 'campaigns', 'href' => 'manageCampaigns.php', 'action' => 'Manage campaigns',
            ],
        ];
        include __DIR__ . '/components/dashboardSpotlight.php';
        include __DIR__ . '/components/dashboardFeatureCards.php';
    ?>

    <section class="row g-3 mb-3 align-items-start">

        <!-- open enquiries -->
        <section class="card dashboard-panel enquiries-panel col-12 col-lg-6">

            <div class="panel-header">
                <h2>Open Enquiries</h2>
                <a class="panel-button" href="manageEnquiries.php">View All</a>
            </div>

            <div class="panel-body">

                <div class="panel-summary">
                    <div class="summary-value">
                        <?= htmlspecialchars($stats['activeEnquiries']) ?>
                    </div>
                    <p>Open enquiries requiring attention</p>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 dashboard-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!$stats['recentEnquiries']) : ?>
                                <tr>
                                    <td colspan="3">No open enquiries.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($stats['recentEnquiries'] as $enquiry) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($enquiry['subject']) ?></td>
                                    <td><?= htmlspecialchars($enquiry['customer']) ?></td>
                                    <td><?= htmlspecialchars($enquiry['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </section>

        <!-- registrations by interest -->
        <section class="card dashboard-panel registration-interest-panel col-12 col-lg-6">

            <div class="panel-header">
                <h2>Registration by Interest</h2>
                <a class="panel-button" href="registrations.php">View Report</a>
            </div>

            <div class="panel-body panel-padding">

                <?php if (!$stats['registrationInterest']) : ?>
                    <p>No registrations yet.</p>
                <?php endif; ?>

                <?php foreach ($stats['registrationInterest'] as $interest) : ?>

                    <div class="interest-item">

                        <div class="interest-heading">
                            <span><?= htmlspecialchars($interest['name']) ?></span>
                            <span><?= htmlspecialchars($interest['percentage']) ?>%</span>
                        </div>

                        <div class="progress dashboard-interest-progress">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                aria-label="<?= htmlspecialchars($interest['name']) ?> registrations"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?= max(0, min(100, (float) $interest['percentage'])) ?>"
                                style="width: <?= max(0, min(100, (float) $interest['percentage'])) ?>%;"
                            ></div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    </section>

    <!-- campaign progress -->
    <section class="card dashboard-panel campaigns-panel">

        <div class="panel-header">
            <h2>Campaigns</h2>
            <a class="panel-button" href="manageCampaigns.php">View All</a>
        </div>

        <div class="panel-body panel-padding">

            <?php if (!$stats['campaigns']) : ?>
                <p>No active campaigns.</p>
            <?php endif; ?>

            <?php foreach ($stats['campaigns'] as $campaign) : ?>

                <div class="campaign-row">

                    <div class="campaign-header">
                        <span><?= htmlspecialchars($campaign['name']) ?></span>
                        <span><?= htmlspecialchars($campaign['percentage']) ?>%</span>
                    </div>

                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?= max(0, min(100, (float) $campaign['percentage'])) ?>%;"></div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

</div>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>
