<?php
/*
 * ADMIN DASHBOARD
 *
 * This page owns the Admin dashboard content.
 *
 * Shared data is obtained from tools/dashboardData.php.
 * The shared dashboard shell is components/dashboard.php.
 *
 * Flow:
 *     session_start()
 *          -> get dashboard data
 *          -> include shared shell
 *          -> write Admin content
 *          -> close the page structure
 *
 * No authentication or database connection is included in this framework.
 */

session_start();

/* Temporary session values used by the standalone framework. */
$_SESSION['username'] = $_SESSION['username'] ?? 'Name Surname';
$_SESSION['role'] = $_SESSION['role'] ?? 'Admin';

require __DIR__ . '/tools/dashboardData.php';

$stats = getDashboardStats();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

/* Open the shared dashboard shell. */
include __DIR__ . '/components/dashboard.php';
?>

<!--
    ADMIN-SPECIFIC CONTENT

    The role wrapper keeps Admin-only CSS scoped to this page:

        .admin-dashboard .dashboard-card { ... }

    Use semantic names for business-specific items, such as:
        enquiries-card
        campaigns-card
        donations-card
        enquiries-panel
        registration-interest-panel
        campaigns-panel
-->
<div class="admin-dashboard">

    <section class="dashboard-cards">

        <?php
        /* Active Enquiries statistic card. */
        $cardTitle = 'Active Enquiries';
        $cardValue = $stats['activeEnquiries'];
        $cardMeta = 'Currently open';
        $cardClass = 'enquiries-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        /* Active Campaigns statistic card. */
        $cardTitle = 'Active Campaigns';
        $cardValue = $stats['activeCampaigns'];
        $cardMeta = 'Currently active';
        $cardClass = 'campaigns-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        /* Total Funds Raised statistic card. */
        $cardTitle = 'Total Funds Raised';
        $cardValue = 'R' . number_format($stats['fundsRaised'], 2);
        $cardMeta = 'Across all campaigns';
        $cardClass = 'donations-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <section class="dashboard-two-column">

        <section class="dashboard-panel enquiries-panel">

            <div class="panel-header">
                <h2>Open Enquiries</h2>
                <a class="panel-button" href="#">View All</a>
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

        <section class="dashboard-panel registration-interest-panel">

            <div class="panel-header">
                <h2>Registration by Interest</h2>
                <a class="panel-button" href="#">View Report</a>
            </div>

            <div class="panel-body panel-padding">

                <?php foreach ($stats['registrationInterest'] as $interest) : ?>

                    <div class="interest-item">

                        <div class="interest-heading">
                            <span><?= htmlspecialchars($interest['name']) ?></span>
                            <span><?= htmlspecialchars($interest['percentage']) ?>%</span>
                        </div>

                        <div class="progress-track">
                            <div
                                class="progress-fill"
                                style="width: <?= max(0, min(100, (float) $interest['percentage'])) ?>%;"
                            ></div>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    </section>

    <section class="dashboard-panel campaigns-panel">

        <div class="panel-header">
            <h2>Campaigns</h2>
            <a class="panel-button" href="#">View All</a>
        </div>

        <div class="panel-body panel-padding">

            <?php foreach ($stats['campaigns'] as $campaign) : ?>

                <div class="campaign-row">

                    <div class="campaign-header">
                        <span><?= htmlspecialchars($campaign['name']) ?></span>
                        <span><?= htmlspecialchars($campaign['percentage']) ?>%</span>
                    </div>

                    <div class="progress-track">
                        <div
                            class="progress-fill"
                            style="width: <?= max(0, min(100, (float) $campaign['percentage'])) ?>%;"
                        ></div>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

</div>


<!-- 
Page end
-->
</main>

</body>
</html>
