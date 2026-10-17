<?php
    // staffDashboard.php is the staff dashboard
    // contains the enquiry, registration, funds and campaigns and events chart cards
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

    // registrations per day for the registrations column chart
    $registrationDays = 14;
    $registrationsPerDay = getRegistrationsPerDay($conn, $registrationDays);

    // dated events in the next 30 days for the events timeline
    $timelineDays = 30;
    $timelineEvents = getEventsWithinDays($conn, $timelineDays);

    // segments for the enquiry donut and the campaign status bar
    $enquirySegments = [
        ['label' => 'New', 'value' => $enquiryStats['new'], 'series' => 'accent'],
        ['label' => 'Contacted', 'value' => $enquiryStats['contacted'], 'series' => 'amber'],
        ['label' => 'Closed', 'value' => $enquiryStats['closed'], 'series' => 'green'],
    ];
    $campaignSegments = [
        ['label' => 'Active', 'value' => $campaignStats['active'], 'series' => 'accent'],
        ['label' => 'Draft', 'value' => $campaignStats['draft'], 'series' => 'amber'],
        ['label' => 'Closed', 'value' => $campaignStats['closed'], 'series' => 'grey'],
    ];

    require_once __DIR__ . '/components/dashboardCharts.php';

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

    <!-- summary chart cards, same card look as components/dashboardCard.php -->
    <section class="dashboard-cards chart-cards">

        <!-- enquiries by status, open in the middle -->
        <article class="dashboard-card chart-card enquiries-chart-card">
            <h2>Enquiries</h2>
            <div class="chart-donut-row">
                <?= chartDonut($enquirySegments, (string) $enquiryStats['open'], 'open') ?>
                <?= chartLegend($enquirySegments) ?>
            </div>
            <p class="dashboard-card-meta">
                <?= $enquiryStats['new'] === 1 ? '1 enquiry awaiting a first response' : $enquiryStats['new'] . ' enquiries awaiting a first response' ?>
            </p>
        </article>

        <!-- registrations per day, last 7 days as the headline -->
        <article class="dashboard-card chart-card registrations-chart-card">
            <h2>Registrations</h2>
            <div class="dashboard-card-value">
                <?= $registrationStats['lastSevenDays'] ?><span class="chart-value-note">in the last 7 days</span>
            </div>
            <?= chartColumns($registrationsPerDay) ?>
        </article>

        <!-- funds raised, and how the top active campaigns are doing against their goal -->
        <article class="dashboard-card chart-card funds-chart-card">
            <h2>Funds Raised</h2>
            <div class="dashboard-card-value"><?= htmlspecialchars(formatRand($campaignStats['fundsRaised'])) ?></div>
            <div class="chart-goals">
                <?php if (!$campaignProgress) : ?>
                    <p class="dashboard-card-meta">No active campaigns.</p>
                <?php endif; ?>

                <?php foreach (array_slice($campaignProgress, 0, 3) as $campaign) : ?>
                    <?php
                    $progressValue = $campaign['percentage'];
                    $progressLabel = $campaign['name'];
                    $progressMeta = formatRand($campaign['raised']) . ' of ' . formatRand($campaign['goal']);
                    include __DIR__ . '/components/dashboardProgress.php';
                    ?>
                <?php endforeach; ?>
            </div>
            <p class="dashboard-card-meta"><?= htmlspecialchars(formatRand($campaignStats['raisedLast30Days'])) ?> in the last 30 days</p>
        </article>

        <!-- campaigns by status and events over the next 30 days -->
        <article class="dashboard-card chart-card schedule-chart-card">
            <h2>Campaigns &amp; Events</h2>
            <div>
                <?= chartShareBar($campaignSegments, $campaignStats['active'] . ' active, ' . $campaignStats['draft'] . ' draft, ' . $campaignStats['closed'] . ' closed campaigns') ?>
                <?= chartLegend($campaignSegments, true) ?>
            </div>
            <?= chartTimeline($timelineEvents, $timelineDays, MARKETING_SOON_DAYS) ?>
            <p class="dashboard-card-meta">
                <?= $eventCounts['upcoming'] === 1 ? '1 upcoming event' : $eventCounts['upcoming'] . ' upcoming events' ?>,
                <?= $eventCounts['soon'] ?> in the next <?= MARKETING_SOON_DAYS ?> days
            </p>
        </article>

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
