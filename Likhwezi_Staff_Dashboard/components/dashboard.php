<?php
/*
 * SHARED DASHBOARD SHELL
 * This component provides the common dashboard structure for all roles.
 * It creates:
 *     - the HTML document and head
 *     - the shared header
 *     - the shared sidebar
 *     - the opening main content area
 *
 * The role-specific page writes its own content after including this file.
 * The role-specific page is also responsible for closing the HTML structure
 * at the end of its own file.
 */

$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Dashboard styling is kept separate from the public website CSS. -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <div class="dashboard">

        <?php include __DIR__ . '/dashboardHeader.php'; ?>

        <div class="dashboard-body">

            <?php include __DIR__ . '/dashboardSidebar.php'; ?>

            <main class="dashboard-main">

                <div class="dashboard-heading">
                    <h1><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
