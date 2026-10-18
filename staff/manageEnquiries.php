<?php
    // manageEnquiries.php is the enquiries page for admin and staff
    // contains the unclaimed enquiries, my enquiries for staff and all enquiries for admin
    // closed enquiries have their own page, closedEnquiries.php

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/enquiryData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Staff']);

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
    $enquiries = getEnquiries($conn, $showArchived);

    // split the enquiries into unclaimed and mine
    $isStaff = $account['role'] === 'Staff';
    $activeEnquiries = array_filter($enquiries, fn ($e) => !$e['isArchived']);
    $unclaimedEnquiries = array_values(array_filter(
        $activeEnquiries,
        fn ($e) => $e['handledBy'] === null && $e['status'] === 'new'
    ));
    $myEnquiries = array_values(array_filter(
        $activeEnquiries,
        fn ($e) => (int) $e['handledBy'] === (int) $account['accountID'] && $e['status'] !== 'closed'
    ));
    $newEnquiries = array_values(array_filter($activeEnquiries, fn ($e) => $e['status'] === 'new'));

    $enquiryTabs = getEnquiryTabs($enquiries, $account);
    $activeTab = $_GET['view'] ?? 'new';
    if (!isset($enquiryTabs[$activeTab]) || $activeTab === 'closed') {
        $activeTab = 'new';
    }

    $pageTitle = 'Enquiries';
    $activePage = 'enquiries';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="enquiries-page">

    <!-- success and error messages -->
    <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

    <!-- enquiry count cards -->
    <?php include __DIR__ . '/components/enquiryTabs.php'; ?>

    <?php if ($activeTab === 'new') : ?>
        <section class="dashboard-panel enquiries-panel" id="new-enquiries">
            <div class="panel-body">
                <?php
                $tableEnquiries = $newEnquiries;
                $tableEmpty = 'No new enquiries.';
                include __DIR__ . '/components/enquiryTable.php';
                ?>
            </div>
        </section>
    <?php elseif ($activeTab === 'unclaimed') : ?>
        <section class="dashboard-panel enquiries-panel" id="unclaimed-enquiries">
            <div class="panel-body">
                <?php
                $tableEnquiries = $unclaimedEnquiries;
                $tableEmpty = 'No unclaimed enquiries. Everything new has been picked up.';
                include __DIR__ . '/components/enquiryTable.php';
                ?>
            </div>
        </section>
    <?php elseif ($activeTab === 'mine') : ?>
        <section class="dashboard-panel enquiries-panel" id="my-enquiries">
            <div class="panel-body">
                <?php
                $tableEnquiries = $myEnquiries;
                $tableEmpty = 'You have no open enquiries. Claim one from Unclaimed Enquiries.';
                include __DIR__ . '/components/enquiryTable.php';
                ?>
            </div>
        </section>
    <?php else : ?>
        <section class="dashboard-panel enquiries-panel" id="all-enquiries">

            <div class="panel-header">
                <a class="panel-button" href="manageEnquiries.php?view=all<?= $showArchived ? '' : '&showArchived=1' ?>">
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