<?php
    // staffDashboard.php is the staff dashboard
    // contains the enquiry, registration, campaign, donation and event cards
    // then open enquiries, recent cym registrations, campaign progress and upcoming events

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/enquiryData.php';
    require __DIR__ . '/helpers/registrationData.php';
    require __DIR__ . '/helpers/marketingData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Staff']);

    // enquiry counts for the first two cards, open is new plus contacted
    $enquiryStats = getEnquiryCounts($conn);
    $enquiryStats['open'] = $enquiryStats['new'] + $enquiryStats['contacted'];

    // open enquiries, new first then contacted, at most five
    $openEnquiries = array_filter(getEnquiries($conn), fn ($e) => $e['status'] !== 'closed');
    usort($openEnquiries, fn ($a, $b) => ($a['status'] === 'new' ? 0 : 1) <=> ($b['status'] === 'new' ? 0 : 1)
        ?: strcmp($b['dateCreated'], $a['dateCreated']));
    $openEnquiries = array_slice($openEnquiries, 0, 5);

    // registrations received in the last 7 days for the third card
    $registrationStats = getRegistrationStats($conn);

    // latest five registrations, newest first
    $recentRegistrations = getRecentRegistrations($conn, 5);

    // campaign counts and donation totals
    $campaignStats = getCampaignStats($conn);

    // upcoming events and how many are this week
    $eventCounts = getUpcomingEventCounts($conn);

    // active campaigns closest to their goal, at most five
    $campaignProgress = getActiveCampaignProgress($conn, 5);

    // next five upcoming events, soonest first
    $upcomingEvents = getUpcomingEvents($conn, 5);

    // css class for each enquiry status badge
    function enquiryStatusClass($status)
    {
        return 'status-' . strtolower($status);
    }

    $pageTitle = 'Staff Dashboard';
    $activePage = 'dashboard';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<!-- staff content, the wrapper keeps the staff css scoped to this page -->
