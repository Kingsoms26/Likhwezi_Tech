<?php
    // Keep the admin dashboard entry point while sharing the staff dashboard features.
    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';

    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    // Keep dashboard features and sample data identical for both roles, preserving incoming options such as the chart period.
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: staffDashboard.php' . ($query !== '' ? '?' . $query : ''));
    exit;
