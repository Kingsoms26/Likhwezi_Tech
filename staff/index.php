<?php
// staff/index.php sends each staff member to the dashboard for their role
// requireStaff() already turns away accounts without a role

session_start();

require __DIR__ . '/../config/dbConnection.php';
require __DIR__ . '/helpers/auth.php';

$account = requireStaff($conn);

// dashboard for each role, anyone else goes to their profile
$dashboards = [
    'Admin'            => 'adminDashboard.php',
    'Marketing'        => 'marketingDashboard.php',
    'Customer Service' => 'customerServiceDashboard.php',
];

header('Location: ' . ($dashboards[$account['role']] ?? 'profile.php'));
exit;