<div class="staff-dashboard">

    <!-- summary cards -->
    <section class="dashboard-cards">

        <?php
        // enquiries nobody has picked up yet, shown first as it needs the most attention
        $cardTitle = 'Awaiting Response';
        $cardValue = $enquiryStats['new'];
        $cardMeta = $enquiryStats['new'] === 1 ? 'New enquiry not yet contacted' : 'New enquiries not yet contacted';
        $cardClass = 'enquiries-awaiting-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // everything not yet closed
        $cardTitle = 'Open Enquiries';
        $cardValue = $enquiryStats['open'];
        $cardMeta = $enquiryStats['new'] . ' new, ' . $enquiryStats['contacted'] . ' contacted';
        $cardClass = 'enquiries-open-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // registrations received in the last 7 days
        $cardTitle = 'New Registrations';
        $cardValue = $registrationStats['lastSevenDays'];
        $cardMeta = 'In the last 7 days';
        $cardClass = 'registrations-recent-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // campaigns currently taking donations
        $cardTitle = 'Active Campaigns';
        $cardValue = $campaignStats['active'];
        $cardMeta = $campaignStats['draft'] . ' draft, ' . $campaignStats['closed'] . ' closed';
        $cardClass = 'campaigns-active-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // all donations to campaigns that are not archived
        $cardTitle = 'Funds Raised';
        $cardValue = formatRand($campaignStats['fundsRaised']);
        $cardMeta = formatRand($campaignStats['raisedLast30Days']) . ' in the last 30 days';
        $cardClass = 'donations-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // events dated today or later plus those to be confirmed
        $cardTitle = 'Upcoming Events';
        $cardValue = $eventCounts['upcoming'];
        $cardMeta = $eventCounts['soon'] . ' in the next ' . MARKETING_SOON_DAYS . ' days';
        $cardClass = 'events-upcoming-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <section class="dashboard-two-column">

        <!-- open enquiries, new first then contacted -->
        <section class="dashboard-panel enquiries-panel">
            <div class="panel-header">
                <h2>Enquiries Needing Attention</h2>
                <a class="panel-button" href="manageEnquiries.php">View all enquiries</a>
            </div>

            <div class="panel-body">
                <?php if (!$openEnquiries) : ?>
                    <div class="dashboard-empty">
                        <p>No open enquiries. You're all caught up.</p>
                    </div>
                <?php else : ?>
                    <div class="dashboard-table-wrap">
                        <table class="dashboard-table snapshot-table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Meeting type</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($openEnquiries as $enquiry) : ?>
                                    <tr>
                                        <td>
                                            <span class="snapshot-name"><?= htmlspecialchars($enquiry['name']) ?></span>
                                            <?php if (($enquiry['companyName'] ?? '') !== '') : ?>
                                                <span class="snapshot-sub"><?= htmlspecialchars($enquiry['companyName']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(ucfirst($enquiry['meetingType'])) ?></td>
                                        <td>
                                            <span class="status-badge <?= enquiryStatusClass($enquiry['status']) ?>">
                                                <?= htmlspecialchars(ucfirst($enquiry['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- recent registrations, latest five -->
        <section class="dashboard-panel recent-registrations-panel">
            <div class="panel-header">
                <h2>Recent Registrations</h2>
                <a class="panel-button" href="registrations.php">View all registrations</a>
            </div>

            <div class="panel-body">
                <?php if (!$recentRegistrations) : ?>
                    <div class="dashboard-empty">
                        <p>No registrations yet. New sign-ups will appear here.</p>
                    </div>
                <?php else : ?>
                    <div class="dashboard-table-wrap">
                        <table class="dashboard-table snapshot-table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Programme</th>
                                    <th scope="col">Age</th>
                                    <th scope="col">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRegistrations as $registration) : ?>
                                    <tr>
                                        <td>
                                            <span class="snapshot-name"><?= htmlspecialchars($registration['firstName'] . ' ' . $registration['lastName']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($registration['programme']) ?></td>
                                        <td class="snapshot-nowrap">
                                            <?= htmlspecialchars($registration['age']) ?>
                                            <?php if ($registration['isMinor']) : ?>
                                                <span class="minor-badge">Minor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="snapshot-nowrap">
                                            <?= htmlspecialchars(date('j M Y', strtotime($registration['registeredAt']))) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </section>

    <section class="dashboard-two-column">

        <!-- campaign progress, closest to their goal first -->
        <section class="dashboard-panel campaigns-panel">
            <div class="panel-header">
                <h2>Campaign Progress</h2>
                <a class="panel-button" href="manageCampaigns.php">View all campaigns</a>
            </div>

            <div class="panel-body">
                <?php if (!$campaignProgress) : ?>
                    <div class="dashboard-empty">
                        <p>No active campaigns. Active campaigns and their progress will appear here.</p>
                    </div>
                <?php else : ?>
                    <div class="dashboard-table-wrap">
                        <table class="dashboard-table snapshot-table">
                            <thead>
                                <tr>
                                    <th scope="col">Campaign</th>
                                    <th scope="col">Raised</th>
                                    <th scope="col">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaignProgress as $campaign) : ?>
                                    <tr>
                                        <td>
                                            <span class="snapshot-name"><?= htmlspecialchars($campaign['name']) ?></span>
                                            <span class="snapshot-sub">
                                                <?= $campaign['donations'] === 1 ? '1 donation' : $campaign['donations'] . ' donations' ?>
                                            </span>
                                        </td>
                                        <td class="snapshot-nowrap">
                                            <?= htmlspecialchars(formatRand($campaign['raised'])) ?>
                                            <span class="snapshot-sub">of <?= htmlspecialchars(formatRand($campaign['goal'])) ?></span>
                                        </td>
                                        <td class="campaign-progress-cell">
                                            <span class="campaign-progress-value"><?= $campaign['percentage'] ?>%</span>
                                            <div class="progress-track" role="progressbar" aria-label="<?= htmlspecialchars($campaign['name']) ?> progress"
                                                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $campaign['percentage'] ?>">
                                                <div class="progress-fill" style="width: <?= $campaign['percentage'] ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- upcoming events, next five soonest first -->
        <section class="dashboard-panel upcoming-events-panel">
            <div class="panel-header">
                <h2>Upcoming Events</h2>
                <a class="panel-button" href="manageEvents.php">View all events</a>
            </div>

            <div class="panel-body">
                <?php if (!$upcomingEvents) : ?>
                    <div class="dashboard-empty">
                        <p>No upcoming events. Scheduled events will appear here.</p>
                    </div>
                <?php else : ?>
                    <div class="dashboard-table-wrap">
                        <table class="dashboard-table snapshot-table">
                            <thead>
                                <tr>
                                    <th scope="col">Event</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingEvents as $event) : ?>
                                    <tr>
                                        <td>
                                            <span class="snapshot-name"><?= htmlspecialchars($event['name']) ?></span>
                                        </td>
                                        <td class="snapshot-nowrap">
                                            <?= $event['eventDate'] ? htmlspecialchars(date('j M Y', strtotime($event['eventDate']))) : '—' ?>
                                        </td>
                                        <td class="snapshot-nowrap">
                                            <span class="when-badge<?= in_array($event['when'], ['Today', 'Tomorrow'], true) ? ' when-soon' : '' ?>">
                                                <?= htmlspecialchars($event['when']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </section>

</div>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>
