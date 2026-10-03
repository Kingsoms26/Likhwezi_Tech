<?php
// notifications.php gives the header the latest notifications as json
// polled by components/dashboardHeader.php so the bell updates without reloading the page
// a logged out account gets a 401 instead of the login redirect so the polling can stop

require_once __DIR__ . '/../includes/security.php';

require __DIR__ . '/../config/dbConnection.php';
require __DIR__ . '/helpers/auth.php';
require __DIR__ . '/helpers/notificationData.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// without this check requireStaff() would redirect to the login page which fetch cannot use
if (!isset($_SESSION['accountID'])) {
    http_response_code(401);
    echo json_encode(['notifications' => []]);
    exit;
}

// fetch the notifications for this account
$account = requireStaff($conn);
$notifications = getNotifications($conn, $account);

// release the session lock so this poll never holds up other requests
session_write_close();

// send back only the fields the header needs
echo json_encode([
    'notifications' => array_map(fn ($note) => [
        'id'     => $note['id'],
        'level'  => $note['level'],
        'title'  => $note['title'],
        'detail' => $note['detail'],
        'link'   => $note['link'],
    ], $notifications),
], JSON_INVALID_UTF8_SUBSTITUTE);
