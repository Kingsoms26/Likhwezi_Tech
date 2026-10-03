<?php
    // marketingDashboard.php is the marketing dashboard
    // contains the campaign, donation and event cards, campaign progress and upcoming events

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/marketingData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Marketing']);

    // campaign counts and donation totals for the first two cards
    $campaignStats = getCampaignStats($conn);

    // upcoming events and how many are this week for the third card
    $eventCounts = getUpcomingEventCounts($conn);

    // active campaigns closest to their goal, at most five
    $campaignProgress = getActiveCampaignProgress($conn, 5);

    // next five upcoming events, soonest first
    $upcomingEvents = getUpcomingEvents($conn, 5);

    $pageTitle = 'Marketing Dashboard';
    $activePage = 'dashboard';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<!-- marketing content, the wrapper keeps the marketing css scoped to this page -->
<div class="marketing-dashboard">

    <!-- summary cards -->
    <section class="dashboard-cards">

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