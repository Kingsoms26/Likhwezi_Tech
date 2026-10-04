<?php
// registrationData.php fetches the cym registrations for registrations.php and the dashboards
// returns data only, registrations come from the form on cym.php
// the form asks for an age not a date of birth so age is their age when they registered

// shown when there are no programmes in the database yet, same list as cym.php
const DEFAULT_REGISTRATION_PROGRAMMES = ['Coding', 'Artificial Intelligence', 'Robotics', 'Hackathons'];

// programmes for the filter, including any used on a registration that has since been renamed or removed
function getRegistrationProgrammes(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT name FROM (
             SELECT name, sortOrder FROM Programme
             UNION
             SELECT DISTINCT programme, 9999 FROM Registration
         ) p
         GROUP BY name
         ORDER BY MIN(sortOrder), name"
    );

    if (!$result) {
        error_log('getRegistrationProgrammes failed: ' . $conn->error);
        return DEFAULT_REGISTRATION_PROGRAMMES;
    }

    $programmes = array_column($result->fetch_all(MYSQLI_ASSOC), 'name');

    return $programmes ?: DEFAULT_REGISTRATION_PROGRAMMES;
}

// numbers for the summary cards, archived registrations are left out
function getRegistrationStats(mysqli $conn): array
{
    $stats = ['total' => 0, 'thisMonth' => 0, 'minors' => 0, 'lastSevenDays' => 0];

    $result = $conn->query(
        "SELECT COUNT(*) AS total,
                SUM(dateCreated >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS thisMonth,
                SUM(age < 18) AS minors,
                SUM(dateCreated >= CURDATE() - INTERVAL 6 DAY) AS lastSevenDays
         FROM Registration
         WHERE isArchived = FALSE"
    );

    if (!$result) {
        error_log('getRegistrationStats failed: ' . $conn->error);
        return $stats;
    }

    $row = $result->fetch_assoc();

    foreach ($stats as $key => $value) {
        $stats[$key] = (int) ($row[$key] ?? 0);
    }

    return $stats;
}

// columns the table can be sorted by and the direction used when one is first clicked
function getRegistrationSortColumns()
{
    return [
        'name' => 'asc',
        'programme' => 'asc',
        'age' => 'asc',
        'date' => 'desc',
        'media' => 'desc'
    ];
}

// read and clean the filters from the url, anything unexpected falls back to its default
function readRegistrationFilters(array $input, array $programmes)
{
    $view = ($input['view'] ?? '') === 'archived' ? 'archived' : 'active';

    $programme = $input['programme'] ?? '';
    if (!in_array($programme, $programmes, true)) {
        $programme = '';
    }

    $ageGroup = $input['age'] ?? '';
    if (!in_array($ageGroup, ['minor', 'adult'], true)) {
        $ageGroup = '';
    }

    // only accept real dates
    $validDate = function ($value) {
        $d = DateTime::createFromFormat('Y-m-d', (string) $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : '';
    };

    // newest first by default
    $sort = $input['sort'] ?? 'date';
    if (!array_key_exists($sort, getRegistrationSortColumns())) {
        $sort = 'date';
    }

    $dir = ($input['dir'] ?? '') === 'asc' ? 'asc' : (($input['dir'] ?? '') === 'desc' ? 'desc' : getRegistrationSortColumns()[$sort]);

    return [
        'view' => $view,
        'q' => trim(substr((string) ($input['q'] ?? ''), 0, 100)),
        'programme' => $programme,
        'age' => $ageGroup,
        'from' => $validDate($input['from'] ?? ''),
        'to' => $validDate($input['to'] ?? ''),
        'sort' => $sort,
        'dir' => $dir,
        'page' => max(1, (int) ($input['page'] ?? 1))
    ];
}

// columns for every registration, who archived it and when come from the latest archive log entry
const REGISTRATION_COLUMNS = "
    r.registrationID, r.firstName, r.lastName, r.age, r.email, r.phoneNumber,
    r.programme, r.consentGivenAt, r.mediaConsent,
    r.guardianName, r.guardianLastName, r.guardianRelationship,
    r.guardianEmail, r.guardianPhoneNumber, r.guardianConsentGivenAt,
    r.guardianCommunicationConsent, r.isArchived,
    r.dateCreated AS registeredAt,
    al.timestamp AS archivedAt,
    COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.firstName, u.lastName)), ''), u.username) AS archivedBy";

