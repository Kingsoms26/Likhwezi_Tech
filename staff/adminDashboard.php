<?php
    // adminDashboard.php keeps the admin entry point while sharing the staff dashboard
    // admins see exactly the same overview as staff, so this forwards straight to it

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';

    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    // keep any incoming options, such as the donation chart period
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: staffDashboard.php' . ($query !== '' ? '?' . $query : ''));
    exit;
