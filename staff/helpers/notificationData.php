<?php
// notificationData.php builds the notifications in the header dropdown
// there is no notification table, each one is worked out from the data so it goes away once dealt with
// dismissing hides a notification for the session until its wording changes

require_once __DIR__ . '/profileData.php';

// upcoming events within this many days are shown to marketing
const NOTIFY_EVENT_DAYS = 7;

// notifications for the logged in account, most urgent first
function getNotifications(mysqli $conn, array $account): array
{
    $notifications = passwordNotifications($account);
    $role = $account['role'];

    if ($role === 'Admin' || $role === 'Customer Service') {
        $notifications = array_merge($notifications, enquiryNotifications($conn, $account));
    }
    if ($role === 'Admin') {
        $notifications = array_merge($notifications, profileNotifications($conn));
    }
    if ($role === 'Admin' || $role === 'Marketing') {
        $notifications = array_merge($notifications, eventNotifications($conn));
    }

    // give each one an id
    foreach ($notifications as &$note) {
        $note['id'] = notificationID($note);
    }
    unset($note);

    // forget dismissed ones that no longer exist then hide the rest
    $currentIDs = array_column($notifications, 'id');
    $dismissed = array_values(array_intersect($_SESSION['dismissedNotifications'] ?? [], $currentIDs));
    $_SESSION['dismissedNotifications'] = $dismissed;
    $notifications = array_values(array_filter($notifications, fn ($note) => !in_array($note['id'], $dismissed, true)));

    // most urgent first
    $order = ['danger' => 0, 'warning' => 1, 'info' => 2];
    usort($notifications, fn ($a, $b) => $order[$a['level']] <=> $order[$b['level']]);

    return $notifications;
}

// id that changes whenever the wording changes
function notificationID(array $note): string
{
    return substr(sha1($note['type'] . '|' . $note['title'] . '|' . $note['detail']), 0, 16);
}

// hide these notifications for the rest of the session
function dismissNotifications(array $ids): void
{
    $ids = array_filter($ids, fn ($id) => is_string($id) && preg_match('/^[0-9a-f]{16}$/', $id));
    $_SESSION['dismissedNotifications'] = array_values(array_unique(
        array_merge($_SESSION['dismissedNotifications'] ?? [], $ids)
    ));
}

// password change due soon or overdue
function passwordNotifications(array $account): array
{
    $age = passwordAge($account);

    if ($age['status'] === 'ok') {
        return [];
    }

    return [[
        'type'   => 'password',
        'level'  => $age['status'] === 'overdue' ? 'danger' : 'warning',
        'title'  => $age['status'] === 'overdue' ? 'Password change overdue' : 'Password change due soon',
        'detail' => passwordAgeLabel($age),
        'link'   => 'profile.php#change-password',
    ]];
}

// new enquiries, admin sees unclaimed ones and customer service also sees the ones they claimed
function enquiryNotifications(mysqli $conn, array $account): array
{
    $accountID = (int) $account['accountID'];

    $stmt = $conn->prepare(
        "SELECT SUM(handledBy IS NULL) AS unassigned,
                SUM(handledBy = ?) AS mine
         FROM Enquiry
         WHERE status = 'new' AND isArchived = FALSE"
    );
    if (!$stmt) {
        error_log('enquiryNotifications failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $counts = $stmt->get_result()->fetch_assoc();

    $unassigned = (int) ($counts['unassigned'] ?? 0);
    $mine = (int) ($counts['mine'] ?? 0);
    $notifications = [];

    if ($account['role'] === 'Customer Service' && $mine > 0) {
        $notifications[] = [
            'type'   => 'enquiry',
            'level'  => 'warning',
            'title'  => $mine === 1 ? '1 claimed enquiry still new' : "$mine claimed enquiries still new",
            'detail' => 'Waiting for first contact',
            'link'   => 'manageEnquiries.php#my-enquiries',
        ];
    }

    if ($unassigned > 0) {
        $notifications[] = [
            'type'   => 'enquiry',
            'level'  => 'info',
            'title'  => $unassigned === 1 ? '1 unclaimed enquiry' : "$unassigned unclaimed enquiries",
            'detail' => 'Not yet claimed by Customer Service',
            'link'   => 'manageEnquiries.php#unclaimed-enquiries',
        ];
    }

    return $notifications;
}

// for admin, active staff with no role yet who cannot use a dashboard
function profileNotifications(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT u.accountID, u.firstName, u.lastName, u.username
         FROM UserAccount u
         LEFT JOIN Admin a ON a.accountID = u.accountID
         LEFT JOIN StaffMarketing m ON m.accountID = u.accountID
         LEFT JOIN StaffCustomerService s ON s.accountID = u.accountID
         WHERE u.accountStatus = 'active'
           AND a.accountID IS NULL AND m.accountID IS NULL AND s.accountID IS NULL
         ORDER BY u.dateCreated DESC"
    );

    $notifications = [];

    foreach ($result ? $result->fetch_all(MYSQLI_ASSOC) : [] as $row) {
        $notifications[] = [
            'type'   => 'profile',
            'level'  => 'warning',
            'title'  => accountDisplayName($row) . ' has no role',
            'detail' => 'Assign a role so they can use the dashboard',
            'link'   => 'accounts.php?search=' . rawurlencode($row['username']),
        ];
    }

    return $notifications;
}

// for admin and marketing, events happening in the next 7 days
function eventNotifications(mysqli $conn): array
{
    $days = NOTIFY_EVENT_DAYS;

    $stmt = $conn->prepare(
        "SELECT name, eventDate, DATEDIFF(eventDate, CURDATE()) AS daysAway
         FROM Event
         WHERE isArchived = FALSE
           AND eventDate BETWEEN CURDATE() AND CURDATE() + INTERVAL ? DAY
         ORDER BY eventDate"
    );
    if (!$stmt) {
        error_log('eventNotifications failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $days);
    $stmt->execute();

    $notifications = [];

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $event) {
        // days away comes from the database so today matches the query even if php's timezone differs
        $daysAway = (int) $event['daysAway'];
        $when = match ($daysAway) {
            0       => 'Today',
            1       => 'Tomorrow',
            default => "In $daysAway days",
        };

        $notifications[] = [
            'type'   => 'event',
            'level'  => 'info',
            'title'  => $event['name'],
            'detail' => $when . ' (' . date('j M', strtotime($event['eventDate'])) . ')',
            'link'   => null,
        ];
    }

    return $notifications;
}

// counts for the badges beside the sidebar links, new enquiries and registrations from the last 7 days
// zero counts are left out so no badge shows
function getSidebarCounts(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT (SELECT COUNT(*) FROM Enquiry WHERE status = 'new' AND isArchived = FALSE) AS enquiries,
                (SELECT COUNT(*) FROM Registration WHERE isArchived = FALSE
                    AND dateCreated >= CURDATE() - INTERVAL 6 DAY) AS registrations"
    );

    if (!$result) {
        error_log('getSidebarCounts failed: ' . $conn->error);
        return [];
    }

    return array_filter(array_map('intval', $result->fetch_assoc()));
}
