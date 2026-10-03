<?php
// archiveData.php lists, restores and permanently deletes archived items for the admin archive page
// only archived items can be deleted and deleting also removes their archive log rows
// events and campaigns take their photos with them and partners take their logo
// campaigns with donations are never deleted since donations are financial records

require_once __DIR__ . '/galleryData.php';
require_once __DIR__ . '/partnerData.php';

// items per page
const ARCHIVE_PER_PAGE = 15;

// each type that can be archived with its table, id column and the query for its archived rows
const ARCHIVE_TYPES = [
    'enquiry' => [
        'label' => 'Enquiries', 'singular' => 'Enquiry', 'table' => 'Enquiry', 'id' => 'enquiryID',
        'select' => "SELECT 'enquiry' AS type, enquiryID AS id, name AS title,
                            LEFT(description, 120) AS detail, 0 AS donations, 0 AS photos
                     FROM Enquiry WHERE isArchived = TRUE",
    ],
    'registration' => [
        'label' => 'Registrations', 'singular' => 'Registration', 'table' => 'Registration', 'id' => 'registrationID',
        'select' => "SELECT 'registration', registrationID, CONCAT(firstName, ' ', lastName),
                            programme, 0, 0
                     FROM Registration WHERE isArchived = TRUE",
    ],
    'partner' => [
        'label' => 'Partners', 'singular' => 'Partner', 'table' => 'Partner', 'id' => 'partnerID',
        'select' => "SELECT 'partner', partnerID, name, COALESCE(websiteURL, ''), 0, 0
                     FROM Partner WHERE isArchived = TRUE",
    ],
    'campaign' => [
        'label' => 'Campaigns', 'singular' => 'Campaign', 'table' => 'Campaign', 'id' => 'campaignID',
        'select' => "SELECT 'campaign', c.campaignID, c.name, CONCAT('Goal R', FORMAT(c.goal, 2)),
                            (SELECT COUNT(*) FROM Donation d WHERE d.campaignID = c.campaignID),
                            (SELECT COUNT(*) FROM GalleryItem g WHERE g.campaignID = c.campaignID)
                     FROM Campaign c WHERE c.isArchived = TRUE",
    ],
    'event' => [
        'label' => 'Events', 'singular' => 'Event', 'table' => 'Event', 'id' => 'eventID',
        'select' => "SELECT 'event', e.eventID, e.name,
                            COALESCE(DATE_FORMAT(e.eventDate, '%e %b %Y'), 'No date'), 0,
                            (SELECT COUNT(*) FROM GalleryItem g WHERE g.eventID = e.eventID)
                     FROM Event e WHERE e.isArchived = TRUE",
    ],
];

// every archived row of every type in one query
function archiveUnionSQL(): string
{
    return implode(' UNION ALL ', array_column(ARCHIVE_TYPES, 'select'));
}

// number of archived items per type plus the total
function getArchiveCounts(mysqli $conn): array
{
    $counts = array_fill_keys(array_keys(ARCHIVE_TYPES), 0);

    $result = $conn->query("SELECT type, COUNT(*) AS total FROM (" . archiveUnionSQL() . ") a GROUP BY type");
    if (!$result) {
        error_log('getArchiveCounts failed: ' . $conn->error);
        return ['all' => 0] + $counts;
    }

    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $counts[$row['type']] = (int) $row['total'];
    }

    return ['all' => array_sum($counts)] + $counts;
}

