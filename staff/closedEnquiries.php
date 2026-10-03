<?php
    // closedEnquiries.php lists every closed enquiry, opened from the closed card on manageEnquiries.php
    // uses the same table and dialog so an enquiry can be reopened or archived here

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/enquiryData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Customer Service']);

    // show archived enquiries when asked
    $showArchived = isset($_GET['showArchived']);

    // handle the form actions before any html so the redirect works
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleEnquiryAction($conn, (int) $_SESSION['accountID']);

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // fetch the closed enquiries
    $flash = takeFlash();
    $closedEnquiries = array_values(array_filter(
        getEnquiries($conn, $showArchived),
        fn ($e) => $e['status'] === 'closed'
    ));

    $pageTitle = 'Closed Enquiries';
    $activePage = 'enquiries';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="enquiries-page">

    <!-- back link and archived toggle, the page title already names the page so the panel has no heading -->
    <div class="enquiries-toolbar">
        <a class="enquiries-back-link" href="manageEnquiries.php">Back to Enquiries</a>
        <a class="panel-button" href="closedEnquiries.php<?= $showArchived ? '' : '?showArchived=1' ?>">
            <?= $showArchived ? 'Hide archived' : 'Show archived' ?>
        </a>
    </div>

    <!-- success and error messages -->
    <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

    <!-- closed enquiries table -->
    <section class="dashboard-panel enquiries-panel" id="closed-enquiries">

        <div class="panel-body">
            <?php
            $tableEnquiries = $closedEnquiries;
            $tableEmpty = 'No closed enquiries yet.';
            include __DIR__ . '/components/enquiryTable.php';
            ?>
        </div>

    </section>

    <!-- enquiry details dialog -->
    <?php include __DIR__ . '/components/enquiryDialog.php'; ?>

</div>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>