// join for who archived it and when
const REGISTRATION_ARCHIVE_JOIN = "
    LEFT JOIN (
        SELECT entityID, MAX(archiveID) AS archiveID
        FROM ArchiveLog
        WHERE action = 'archived'
        GROUP BY entityID
    ) latest ON latest.entityID = r.registrationID
    LEFT JOIN ArchiveLog al ON al.archiveID = latest.archiveID
    LEFT JOIN UserAccount u ON u.accountID = al.performedBy";

// tidy up the columns and mark whether they are under 18
function normaliseRegistration(array $row): array
{
    $row['registrationID'] = (int) $row['registrationID'];
    $row['age'] = (int) $row['age'];
    $row['isMinor'] = $row['age'] < 18;
    $row['mediaConsent'] = (bool) $row['mediaConsent'];
    $row['guardianCommunicationConsent'] = (bool) $row['guardianCommunicationConsent'];
    $row['isArchived'] = (bool) $row['isArchived'];

    return $row;
}

// make a search match % and _ as plain characters
function likeContains(string $value): string
{
    return '%' . addcslashes($value, '\\%_') . '%';
}

// where clause, bound values and order by for the filters and sort, shared by the table and the csv download
function buildRegistrationQuery(array $filters): array
{
    $wantArchived = $filters['view'] === 'archived';
    $where = ['r.isArchived = ?'];
    $types = 'i';
    $params = [$wantArchived ? 1 : 0];

    if ($filters['programme'] !== '') {
        $where[] = 'r.programme = ?';
        $types .= 's';
        $params[] = $filters['programme'];
    }

    if ($filters['age'] === 'minor') {
        $where[] = 'r.age < 18';
    } elseif ($filters['age'] === 'adult') {
        $where[] = 'r.age >= 18';
    }

    if ($filters['from'] !== '') {
        $where[] = 'r.dateCreated >= ?';
        $types .= 's';
        $params[] = $filters['from'] . ' 00:00:00';
    }

    if ($filters['to'] !== '') {
        $where[] = 'r.dateCreated < ? + INTERVAL 1 DAY';
        $types .= 's';
        $params[] = $filters['to'];
    }

    if ($filters['q'] !== '') {
        // phone numbers are matched without spaces
        $where[] = "(CONCAT(r.firstName, ' ', r.lastName) LIKE ?
                     OR r.email LIKE ?
                     OR REPLACE(r.phoneNumber, ' ', '') LIKE ?)";
        $types .= 'sss';
        $params[] = likeContains($filters['q']);
        $params[] = likeContains($filters['q']);
        $params[] = likeContains(str_replace(' ', '', $filters['q']));
    }

    $whereSql = implode(' AND ', $where);

    // sorting, only the listed columns reach the query and ties show newest first
    $dateColumn = $wantArchived ? 'al.timestamp' : 'r.dateCreated';
    $sortColumns = [
        'name' => 'r.firstName %1$s, r.lastName %1$s',
        'programme' => 'r.programme %1$s',
        'age' => 'r.age %1$s',
        'media' => 'r.mediaConsent %1$s',
        'date' => $dateColumn . ' %1$s'
    ];
    $direction = $filters['dir'] === 'asc' ? 'ASC' : 'DESC';
    $orderSql = sprintf($sortColumns[$filters['sort']], $direction) . ", $dateColumn DESC, r.registrationID DESC";

    return [$whereSql, $types, $params, $orderSql];
}

