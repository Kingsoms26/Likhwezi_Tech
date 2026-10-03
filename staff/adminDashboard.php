<?php
    // adminDashboard.php is the admin dashboard
    // contains the summary cards, open enquiries, registrations by interest and campaign progress

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    require __DIR__ . '/helpers/dashboardData.php';

    // fetch the enquiries, campaigns, funds and registrations
    $stats = getDashboardStats($conn);

    $pageTitle = 'Dashboard';
    $activePage = 'dashboard';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<!-- admin content, the wrapper keeps the admin css scoped to this page -->
<div class="admin-dashboard">

    <!-- summary cards -->
    <section class="dashboard-cards">

        <?php
        // active enquiries card
        $cardTitle = 'Active Enquiries';
        $cardValue = $stats['activeEnquiries'];
        $cardMeta = 'Currently open';
        $cardClass = 'enquiries-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // active campaigns card
        $cardTitle = 'Active Campaigns';
        $cardValue = $stats['activeCampaigns'];
        $cardMeta = 'Currently active';
        $cardClass = 'campaigns-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // total funds raised card
        $cardTitle = 'Total Funds Raised';
        $cardValue = 'R' . number_format($stats['fundsRaised'], 2);
        $cardMeta = 'Across all campaigns';
        $cardClass = 'donations-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <section class="dashboard-two-column">

        <!-- open enquiries -->
        <section class="dashboard-panel enquiries-panel">

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

                <table class="dashboard-table">
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

        </section>

        <!-- registrations by interest -->
        <section class="dashboard-panel registration-interest-panel">

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

                        <div class="progress-track">
                            <div class="progress-fill" style="width: <?= max(0, min(100, (float) $interest['percentage'])) ?>%;"></div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    </section>

    <!-- campaign progress -->
    <section class="dashboard-panel campaigns-panel">

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