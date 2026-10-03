<?php
    // manageEnquiries.php is the enquiries page for admin and customer service
    // contains the unclaimed enquiries, my enquiries for customer service and all enquiries for admin
    // closed enquiries have their own page, closedEnquiries.php

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/enquiryData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Customer Service']);

    // show archived enquiries when asked
    $showArchived = isset($_GET['showArchived']);

    // handle the form actions before any html so the redirect works
    // going back to the same page stops a refresh from submitting the form again
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleEnquiryAction($conn, (int) $_SESSION['accountID']);

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // fetch the enquiries and counts
    $flash = takeFlash();
    $counts = getEnquiryCounts($conn);
    $enquiries = getEnquiries($conn, $showArchived);

    // split the enquiries into unclaimed and mine
    $isCustomerService = $account['role'] === 'Customer Service';
    $activeEnquiries = array_filter($enquiries, fn ($e) => !$e['isArchived']);
    $unclaimedEnquiries = array_values(array_filter(
        $activeEnquiries,
        fn ($e) => $e['handledBy'] === null && $e['status'] === 'new'
    ));
    $myEnquiries = array_values(array_filter(
        $activeEnquiries,
        fn ($e) => (int) $e['handledBy'] === (int) $account['accountID'] && $e['status'] !== 'closed'
    ));

    $pageTitle = 'Enquiries';
    $activePage = 'enquiries';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="enquiries-page">

    <!-- success and error messages -->
    <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

    <!-- enquiry count cards -->
    <section class="dashboard-cards">

        <?php
        // new and unclaimed are one card, a new enquiry stops counting here once claimed
        $cardTitle = 'New';
        $cardValue = count($unclaimedEnquiries);
        $cardMeta = 'Not yet claimed';
        $cardClass = 'enquiries-new-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php if ($isCustomerService) : ?>
            <?php
            $cardTitle = 'My Enquiries';
            $cardValue = count($myEnquiries);
            $cardMeta = 'Claimed by you, still open';
            $cardClass = 'enquiries-mine-card';
            include __DIR__ . '/components/dashboardCard.php';
            ?>
        <?php endif; ?>

        <?php
        $cardTitle = 'Contacted';
        $cardValue = $counts['contacted'];
        $cardMeta = 'Response sent, in progress';
        $cardClass = 'enquiries-contacted-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        $cardTitle = 'Closed';
        $cardValue = $counts['closed'];
        $cardMeta = 'Resolved';
        $cardClass = 'enquiries-closed-card';
        $cardLink = 'closedEnquiries.php';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <!-- unclaimed enquiries -->
    <section class="dashboard-panel enquiries-panel" id="unclaimed-enquiries">
        <div class="panel-header">
            <h2>Unclaimed Enquiries</h2>
        </div>
        <div class="panel-body">
            <?php
            $tableEnquiries = $unclaimedEnquiries;
            $tableEmpty = 'No unclaimed enquiries. Everything new has been picked up.';
            include __DIR__ . '/components/enquiryTable.php';
            ?>
        </div>
    </section>

    <!-- my enquiries for customer service, all enquiries for everyone else -->
    <?php if ($isCustomerService) : ?>
        <section class="dashboard-panel enquiries-panel" id="my-enquiries">
            <div class="panel-header">
                <h2>My Enquiries</h2>
            </div>
            <div class="panel-body">
                <?php
                $tableEnquiries = $myEnquiries;
                $tableEmpty = 'You have no open enquiries. Claim one from Unclaimed above.';
                include __DIR__ . '/components/enquiryTable.php';
                ?>
            </div>
        </section>
    <?php else : ?>
        <section class="dashboard-panel enquiries-panel" id="all-enquiries">

            <div class="panel-header">
                <h2>All Enquiries</h2>
                <a class="panel-button" href="manageEnquiries.php<?= $showArchived ? '' : '?showArchived=1' ?>#all-enquiries">
                    <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
                </a>
            </div>

            <div class="panel-body">
                <?php
                $tableEnquiries = $enquiries;
                $tableEmpty = 'No enquiries to show.';
                include __DIR__ . '/components/enquiryTable.php';
                ?>
            </div>

        </section>
    <?php endif; ?>

    <!-- enquiry details dialog -->
    <?php include __DIR__ . '/components/enquiryDialog.php'; ?>

</div>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>