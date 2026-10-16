<?php
    // adminDashboard.php is the admin dashboard
    // contains the summary cards, open enquiries, registrations by interest and campaign progress

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/previewData.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    require __DIR__ . '/helpers/dashboardData.php';

    // Keep dashboard data separate from the live staff-page preview mode.
    $dashboardUsesSampleData = true;
    if ($dashboardUsesSampleData) {
        $stats = [
            'activeEnquiries' => 5,
            'activeCampaigns' => 4,
            'fundsRaised' => 119050.0,
            'recentEnquiries' => [
                ['subject' => 'We are looking for coding sessions for our community centre.', 'customer' => 'Amara Ndlovu', 'status' => 'New'],
                ['subject' => 'Could you share details about the robotics programme?', 'customer' => 'Thabo Mokoena', 'status' => 'New'],
                ['subject' => 'Please send a proposal for a digital skills workshop.', 'customer' => 'Lerato Dlamini', 'status' => 'New'],
            ],
            'registrationInterest' => [
                ['name' => 'Coding', 'total' => 4, 'percentage' => 36],
                ['name' => 'Robotics', 'total' => 3, 'percentage' => 27],
                ['name' => 'Artificial Intelligence', 'total' => 2, 'percentage' => 18],
                ['name' => 'Hackathons', 'total' => 2, 'percentage' => 19],
            ],
            'campaigns' => [
                ['campaignID' => 99201, 'name' => 'School Lab Equipment', 'goal' => 50000, 'raised' => 38250, 'donations' => 18, 'percentage' => 77],
                ['campaignID' => 99202, 'name' => 'Youth Coding Kits', 'goal' => 35000, 'raised' => 22400, 'donations' => 12, 'percentage' => 64],
                ['campaignID' => 99203, 'name' => 'Robotics Workshop', 'goal' => 40000, 'raised' => 18800, 'donations' => 9, 'percentage' => 47],
            ],
        ];
    } else {
        // fetch the enquiries, campaigns, funds and registrations
        $stats = getDashboardStats($conn);
    }

    $pageTitle = 'Dashboard';
    $activePage = 'dashboard';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<!-- Admin dashboard content uses the staff overview presentation with admin-specific data. -->
<div class="staff-dashboard admin-dashboard">

    <section class="overview-hero" aria-labelledby="overview-title">
        <div class="overview-top">
            <div class="overview-intro">
                <p class="overview-eyebrow">Likhwezi Technologies</p>
                <h2 id="overview-title">Dashboard</h2>
                <p>Your organisation's activity and impact at a glance.</p>
            </div>

            <section class="donations-glass admin-summary-glass" aria-label="Dashboard summary">
                <p class="overview-eyebrow">At a glance</p>
                <div class="donation-totals">
                    <div>
                        <span>Open enquiries</span>
                        <strong><?= (int) $stats['activeEnquiries'] ?></strong>
                    </div>
                    <div>
                        <span>Active campaigns</span>
                        <strong><?= (int) $stats['activeCampaigns'] ?></strong>
                    </div>
                    <div>
                        <span>Funds raised</span>
                        <strong><?= htmlspecialchars(formatRand($stats['fundsRaised'])) ?></strong>
                    </div>
                </div>
            </section>
        </div>

        <section class="overview-cards" aria-label="Dashboard highlights">
            <article class="overview-card enquiry-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker">Inbox</p>
                        <h3>Open Enquiries</h3>
                    </div>
                    <a class="overview-card-action" href="manageEnquiries.php" aria-label="View enquiries">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="overview-card-metric"><?= (int) $stats['activeEnquiries'] ?><span> open</span></div>
                <?php if ($stats['recentEnquiries']) : ?>
                    <ul class="admin-overview-list">
                        <?php foreach ($stats['recentEnquiries'] as $enquiry) : ?>
                            <li>
                                <strong><?= htmlspecialchars($enquiry['subject']) ?></strong>
                                <span><?= htmlspecialchars($enquiry['customer']) ?> · <?= htmlspecialchars($enquiry['status']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="chart-empty">No open enquiries.</p>
                <?php endif; ?>
            </article>

            <article class="overview-card registrations-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker"><?= array_sum(array_column($stats['registrationInterest'], 'total')) ?> registrations total</p>
                        <h3>Registration by Interest</h3>
                    </div>
                    <a class="overview-card-action" href="registrations.php" aria-label="View registrations">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <?php if ($stats['registrationInterest']) : ?>
                    <div class="admin-interest-list">
                        <?php foreach (array_slice($stats['registrationInterest'], 0, 5) as $interest) : ?>
                            <div class="admin-progress-item">
                                <div class="admin-progress-heading">
                                    <span><?= htmlspecialchars($interest['name']) ?></span>
                                    <strong><?= (int) $interest['total'] ?> · <?= (int) $interest['percentage'] ?>%</strong>
                                </div>
                                <div class="progress-track" role="img" aria-label="<?= htmlspecialchars($interest['name']) ?>: <?= (int) $interest['total'] ?> registrations, <?= (int) $interest['percentage'] ?> percent">
                                    <div class="progress-fill" style="width: <?= max(0, min(100, (float) $interest['percentage'])) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="chart-empty">No registrations yet.</p>
                <?php endif; ?>
            </article>

            <article class="overview-card campaign-overview-card">
                <div class="overview-card-heading">
                    <div>
                        <p class="overview-card-kicker"><?= (int) $stats['activeCampaigns'] ?> active</p>
                        <h3>Campaign Progress</h3>
                    </div>
                    <a class="overview-card-action" href="manageCampaigns.php" aria-label="View campaigns">
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </div>
                <?php if ($stats['campaigns']) : ?>
                    <div class="admin-campaign-list">
                        <?php foreach (array_slice($stats['campaigns'], 0, 3) as $campaign) : ?>
                            <div class="admin-progress-item">
                                <div class="admin-progress-heading">
                                    <span><?= htmlspecialchars($campaign['name']) ?></span>
                                    <strong><?= (int) $campaign['percentage'] ?>%</strong>
                                </div>
                                <div class="progress-track" role="img" aria-label="<?= htmlspecialchars($campaign['name']) ?>: <?= (int) $campaign['percentage'] ?> percent funded">
                                    <div class="progress-fill" style="width: <?= max(0, min(100, (float) $campaign['percentage'])) ?>%;"></div>
                                </div>
                                <small><?= htmlspecialchars(formatRand($campaign['raised'])) ?> of <?= htmlspecialchars(formatRand($campaign['goal'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="chart-empty">No active campaigns.</p>
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