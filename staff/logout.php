<?php
    // logout.php logs the staff member out and sends them back to the login page
    require_once __DIR__ . '/../includes/security.php';

    // record the logout in the activity log
    if (isset($_SESSION['accountID'])) {
        require __DIR__ . '/../config/dbConnection.php';
        require __DIR__ . '/helpers/activityLog.php';

        if (!$conn->connect_error) {
            logActivity($conn, 'Login', 'logout', 'Logged out', (int) $_SESSION['accountID']);
        }
    }

    // clear the session and go back to the login page
    $_SESSION = [];
    session_destroy();
    header('Location: ../staffLogin.php');
    exit;