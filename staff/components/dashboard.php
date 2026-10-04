<?php
    // dashboard.php is the shared layout for every staff page
    // contains the head, header, sidebar and the start of the main area
    // each page writes its own content after this and closes the html itself
    // notifications show on every page so they are loaded here once

    require_once __DIR__ . '/../helpers/notificationData.php';

    $pageTitle = $pageTitle ?? 'Dashboard';
    $activePage = $activePage ?? 'dashboard';
    $notifications = isset($conn, $account) ? getNotifications($conn, $account) : [];
    $sidebarCounts = isset($conn, $account) ? getSidebarCounts($conn) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Public Sans, the same font as the public site -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- bootstrap, loaded before dashboard.css so our styles win -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <!-- confirm and save as you change forms, used on every staff page -->
    <script src="assets/js/dashboardForms.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboardForms.js') ?>" defer></script>

    <!-- email links offer gmail or outlook when no email app opens, shared with the public site -->
    <script src="../assets/js/emailChooser.js?v=<?= filemtime(__DIR__ . '/../../assets/js/emailChooser.js') ?>" defer></script>
    <link rel="stylesheet" href="../assets/css/emailChooser.css?v=<?= filemtime(__DIR__ . '/../../assets/css/emailChooser.css') ?>">

    <!-- bootstrap icons, the same set the public site uses -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- dashboard styles are kept separate from the public website css -->
    <!-- the version number changes with every edit so browsers fetch the new css -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/dashboard.css') ?>">
</head>

<body>

    <div class="dashboard">

        <!-- header -->
        <?php include __DIR__ . '/dashboardHeader.php'; ?>

        <div class="dashboard-body">

            <!-- sidebar -->
            <?php include __DIR__ . '/dashboardSidebar.php'; ?>

            <!-- main area, the page fills this in -->
            <main class="dashboard-main">

                <div class="dashboard-heading">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
