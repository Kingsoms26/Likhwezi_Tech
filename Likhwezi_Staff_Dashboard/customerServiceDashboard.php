<?php
/*
 * CUSTOMER SERVICE DASHBOARD*/
$_SESSION['username'] = $_SESSION['username'] ?? 'Name Surname';
$_SESSION['role'] = $_SESSION['role'] ?? 'Customer Service';

/* ---------------------------------------------------------------------
 * SAMPLE DATA (hardcoded for the visual preview)
 * --------------------------------------------------------------------- */

/* Enquiry counts for the first two cards. open = new + contacted. */
$enquiryStats = [
    'new' => 3,
    'contacted' => 4,
    'open' => 7
];

/*
 * Open enquiries: New first, then Contacted.
 * Statuses: New, Contacted, Closed. Meeting types: Virtual, Face-to-face.
 */
$openEnquiries = [
    ['name' => 'Thandeka Mthethwa', 'companyName' => 'Mthethwa Logistics', 'meetingType' => 'Virtual', 'status' => 'New'],
    ['name' => 'David Pillay', 'companyName' => '', 'meetingType' => 'Face-to-face', 'status' => 'New'],
    ['name' => 'Lerato Moloi', 'companyName' => 'Bright Path Finance', 'meetingType' => 'Virtual', 'status' => 'New'],
    ['name' => 'Johan van Wyk', 'companyName' => 'Van Wyk Agri', 'meetingType' => 'Virtual', 'status' => 'Contacted'],
    ['name' => 'Nokuthula Sibiya', 'companyName' => 'Sibiya & Partners', 'meetingType' => 'Face-to-face', 'status' => 'Contacted']
];

/* Registrations received in the last 7 days (third card). */
$registrationStats = [
    'lastSevenDays' => 4
];

/* Latest registrations, newest first. Age is calculated from date of birth in the real data. */
$recentRegistrations = [
    ['name' => 'Lindiwe Dlamini', 'programme' => 'Robotics', 'age' => 15, 'isMinor' => true, 'registered' => '1 Oct 2026'],
    ['name' => 'Sipho Mahlangu', 'programme' => 'Coding', 'age' => 22, 'isMinor' => false, 'registered' => '29 Sep 2026'],
    ['name' => 'Ayanda Khumalo', 'programme' => 'Artificial Intelligence', 'age' => 17, 'isMinor' => true, 'registered' => '28 Sep 2026'],
    ['name' => 'Karabo Molefe', 'programme' => 'Hackathons', 'age' => 34, 'isMinor' => false, 'registered' => '26 Sep 2026'],
    ['name' => 'Thabo Nkosi', 'programme' => 'Coding', 'age' => 12, 'isMinor' => true, 'registered' => '23 Sep 2026']
];

/* --------------------------------------------------------------------- */

/* CSS class for each enquiry status badge: status-new, status-contacted, status-closed. */
function enquiryStatusClass($status)
{
    return 'status-' . strtolower($status);
}

$pageTitle = 'Customer Service Dashboard';
$activePage = 'dashboard';

/* Open the shared dashboard shell. */
include __DIR__ . '/components/dashboard.php';
?>

<!--
    CUSTOMER SERVICE CONTENT

    The role wrapper keeps Customer Service CSS scoped to this page:
        .customer-service-dashboard .dashboard-card { ... }
-->
<div class="customer-service-dashboard">

    <section class="dashboard-cards">

        <?php
        /* Enquiries nobody has picked up yet. First, because it is the most actionable. */
        $cardTitle = 'Awaiting Response';
        $cardValue = $enquiryStats['new'];
        $cardMeta = $enquiryStats['new'] === 1 ? 'New enquiry not yet contacted' : 'New enquiries not yet contacted';
        $cardClass = 'enquiries-awaiting-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        /* Everything not yet closed. */
        $cardTitle = 'Open Enquiries';
        $cardValue = $enquiryStats['open'];
        $cardMeta = $enquiryStats['new'] . ' new, ' . $enquiryStats['contacted'] . ' contacted';
        $cardClass = 'enquiries-open-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        /* Registrations received today and in the previous six days. */
        $cardTitle = 'New Registrations';
        $cardValue = $registrationStats['lastSevenDays'];
        $cardMeta = 'In the last 7 days';
        $cardClass = 'registrations-recent-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <section class="dashboard-two-column">

        <!-- OPEN ENQUIRIES: New first, then Contacted. -->
        <section class="dashboard-panel enquiries-panel">
            <div class="panel-header">
                <h2>Enquiries Needing Attention</h2>
                <a class="panel-button" href="enquiries.php">View all enquiries</a>
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
                                            <?php if ($enquiry['companyName'] !== '') : ?>
                                                <span class="snapshot-sub"><?= htmlspecialchars($enquiry['companyName']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($enquiry['meetingType']) ?></td>
                                        <td>
                                            <span class="status-badge <?= enquiryStatusClass($enquiry['status']) ?>">
                                                <?= htmlspecialchars($enquiry['status']) ?>
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

        <!-- RECENT REGISTRATIONS: latest five. -->
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
                                            <span class="snapshot-name"><?= htmlspecialchars($registration['name']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($registration['programme']) ?></td>
                                        <td class="snapshot-nowrap">
                                            <?= htmlspecialchars($registration['age']) ?>
                                            <?php if ($registration['isMinor']) : ?>
                                                <span class="minor-badge">Minor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="snapshot-nowrap">
                                            <?= htmlspecialchars($registration['registered']) ?>
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

<!--
Page end
Closes main, .dashboard-body and .dashboard opened by components/dashboard.php.
-->
</main>
</div>
</div>

</body>
</html>
