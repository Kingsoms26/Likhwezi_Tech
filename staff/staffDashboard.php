<?php
    // staffDashboard.php is the staff dashboard overview
    // shows the greeting, the donation totals and the enquiry, registration,
    // campaign and event highlight cards, all fed from the database

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/enquiryData.php';
    require __DIR__ . '/helpers/registrationData.php';
    require __DIR__ . '/helpers/marketingData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Staff']);

    // the donation card shows a week, a month or a year
    $donationPeriod = in_array($_GET['donationPeriod'] ?? '', ['week', 'month', 'year'], true)
        ? $_GET['donationPeriod']
        : 'week';

    // greeting, morning before 12, afternoon before 17, evening after
    $hour = (int) date('G');
    $greeting = match (true) {
        $hour < 12 => 'Good morning',
        $hour < 17 => 'Good afternoon',
        default    => 'Good evening',
    };
    $firstName = trim((string) ($account['firstName'] ?? '')) ?: ($account['username'] ?? 'there');

    // enquiry counts, open is new plus contacted
    $enquiryStats = getEnquiryCounts($conn);
    $enquiryStats['open'] = $enquiryStats['new'] + $enquiryStats['contacted'];

    // the stacked bar shows all three statuses, so its own total includes closed
    $enquiryBands = [
        ['key' => 'new',       'label' => 'New',       'class' => 'stack-new',       'colour' => '#2563eb'],
        ['key' => 'contacted', 'label' => 'Contacted', 'class' => 'stack-contacted', 'colour' => '#f59e0b'],
        ['key' => 'closed',    'label' => 'Closed',    'class' => 'stack-closed',    'colour' => '#16a34a'],
    ];
    $enquiryBandTotal = $enquiryStats['new'] + $enquiryStats['contacted'] + $enquiryStats['closed'];

    // registrations over 30 days for the card and 7 days for the hero pill,
    // both from one query each so the counts and their window always agree
    $registrationStats = getRegistrationWindow($conn, 30);
    $registrationWeek = getRegistrationWindow($conn, 7);

    // the three programmes the card reports, matching the Cyber Young Minds programmes
    $programmeSpec = [
        ['label' => 'Coding',   'keys' => ['Coding']],
        ['label' => 'Robotics', 'keys' => ['Robotics']],
        ['label' => 'AI',       'keys' => ['Artificial Intelligence', 'AI']],
    ];
    $programmeCounts = array_map(function ($spec) use ($registrationStats) {
        $total = 0;
        foreach ($spec['keys'] as $key) {
            $total += (int) ($registrationStats['byProgramme'][$key] ?? 0);
        }
        return ['label' => $spec['label'], 'total' => $total];
    }, $programmeSpec);

    // donation totals for the chosen period, and the all-time total for comparison
    $donations = getDonationTotals($conn, $donationPeriod);

    // active campaigns in total, and the two closest to their goal
    $campaignTotals = getActiveCampaignTotals($conn);
    $campaignProgress = getActiveCampaignProgress($conn, 2);

    // the next three events, including drafts whose date is still to be confirmed,
    // and how many fall in the next week for the pill
    $upcomingEvents = getUpcomingEvents($conn, 3);
    $eventsNextWeek = getUpcomingDatedEventCount($conn, MARKETING_SOON_DAYS);

    // donut geometry, the arc is the raised share of the combined goal
    $goalPercentage = $campaignTotals['goal'] > 0
        ? (int) round($campaignTotals['raised'] / $campaignTotals['goal'] * 100)
        : 0;
    $goalPercentage = max(0, min(100, $goalPercentage));
    $donutCircumference = 2 * M_PI * 48;
    $donutArc = $donutCircumference * $goalPercentage / 100;

    $pageTitle = 'Overview';
    $activePage = 'dashboard';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<!-- staff content, the wrapper keeps the staff css scoped to this page -->
