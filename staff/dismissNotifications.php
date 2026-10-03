<?php
// dismissNotifications.php hides notifications when one is tapped or clear all is pressed
// called by the notifications dropdown in components/dashboardHeader.php
// takes the csrf token and the ids to hide, answers with json ok true or false

session_start();

require __DIR__ . '/../config/dbConnection.php';
require __DIR__ . '/helpers/auth.php';
require __DIR__ . '/helpers/notificationData.php';

requireStaff($conn);

header('Content-Type: application/json');

// only accept a post with a valid csrf token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

// hide the notifications
dismissNotifications((array) ($_POST['ids'] ?? []));

echo json_encode(['ok' => true]);