// one page of registrations matching the filters and sort, plus the active and archived counts
function getRegistrations(mysqli $conn, array $filters, $perPage = 10)
{
    $empty = ['rows' => [], 'totalRows' => 0, 'page' => 1, 'pages' => 1, 'counts' => ['active' => 0, 'archived' => 0]];

    // active and archived counts for the view toggle
    $countResult = $conn->query(
        "SELECT SUM(isArchived = FALSE) AS active, SUM(isArchived = TRUE) AS archived FROM Registration"
    );

    if (!$countResult) {
        error_log('getRegistrations failed: ' . $conn->error);
        return $empty;
    }

    $countRow = $countResult->fetch_assoc();
    $counts = ['active' => (int) ($countRow['active'] ?? 0), 'archived' => (int) ($countRow['archived'] ?? 0)];

    [$whereSql, $types, $params, $orderSql] = buildRegistrationQuery($filters);

    // count the matches and keep the page within range
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Registration r WHERE $whereSql");

    if (!$stmt) {
        error_log('getRegistrations failed: ' . $conn->error);
        return ['counts' => $counts] + $empty;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalRows = (int) $stmt->get_result()->fetch_row()[0];

    $pages = max(1, (int) ceil($totalRows / $perPage));
    $page = min($filters['page'], $pages);

    $stmt = $conn->prepare(
        "SELECT " . REGISTRATION_COLUMNS . "
         FROM Registration r " . REGISTRATION_ARCHIVE_JOIN . "
         WHERE $whereSql
         ORDER BY $orderSql
         LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        error_log('getRegistrations failed: ' . $conn->error);
        return ['counts' => $counts] + $empty;
    }

    // fetch this page
    $offset = ($page - 1) * $perPage;
    $types .= 'ii';
    $params[] = $perPage;
    $params[] = $offset;

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = array_map('normaliseRegistration', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    return [
        'rows' => $rows,
        'totalRows' => $totalRows,
        'page' => $page,
        'pages' => $pages,
        'counts' => $counts
    ];
}

// every registration matching the filters and sort, no paging, for the csv download
function getRegistrationsForExport(mysqli $conn, array $filters): array
{
    [$whereSql, $types, $params, $orderSql] = buildRegistrationQuery($filters);

    $stmt = $conn->prepare(
        "SELECT " . REGISTRATION_COLUMNS . "
         FROM Registration r " . REGISTRATION_ARCHIVE_JOIN . "
         WHERE $whereSql
         ORDER BY $orderSql"
    );

    if (!$stmt) {
        error_log('getRegistrationsForExport failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return array_map('normaliseRegistration', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// latest registrations newest first for the dashboards
function getRecentRegistrations(mysqli $conn, int $limit = 5): array
{
    $stmt = $conn->prepare(
        "SELECT registrationID, firstName, lastName, age, programme, dateCreated AS registeredAt
         FROM Registration
         WHERE isArchived = FALSE
         ORDER BY dateCreated DESC, registrationID DESC
         LIMIT ?"
    );

    if (!$stmt) {
        error_log('getRecentRegistrations failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();

    return array_map(function ($row) {
        $row['age'] = (int) $row['age'];
        $row['isMinor'] = $row['age'] < 18;
        return $row;
    }, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// registrations per programme, most popular first, with each one's share of the total
function getRegistrationsByProgramme(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT programme AS name, COUNT(*) AS total
         FROM Registration
         WHERE isArchived = FALSE
         GROUP BY programme
         ORDER BY total DESC, programme"
    );

    if (!$result) {
        error_log('getRegistrationsByProgramme failed: ' . $conn->error);
        return [];
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $sum = array_sum(array_column($rows, 'total'));

    return array_map(fn ($row) => [
        'name' => $row['name'],
        'total' => (int) $row['total'],
        'percentage' => $sum ? (int) round($row['total'] / $sum * 100) : 0
    ], $rows);
}

// archive or restore a registration and log who did it
function setRegistrationArchived(mysqli $conn, int $registrationID, bool $archive, int $accountID): bool
{
    $newState = $archive ? 1 : 0;
    $currentState = $archive ? 0 : 1;
    $action = $archive ? 'archived' : 'restored';

    $conn->begin_transaction();

    $update = $conn->prepare("UPDATE Registration SET isArchived = ? WHERE registrationID = ? AND isArchived = ?");

    if (!$update) {
        $conn->rollback();
        return false;
    }

    $update->bind_param("iii", $newState, $registrationID, $currentState);

    if (!$update->execute() || $update->affected_rows !== 1) {
        $conn->rollback();
        return false;
    }

    $log = $conn->prepare("INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, ?)");

    if (!$log) {
        $conn->rollback();
        return false;
    }

    $log->bind_param("iis", $registrationID, $accountID, $action);

    if (!$log->execute()) {
        $conn->rollback();
        return false;
    }

    return $conn->commit();
}