<div class="staff-dashboard">

    <section class="overview-hero" aria-labelledby="overview-title">
        <div class="overview-top">
            <div class="overview-intro">
                <h1 id="overview-title"><?= htmlspecialchars($greeting . ', ' . $firstName) ?></h1>
                <p>Your team's activity and impact at a glance.</p>

                <ul class="overview-pills">
                    <li class="overview-pill">
                        <strong><?= (int) $enquiryStats['new'] ?></strong> awaiting a response
                    </li>
                    <li class="overview-pill">
                        <strong><?= (int) $registrationWeek['total'] ?></strong> sign-ups this week
                    </li>
                    <li class="overview-pill">
                        <strong><?= (int) $eventsNextWeek ?></strong> events in the next <?= MARKETING_SOON_DAYS ?> days
                    </li>
                </ul>
            </div>

            <!-- donation totals, the period form reloads the page with another window -->
            <section class="donations-glass" aria-labelledby="donations-title">
                <div class="donations-heading">
                    <div>
                        <h3 id="donations-title">Donations</h3>
                    </div>
                    <form class="donation-period-form" method="get" action="staffDashboard.php">
                        <label class="visually-hidden" for="donation-period">Donation period</label>
                        <select id="donation-period" name="donationPeriod" data-submit-on-change>
                            <option value="week"<?= $donationPeriod === 'week' ? ' selected' : '' ?>>Weekly</option>
                            <option value="month"<?= $donationPeriod === 'month' ? ' selected' : '' ?>>Monthly</option>
                            <option value="year"<?= $donationPeriod === 'year' ? ' selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                </div>

                <div class="donation-figures">
                    <div class="donation-figure">
                        <span>Raised this period</span>
                        <strong><?= htmlspecialchars(formatRand($donations['period'])) ?></strong>
                    </div>
                    <div class="donation-figure">
                        <span>All-time total</span>
                        <strong><?= htmlspecialchars(formatRand($donations['allTime'])) ?></strong>
                    </div>
                </div>
            </section>
        </div>

        <!-- highlight cards, each one links through to its full page -->
        <section class="overview-cards">

            <article class="overview-card enquiries-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker"><?= (int) $enquiryStats['open'] ?> open right now</p>
                        <h3>Enquiries</h3>
                    </div>
                    <a class="overview-card-action" href="manageEnquiries.php" aria-label="View enquiries">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>

                <p class="card-figure">
                    <strong><?= (int) $enquiryStats['open'] ?></strong>
                    <span>open</span>
                </p>

                <?php if ($enquiryBandTotal > 0) : ?>
                    <div class="enquiry-stack" role="img" aria-label="Enquiries by status">
                        <?php foreach ($enquiryBands as $band) : ?>
                            <span class="<?= $band['class'] ?>"
                                  style="width: <?= number_format($enquiryStats[$band['key']] / $enquiryBandTotal * 100, 2, '.', '') ?>%"></span>
                        <?php endforeach; ?>
                    </div>

                    <ul class="status-counts">
                        <?php foreach ($enquiryBands as $band) : ?>
                            <li>
                                <i class="legend-dot" style="--cell-color: <?= $band['colour'] ?>"></i>
                                <?= $band['label'] ?> <b><?= (int) $enquiryStats[$band['key']] ?></b>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="chart-empty chart-empty-dark">No enquiries yet.</p>
                <?php endif; ?>
            </article>

            <article class="overview-card registrations-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker">Last 30 days</p>
                        <h3>Registrations</h3>
                    </div>
                    <a class="overview-card-action" href="registrations.php" aria-label="View registrations">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>

                <p class="card-figure">
                    <strong><?= (int) $registrationStats['total'] ?></strong>
                    <span>sign-ups in 30 days</span>
                </p>

                <ul class="programme-counts">
                    <?php foreach ($programmeCounts as $programme) : ?>
                        <li>
                            <span><?= htmlspecialchars($programme['label']) ?></span>
                            <b><?= (int) $programme['total'] ?></b>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>

            <article class="overview-card events-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker">Next up</p>
                        <h3>Upcoming Events</h3>
                    </div>
                    <a class="overview-card-action" href="manageEvents.php" aria-label="View events">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>

                <?php if ($upcomingEvents) : ?>
                    <ul class="upcoming-events-list">
                        <?php foreach ($upcomingEvents as $event) : ?>
                            <?php $eventDate = $event['eventDate'] ? date_create($event['eventDate']) : null; ?>
                            <li>
                                <span class="event-date-tile<?= $eventDate ? '' : ' is-tbc' ?>">
                                    <?php if ($eventDate) : ?>
                                        <strong><?= htmlspecialchars($eventDate->format('d')) ?></strong>
                                        <small><?= htmlspecialchars($eventDate->format('M')) ?></small>
                                    <?php else : ?>
                                        <strong>TBC</strong>
                                    <?php endif; ?>
                                </span>
                                <span class="upcoming-event-copy">
                                    <strong><?= htmlspecialchars($event['name']) ?></strong>
                                    <small><?= htmlspecialchars($eventDate ? $eventDate->format('D, j M Y') : 'Date to be confirmed') ?></small>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <a class="upcoming-events-link" href="manageEvents.php">
                        All events <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                <?php else : ?>
                    <div class="events-empty">
                        <p>No upcoming events</p>
                        <a class="upcoming-events-link" href="manageEvents.php">
                            Create event <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </article>

            <article class="overview-card campaigns-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker"><?= (int) $campaignTotals['campaigns'] ?> active</p>
                        <h3>Campaign Progress</h3>
                    </div>
                    <a class="overview-card-action" href="manageCampaigns.php" aria-label="View campaigns">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="campaign-donut-wrap">
                    <svg class="campaign-donut" viewBox="0 0 120 120" role="img"
                         aria-label="<?= $goalPercentage ?>% of the goal raised across active campaigns">
                        <circle cx="60" cy="60" r="48" fill="none" stroke="#04113b" stroke-width="12"></circle>
                        <circle cx="60" cy="60" r="48" fill="none" stroke="#f97316" stroke-width="12"
                                stroke-linecap="round"
                                stroke-dasharray="<?= number_format($donutArc, 2, '.', '') ?> <?= number_format($donutCircumference, 2, '.', '') ?>"
                                transform="rotate(-90 60 60)"></circle>
                        <text class="campaign-donut-value" x="60" y="57" text-anchor="middle"><?= $goalPercentage ?>%</text>
                        <text class="campaign-donut-caption" x="60" y="75" text-anchor="middle">of goal</text>
                    </svg>
                </div>

                <p class="campaign-summary">
                    <?= htmlspecialchars(formatRand($campaignTotals['raised'])) ?> of <?= htmlspecialchars(formatRand($campaignTotals['goal'])) ?>
                    across <?= (int) $campaignTotals['campaigns'] ?> active campaign<?= $campaignTotals['campaigns'] === 1 ? '' : 's' ?>
                </p>

                <?php if ($campaignProgress) : ?>
                    <ul class="campaign-mini-list">
                        <?php foreach ($campaignProgress as $campaign) : ?>
                            <li class="campaign-mini">
                                <div class="campaign-mini-top">
                                    <span class="campaign-mini-name"><?= htmlspecialchars($campaign['name']) ?></span>
                                    <span class="campaign-mini-pct"><?= (int) $campaign['percentage'] ?>%</span>
                                </div>
                                <div class="campaign-mini-track">
                                    <span class="campaign-mini-fill" style="width: <?= max(0, min(100, (int) $campaign['percentage'])) ?>%"></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>

        </section>
    </section>

</div>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>
