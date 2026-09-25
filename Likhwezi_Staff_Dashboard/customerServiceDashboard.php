<?php
/*
 * CUSTOMER SERVICE DASHBOARD TEMPLATE
 */

session_start();

$_SESSION['username'] = $_SESSION['username'] ?? 'Name Surname';
$_SESSION['role'] = $_SESSION['role'] ?? 'Customer Service';

require __DIR__ . '/tools/dashboardData.php';

$stats = getDashboardStats();
$pageTitle = 'Customer Service Dashboard';
$activePage = 'dashboard';

include __DIR__ . '/components/dashboard.php';
?>

<div class="customer-service-dashboard">

    <!--
        Put Customer Service dashboard content here.

        Shared data is available through $stats.
        Shared components can be used without duplicating their structure.
    -->

    <section class="dashboard-cards">

        <?php
        $cardTitle = 'Active Enquiries';
        $cardValue = $stats['activeEnquiries'];
        $cardMeta = 'Shared dashboard metric';
        $cardClass = 'enquiries-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

</div>


<!-- 
Page end
-->
</main>
</body>
</html>
