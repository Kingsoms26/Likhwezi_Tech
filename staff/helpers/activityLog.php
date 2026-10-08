<?php
// activityLog.php records everything a staff member does for the admin activity log page
// rows are never edited or deleted and the username is copied in so the log still reads right after an account changes
// targetID has no foreign key so a row survives when the record is deleted
// logging never breaks the action it records, failures go to the error log
// the table is set up in database/dbSetup.md

// each category and the label shown in the filter, keep in step with the logActivity() calls
const ACTIVITY_CATEGORIES = [
    'Login'        => 'Logins',
    'Account'      => 'Accounts',
    'Profile'      => 'Profiles',
    'Enquiry'      => 'Enquiries',
    'Registration' => 'Registrations',
    'Campaign'     => 'Campaigns',
    'Event'        => 'Events & Gallery',
    'Partner'      => 'Partners',
    'Content'      => 'Pages & Site info',
    'Announcement' => 'Announcements',
    'Archive'      => 'Archive',
];

// rows per page
const ACTIVITY_PER_PAGE = 25;

// record one action against the logged in user
// pass $actor for actions with no session yet like a failed login
function logActivity(mysqli $conn, string $category, string $action, string $description, ?int $targetID = null, ?array $actor = null): void
{
    $accountID = $actor ? ($actor['accountID'] ?? null) : ($_SESSION['accountID'] ?? null);
    $username = $actor ? ($actor['username'] ?? '') : ($_SESSION['username'] ?? '');
    $accountID = $accountID === null ? null : (int) $accountID;
    $username = mb_substr((string) $username, 0, 255);
    $description = mb_substr($description, 0, 500);
    $ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null;

    $stmt = $conn->prepare(
        "INSERT INTO ActivityLog (accountID, username, category, action, description, targetID, ipAddress)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log('logActivity failed (has the ActivityLog table from database/dbSetup.md been created?): ' . $conn->error);
        return;
    }

    $stmt->bind_param("issssis", $accountID, $username, $category, $action, $description, $targetID, $ip);

    if (!$stmt->execute()) {
        error_log('logActivity failed: ' . $stmt->error);
    }
}

// reading the log for admin

// read and clean the activity log filters from the url, invalid values mean no filter
function readActivityFilters(array $query): array
{
    $date = fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
        && DateTime::createFromFormat('Y-m-d', $value) ? $value : '';

    $category = (string) ($query['category'] ?? '');

    return [
        'account'  => ctype_digit((string) ($query['account'] ?? '')) ? (int) $query['account'] : 0,
        'category' => isset(ACTIVITY_CATEGORIES[$category]) ? $category : '',
        'search'   => trim(mb_substr((string) ($query['search'] ?? ''), 0, 100)),
        'from'     => $date($query['from'] ?? ''),
        'to'       => $date($query['to'] ?? ''),
        'page'     => max(1, (int) ($query['page'] ?? 1)),
    ];
}

// build the query conditions for the filters
function activityWhere(array $filters): array
{
    $where = [];
    $types = '';
    $params = [];

    if ($filters['account']) {
        $where[] = 'l.accountID = ?';
        $types .= 'i';
        $params[] = $filters['account'];
    }
    if ($filters['category'] !== '') {
        $where[] = 'l.category = ?';
        $types .= 's';
        $params[] = $filters['category'];
    }
    if ($filters['search'] !== '') {
        $where[] = '(l.description LIKE ? OR l.username LIKE ?)';
        $like = '%' . addcslashes($filters['search'], '%_\\') . '%';
        $types .= 'ss';
        array_push($params, $like, $like);
    }
    if ($filters['from'] !== '') {
        $where[] = 'l.createdAt >= ?';
        $types .= 's';
        $params[] = $filters['from'] . ' 00:00:00';
    }
    if ($filters['to'] !== '') {
        // include the whole of the to day
        $where[] = 'l.createdAt < DATE_ADD(?, INTERVAL 1 DAY)';
        $types .= 's';
        $params[] = $filters['to'];
    }

    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $types, $params];
}

// columns for the page and the csv, the log row plus the staff member's current name and archive state
const ACTIVITY_SELECT_SQL =
    "SELECT l.logID, l.accountID, l.username, l.category, l.action, l.description,
            l.targetID, l.ipAddress, l.createdAt,
            NULLIF(TRIM(CONCAT_WS(' ', u.firstName, u.lastName)), '') AS fullName,
            u.isArchived AS actorArchived
     FROM ActivityLog l
     LEFT JOIN UserAccount u ON u.accountID = l.accountID";

// one page of log rows matching the filters, newest first
function getActivityLog(mysqli $conn, array $filters, int $perPage = ACTIVITY_PER_PAGE): array
{
    [$where, $types, $params] = activityWhere($filters);
    $empty = ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1];

    // count the matching rows
    $countStmt = $conn->prepare('SELECT COUNT(*) FROM ActivityLog l' . $where);
    if (!$countStmt) {
        error_log('getActivityLog failed (has the ActivityLog table from database/dbSetup.md been created?): ' . $conn->error);
        return $empty;
    }
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_row()[0];

    // work out the pages then fetch this page's rows
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($filters['page'], $pages);
    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare(ACTIVITY_SELECT_SQL . $where . ' ORDER BY l.createdAt DESC, l.logID DESC LIMIT ? OFFSET ?');
    $stmt->bind_param($types . 'ii', ...[...$params, $perPage, $offset]);
    $stmt->execute();

    return [
        'rows'  => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'total' => $total,
        'page'  => $page,
        'pages' => $pages,
    ];
}

// every row matching the filters for the csv download, streamed instead of held in memory
function getActivityLogResult(mysqli $conn, array $filters): ?mysqli_result
{
    [$where, $types, $params] = activityWhere($filters);

    $stmt = $conn->prepare(ACTIVITY_SELECT_SQL . $where . ' ORDER BY l.createdAt DESC, l.logID DESC');
    if (!$stmt) {
        error_log('getActivityLogResult failed: ' . $conn->error);
        return null;
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt->get_result() ?: null;
}

// every account for the user filter, archived ones included so their history can still be found
function getActivityAccounts(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT accountID, username, firstName, lastName, isArchived
         FROM UserAccount
         ORDER BY isArchived, username"
    );

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// summary for one account, how many actions and when the first and last were
function getAccountActivitySummary(mysqli $conn, int $accountID): array
{
    $stmt = $conn->prepare("SELECT COUNT(*), MIN(createdAt), MAX(createdAt) FROM ActivityLog WHERE accountID = ?");
    if (!$stmt) {
        return ['total' => 0, 'firstAt' => null, 'lastAt' => null];
    }
    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    [$total, $firstAt, $lastAt] = $stmt->get_result()->fetch_row();

    return ['total' => (int) $total, 'firstAt' => $firstAt, 'lastAt' => $lastAt];
}