// one page of archived items, most recently archived first, filtered by type and search
function getArchivedItems(mysqli $conn, string $type, string $search, int $page): array
{
    $where = [];
    $types = '';
    $params = [];

    if ($type !== 'all') {
        $where[] = 'a.type = ?';
        $types .= 's';
        $params[] = $type;
    }
    if ($search !== '') {
        $where[] = '(a.title LIKE ? OR a.detail LIKE ?)';
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $types .= 'ss';
        array_push($params, $like, $like);
    }

    // filters go inside the query so the joins below can follow it
    $from = " FROM (SELECT * FROM (" . archiveUnionSQL() . ") a"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ") a";

    // count the matching items
    $count = $conn->prepare('SELECT COUNT(*)' . $from);
    if (!$count) {
        error_log('getArchivedItems failed: ' . $conn->error);
        return ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
    }
    if ($params) {
        $count->bind_param($types, ...$params);
    }
    $count->execute();
    $total = (int) $count->get_result()->fetch_row()[0];

    // work out the pages
    $pages = max(1, (int) ceil($total / ARCHIVE_PER_PAGE));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * ARCHIVE_PER_PAGE;
    $limit = ARCHIVE_PER_PAGE;

    // who archived it and when from the latest archive log entry
    $stmt = $conn->prepare(
        "SELECT a.*, l.timestamp AS archivedAt,
                COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.firstName, u.lastName)), ''), u.username) AS archivedBy"
        . $from . "
         LEFT JOIN (
             SELECT entityID, MAX(archiveID) AS lastID
             FROM ArchiveLog
             WHERE action = 'archived'
             GROUP BY entityID
         ) latest ON latest.entityID = a.id
         LEFT JOIN ArchiveLog l ON l.archiveID = latest.lastID
         LEFT JOIN UserAccount u ON u.accountID = l.performedBy
         ORDER BY l.timestamp IS NULL, l.timestamp DESC, a.id DESC
         LIMIT ? OFFSET ?"
    );
    if (!$stmt) {
        error_log('getArchivedItems failed: ' . $conn->error);
        return ['rows' => [], 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
    $stmt->bind_param($types . 'ii', ...[...$params, $limit, $offset]);
    $stmt->execute();

    $rows = array_map(function ($row) {
        $row['id'] = (int) $row['id'];
        $row['donations'] = (int) $row['donations'];
        $row['photos'] = (int) $row['photos'];
        return $row;
    }, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

// turn the posted items into a type and id each, dropping anything broken or repeated
function parseArchiveItems(array $values): array
{
    $items = [];

    foreach ($values as $value) {
        if (is_string($value) && preg_match('/^([a-z]+):(\d+)$/', $value, $m) && isset(ARCHIVE_TYPES[$m[1]])) {
            $items[$m[1] . ':' . $m[2]] = [$m[1], (int) $m[2]];
        }
    }

    return array_values($items);
}

// an archived item's name, or null if it is not in the archive
function getArchivedItemName(mysqli $conn, string $type, int $id): ?string
{
    $config = ARCHIVE_TYPES[$type];
    $name = $type === 'registration' ? "CONCAT(firstName, ' ', lastName)" : 'name';

    $stmt = $conn->prepare("SELECT $name FROM `{$config['table']}` WHERE `{$config['id']}` = ? AND isArchived = TRUE");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();

    return $row ? $row[0] : null;
}

// restore an archived item and log who did it, returns an error message or null when it works
function restoreArchivedItem(mysqli $conn, string $type, int $id, int $accountID): ?string
{
    $config = ARCHIVE_TYPES[$type];

    $conn->begin_transaction();

    $update = $conn->prepare("UPDATE `{$config['table']}` SET isArchived = FALSE WHERE `{$config['id']}` = ? AND isArchived = TRUE");
    $update->bind_param('i', $id);

    if (!$update->execute() || $update->affected_rows !== 1) {
        $conn->rollback();
        return "{$config['singular']} #$id is no longer in the archive.";
    }

    $log = $conn->prepare("INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, 'restored')");
    $log->bind_param('ii', $id, $accountID);

    if (!$log->execute()) {
        $conn->rollback();
        return "{$config['singular']} #$id could not be restored.";
    }

    $conn->commit();
    return null;
}

// permanently delete an archived item, files are only removed once the database delete has saved
// returns an error message or null when it works
function deleteArchivedItem(mysqli $conn, string $type, int $id): ?string
{
    $config = ARCHIVE_TYPES[$type];
    $name = getArchivedItemName($conn, $type, $id);

    if ($name === null) {
        return "{$config['singular']} #$id is no longer in the archive.";
    }

    // campaigns with donations cannot be deleted
    if ($type === 'campaign') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Donation WHERE campaignID = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int) $stmt->get_result()->fetch_row()[0] > 0) {
            return "'$name' has donations, which must be kept for financial records, so it cannot be deleted.";
        }
    }

    // files to remove once the rows are gone
    $photos = [];
    if ($type === 'event' || $type === 'campaign') {
        $stmt = $conn->prepare("SELECT galleryItemID, image FROM GalleryItem WHERE `{$config['id']}` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $logo = null;
    if ($type === 'partner') {
        $stmt = $conn->prepare("SELECT logo FROM Partner WHERE partnerID = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $logo = $stmt->get_result()->fetch_row()[0] ?? null;
    }

    // the item and its photos all have archivable entity and maybe archive log rows
    $entityIDs = array_merge([$id], array_map('intval', array_column($photos, 'galleryItemID')));
    $placeholders = implode(', ', array_fill(0, count($entityIDs), '?'));
    $intTypes = str_repeat('i', count($entityIDs));

    $conn->begin_transaction();

    // delete the linked rows in order rather than relying on cascade
    $deleteLogs = $conn->prepare("DELETE FROM ArchiveLog WHERE entityID IN ($placeholders)");
    $deleteLogs->bind_param($intTypes, ...$entityIDs);

    $deletePhotos = $conn->prepare("DELETE FROM GalleryItem WHERE galleryItemID IN ($placeholders)");
    $deletePhotos->bind_param($intTypes, ...$entityIDs);

    $deleteItem = $conn->prepare("DELETE FROM `{$config['table']}` WHERE `{$config['id']}` = ? AND isArchived = TRUE");
    $deleteItem->bind_param('i', $id);

    $deleteEntities = $conn->prepare("DELETE FROM ArchivableEntity WHERE entityID IN ($placeholders)");
    $deleteEntities->bind_param($intTypes, ...$entityIDs);

    if (!$deleteLogs->execute() || !$deletePhotos->execute()
        || !$deleteItem->execute() || $deleteItem->affected_rows !== 1
        || !$deleteEntities->execute()) {
        error_log("deleteArchivedItem $type #$id failed: " . $conn->error);
        $conn->rollback();
        return "'$name' could not be deleted. Please try again.";
    }

    $conn->commit();

    // remove the files
    foreach ($photos as $photo) {
        deleteGalleryPhotoFile($photo['image']);
    }
    deletePartnerLogo($logo);

    return null;
}
