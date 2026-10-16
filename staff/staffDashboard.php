<?php
    // staffDashboard.php is the staff dashboard overview

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/enquiryData.php';
    require __DIR__ . '/helpers/registrationData.php';
    require __DIR__ . '/helpers/marketingData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Staff']);

    $donationPeriod = in_array($_GET['donationPeriod'] ?? '', ['week', 'month'], true)
        ? $_GET['donationPeriod']
        : 'week';

    // Both dashboard roles use the same fixed fixtures so the overview is populated without live records.
    $dashboardUsesSampleData = true;
    $staffPreview = staffPreviewEnabled();

    if ($dashboardUsesSampleData) {
        $enquiryStats = ['new' => 3, 'contacted' => 2, 'closed' => 2, 'open' => 5];
        $registrationInterest = [
            ['name' => 'Coding', 'total' => 4, 'percentage' => 36],
            ['name' => 'Robotics', 'total' => 3, 'percentage' => 27],
            ['name' => 'Artificial Intelligence', 'total' => 2, 'percentage' => 18],
            ['name' => 'Hackathons', 'total' => 2, 'percentage' => 19],
        ];
        $campaignStats = [
            'active' => 4,
            'draft' => 1,
            'closed' => 1,
            'fundsRaised' => 119050.0,
            'raisedLast30Days' => 24550.0,
            'donations' => 64,
        ];
        $campaignProgress = [
            ['campaignID' => 1, 'name' => 'School Lab Equipment', 'goal' => 50000.0, 'raised' => 38250.0, 'donations' => 18, 'percentage' => 77],
            ['campaignID' => 2, 'name' => 'Youth Coding Kits', 'goal' => 35000.0, 'raised' => 22400.0, 'donations' => 12, 'percentage' => 64],
            ['campaignID' => 3, 'name' => 'Robotics Workshop', 'goal' => 40000.0, 'raised' => 18800.0, 'donations' => 9, 'percentage' => 47],
            ['campaignID' => 4, 'name' => 'Digital Skills Bus', 'goal' => 80000.0, 'raised' => 21600.0, 'donations' => 11, 'percentage' => 27],
        ];

        $previewDonations = [
            [-26, 1850.0], [-23, 3200.0], [-19, 1400.0], [-16, 4600.0], [-12, 2300.0],
            [-9, 3800.0], [-6, 450.0], [-5, 800.0], [-3, 600.0], [-2, 1200.0], [0, 950.0],
        ];
        $donationTrend = array_map(fn ($donation) => [
            'date' => (new DateTimeImmutable('today'))->modify($donation[0] . ' days')->format('Y-m-d'),
            'amount' => $donation[1],
        ], $previewDonations);

        $today = new DateTimeImmutable('today');
        $upcomingEvents = [
            ['eventID' => 0, 'name' => 'CYM Coding Workshop', 'eventDate' => $today->modify('+2 days')->format('Y-m-d'), 'when' => 'In 2 days'],
            ['eventID' => 0, 'name' => 'Robotics Open Day', 'eventDate' => $today->modify('+5 days')->format('Y-m-d'), 'when' => 'In 5 days'],
            ['eventID' => 0, 'name' => 'Digital Skills for Educators', 'eventDate' => $today->modify('+9 days')->format('Y-m-d'), 'when' => 'In 9 days'],
        ];
    } else {
        // enquiry counts for the first two cards, open is new plus contacted
        $enquiryStats = getEnquiryCounts($conn);
        $enquiryStats['open'] = $enquiryStats['new'] + $enquiryStats['contacted'];
        $registrationInterest = getRegistrationsByProgramme($conn);
        $campaignStats = getCampaignStats($conn);
        $donationTrend = getDonationTrend($conn, $donationPeriod);
        $campaignProgress = getActiveCampaignProgress($conn, 5);
        $upcomingEvents = getUpcomingEvents($conn, 5);
    }

    $chartPoints = function (array $values, ?float $scale = null): string {
        $count = count($values);
        if (!$count) {
            return '';
        }

        $maximum = max(1, $scale ?? max(...$values));
        $lastIndex = max(1, $count - 1);
        $points = [];
        foreach ($values as $index => $value) {
            $x = 12 + ($index / $lastIndex) * 296;
            $y = 96 - ((float) $value / $maximum) * 76;
            $points[] = number_format($x, 1, '.', '') . ',' . number_format($y, 1, '.', '');
        }

        return implode(' ', $points);
    };

    $donationDays = $donationPeriod === 'month' ? 30 : 7;
    $donationsByDate = array_column($donationTrend, 'amount', 'date');
    $donationChart = [];
    $today = new DateTimeImmutable('today');
    for ($daysAgo = $donationDays - 1; $daysAgo >= 0; $daysAgo--) {
        $date = $today->modify("-$daysAgo days");
        $dateKey = $date->format('Y-m-d');
        $donationChart[] = [
            'date' => $dateKey,
            'label' => $date->format($donationPeriod === 'month' ? 'j' : 'D'),
            'amount' => (float) ($donationsByDate[$dateKey] ?? 0),
        ];
    }
    $donationValues = array_column($donationChart, 'amount');
    $donationPeriodTotal = array_sum($donationValues);
    $donationChartPoints = $chartPoints($donationValues);
    $donationChartLabels = $donationPeriod === 'week'
        ? $donationChart
        : array_values(array_filter($donationChart, fn ($day, $index) => $index % 5 === 0 || $index === 29, ARRAY_FILTER_USE_BOTH));

    $registrationBuckets = array_slice($registrationInterest, 0, 4);
    if (count($registrationInterest) > 4) {
        $registrationBuckets[] = [
            'name' => 'Other programmes',
            'total' => array_sum(array_column(array_slice($registrationInterest, 4), 'total')),
        ];
    }
    $registrationTotal = array_sum(array_column($registrationBuckets, 'total'));
    $honeycombColors = ['#173f8f', '#2f66d3', '#6d9be8', '#a9c5f4', '#d4e2fb'];
    $honeycomb = [];
    if ($registrationTotal > 0) {
        $remainders = [];
        $assigned = 0;
        foreach ($registrationBuckets as $index => $bucket) {
            $exact = 37 * $bucket['total'] / $registrationTotal;
            $honeycomb[$index] = (int) floor($exact);
            $assigned += $honeycomb[$index];
            $remainders[$index] = $exact - $honeycomb[$index];
        }
        arsort($remainders);
        foreach (array_slice(array_keys($remainders), 0, 37 - $assigned) as $index) {
            $honeycomb[$index]++;
        }
    }

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
                <?php if ($dashboardUsesSampleData) : ?>
                    <p class="dashboard-preview-notice"><i class="bi bi-eye" aria-hidden="true"></i> Sample dashboard data</p>
                <?php endif; ?>
                <p class="overview-eyebrow">Likhwezi Technologies</p>
                <h2 id="overview-title">Overview</h2>
                <p>Your team's activity and impact at a glance.</p>
            </div>

            <section class="donations-glass" aria-labelledby="donations-title">
                <div class="donations-heading">
                    <div>
                        <p class="overview-eyebrow">Giving at a glance</p>
                        <h3 id="donations-title">Donations</h3>
                    </div>
                    <form class="donation-period-form" method="get" action="staffDashboard.php">
                        <?php if ($staffPreview) : ?>
                            <input type="hidden" name="preview" value="1">
                        <?php endif; ?>
                        <label class="visually-hidden" for="donation-period">Donation chart period</label>
                        <select id="donation-period" name="donationPeriod">
                            <option value="week"<?= $donationPeriod === 'week' ? ' selected' : '' ?>>Weekly</option>
                            <option value="month"<?= $donationPeriod === 'month' ? ' selected' : '' ?>>Monthly</option>
                        </select>
                    </form>
                </div>
                <div class="donation-totals">
                    <div>
                        <span>Total donations</span>
                        <strong><?= (int) $campaignStats['donations'] ?></strong>
                    </div>
                    <div>
                        <span>Funds raised</span>
                        <strong><?= htmlspecialchars(formatRand($campaignStats['fundsRaised'])) ?></strong>
                    </div>
                </div>
                <?php if ($donationTrend) : ?>
                    <div class="donation-chart">
                        <svg viewBox="0 0 320 112" role="img" aria-labelledby="donation-chart-title donation-chart-desc">
                            <title id="donation-chart-title">Paid donations over the <?= $donationPeriod === 'week' ? 'last seven days' : 'last thirty days' ?></title>
                            <desc id="donation-chart-desc">The selected period total is <?= htmlspecialchars(formatRand($donationPeriodTotal)) ?>. Each point shows the actual paid donation total for that date.</desc>
                            <line x1="12" y1="96" x2="308" y2="96" class="chart-baseline"></line>
                            <polyline points="<?= htmlspecialchars($donationChartPoints) ?>" class="donation-line"></polyline>
                            <?php foreach ($donationChart as $index => $day) :
                                [$pointX, $pointY] = explode(',', explode(' ', $donationChartPoints)[$index]);
                            ?>
                                <circle cx="<?= $pointX ?>" cy="<?= $pointY ?>" r="3.5" class="donation-point">
                                    <title><?= htmlspecialchars(date('j M Y', strtotime($day['date'])) . ': ' . formatRand($day['amount'])) ?></title>
                                </circle>
                            <?php endforeach; ?>
                        </svg>
                        <div class="donation-chart-labels">
                            <?php foreach ($donationChartLabels as $day) : ?>
                                <span><?= htmlspecialchars($day['label']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <p class="chart-empty">No paid donations recorded in this period.</p>
                <?php endif; ?>
                <div class="donation-period-meta">
                    <span><?= $donationPeriod === 'week' ? 'Last 7 days' : 'Last 30 days' ?></span>
                    <strong><?= htmlspecialchars(formatRand($donationPeriodTotal)) ?> in period</strong>
                </div>
            </section>
        </div>

        <section class="overview-cards" aria-label="Dashboard highlights">
            <article class="overview-card enquiry-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker">Inbox</p>
                        <h3>Enquiries</h3>
                    </div>
                    <a class="overview-card-action" href="manageEnquiries.php" aria-label="View enquiries">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="overview-card-metric"><?= (int) $enquiryStats['open'] ?><span> open</span></div>
                <?php
                    $enquiryTotal = $enquiryStats['new'] + $enquiryStats['contacted'] + $enquiryStats['closed'];
                    $newWidth = $enquiryTotal ? $enquiryStats['new'] / $enquiryTotal * 100 : 0;
                    $contactedWidth = $enquiryTotal ? $enquiryStats['contacted'] / $enquiryTotal * 100 : 0;
                    $closedWidth = $enquiryTotal ? $enquiryStats['closed'] / $enquiryTotal * 100 : 0;
                ?>
                <div class="enquiry-segments" role="img" aria-label="<?= (int) $enquiryStats['new'] ?> new, <?= (int) $enquiryStats['contacted'] ?> contacted, <?= (int) $enquiryStats['closed'] ?> resolved enquiries">
                    <span class="segment-new" style="width: <?= $newWidth ?>%"></span>
                    <span class="segment-contacted" style="width: <?= $contactedWidth ?>%"></span>
                    <span class="segment-closed" style="width: <?= $closedWidth ?>%"></span>
                </div>
                <div class="overview-legend enquiry-legend">
                    <span><i class="legend-dot segment-new"></i>New <b><?= (int) $enquiryStats['new'] ?></b></span>
                    <span><i class="legend-dot segment-contacted"></i>Contacted <b><?= (int) $enquiryStats['contacted'] ?></b></span>
                    <span><i class="legend-dot segment-closed"></i>Resolved <b><?= (int) $enquiryStats['closed'] ?></b></span>
                </div>
            </article>

            <article class="overview-card upcoming-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker"><?= count($upcomingEvents) ?> scheduled</p>
                        <h3>Upcoming Events</h3>
                    </div>
                    <a class="overview-card-action" href="manageEvents.php" aria-label="View upcoming events">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <?php if ($upcomingEvents) : ?>
                    <ul class="upcoming-event-list">
                        <?php foreach (array_slice($upcomingEvents, 0, 3) as $event) :
                            $eventDate = $event['eventDate'] ? new DateTimeImmutable($event['eventDate']) : null;
                        ?>
                            <li>
                                <span class="event-date-tile">
                                    <strong><?= $eventDate ? htmlspecialchars($eventDate->format('d')) : '—' ?></strong>
                                    <small><?= $eventDate ? htmlspecialchars($eventDate->format('M')) : 'TBC' ?></small>
                                </span>
                                <span class="upcoming-event-copy">
                                    <strong><?= htmlspecialchars($event['name']) ?></strong>
                                    <small><?= htmlspecialchars($event['when']) ?></small>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="upcoming-events-link" href="manageEvents.php">All events <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <?php else : ?>
                    <p class="chart-empty upcoming-events-empty">No upcoming events scheduled.</p>
                    <a class="upcoming-events-link" href="manageEvents.php">Manage events <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <?php endif; ?>
            </article>

            <article class="overview-card registrations-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker"><?= $registrationTotal ?> registrations total</p>
                        <h3>Registrations</h3>
                    </div>
                    <a class="overview-card-action" href="registrations.php" aria-label="View registrations">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <?php if ($registrationTotal > 0) : ?>
                    <div class="honeycomb-chart" role="img" aria-label="Registration distribution across <?= count($registrationBuckets) ?> programmes">
                        <?php foreach ($honeycomb as $bucketIndex => $cellCount) : ?>
                            <?php for ($cell = 0; $cell < $cellCount; $cell++) : ?>
                                <span class="honeycomb-cell" style="--cell-color: <?= $honeycombColors[$bucketIndex % count($honeycombColors)] ?>" title="<?= htmlspecialchars($registrationBuckets[$bucketIndex]['name'] . ': ' . $registrationBuckets[$bucketIndex]['total']) ?>"></span>
                            <?php endfor; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="overview-legend registration-legend">
                        <?php foreach ($registrationBuckets as $index => $bucket) : ?>
                            <span title="<?= htmlspecialchars($bucket['name']) ?>">
                                <i class="legend-dot" style="--cell-color: <?= $honeycombColors[$index % count($honeycombColors)] ?>"></i>
                                <?= htmlspecialchars($bucket['name']) ?><b><?= (int) $bucket['total'] ?></b>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="chart-empty">No registrations recorded yet.</p>
                <?php endif; ?>
            </article>

            <article class="overview-card campaign-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker">Active fundraising</p>
                        <h3>Campaign Progress</h3>
                    </div>
                    <a class="overview-card-action" href="manageCampaigns.php" aria-label="View campaigns">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <?php if ($campaignProgress) : ?>
                    <?php $campaignBars = array_slice($campaignProgress, 0, 7); ?>
                    <div class="campaign-bars" role="img" aria-label="Progress of active campaigns">
                        <?php foreach ($campaignBars as $index => $campaign) : ?>
                            <div class="campaign-bar-item" title="<?= htmlspecialchars($campaign['name'] . ': ' . formatRand($campaign['raised']) . ' raised of ' . formatRand($campaign['goal'])) ?>">
                                <span class="campaign-bar-value"><?= (int) $campaign['percentage'] ?>%</span>
                                <span class="campaign-bar" style="--bar-height: <?= max(8, (int) $campaign['percentage']) ?>%; --bar-color: <?= $honeycombColors[$index % count($honeycombColors)] ?>"></span>
                                <span class="campaign-bar-label"><?= htmlspecialchars(mb_strimwidth($campaign['name'], 0, 9, '…')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="campaign-chart-caption"><?= (int) $campaignStats['active'] ?> active campaigns · <?= htmlspecialchars(formatRand($campaignStats['fundsRaised'])) ?> raised</p>
                <?php else : ?>
                    <p class="chart-empty campaign-empty">No active campaigns to chart.</p>
                <?php endif; ?>
            </article>
        </section>
    </section>

    <nav class="dashboard-quick-toolbar" aria-label="Quick links">
        <a href="index.php" aria-label="Dashboard" title="Dashboard"><i class="bi bi-grid-1x2" aria-hidden="true"></i></a>
        <?php $notificationToolbar = true; include __DIR__ . '/components/notificationControl.php'; unset($notificationToolbar); ?>
        <a href="manageEnquiries.php" aria-label="Enquiries" title="Enquiries"><i class="bi bi-chat-left-text" aria-hidden="true"></i></a>
        <a href="profile.php" aria-label="Profile settings" title="Profile settings"><i class="bi bi-gear" aria-hidden="true"></i></a>
    </nav>

</div>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>
