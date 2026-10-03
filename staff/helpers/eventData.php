<?php
// eventData.php fetches and saves the events for manageEvents.php
// returns data only, includes/helpers/events.php decides what shows as past or upcoming on the public site
// an event with no date is past unless staff mark it to be confirmed
// archiving an event moves it to past events and its cover is its first photo
// admin and marketing can both add, edit and archive events

// every event newest first with its photo count, cover photo and who added it
// each one also says if it is upcoming or past and when it is like today or in 3 days
function getAllEvents(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT e.eventID, e.name, e.description, e.eventDate, e.dateToBeConfirmed, e.isArchived,
                DATEDIFF(e.eventDate, CURDATE()) AS daysAway,
                u.firstName AS creatorFirstName, u.lastName AS creatorLastName, u.username AS creatorUsername,
                (SELECT COUNT(*) FROM GalleryItem gi WHERE gi.eventID = e.eventID) AS photoCount,
                (SELECT gi.image FROM GalleryItem gi WHERE gi.eventID = e.eventID
                 ORDER BY gi.galleryItemID LIMIT 1) AS coverImage
         FROM Event e
         LEFT JOIN UserAccount u ON u.accountID = e.createdBy
         ORDER BY e.eventDate IS NULL, e.eventDate DESC, e.eventID DESC"
    );

    if (!$result) {
        error_log('getAllEvents failed: ' . $conn->error);
        return [];
    }

    return array_map('normaliseEvent', $result->fetch_all(MYSQLI_ASSOC));
}

// one event
function getEvent(mysqli $conn, int $eventID): ?array
{
    $stmt = $conn->prepare(
        "SELECT eventID, name, description, eventDate, dateToBeConfirmed, isArchived,
                DATEDIFF(eventDate, CURDATE()) AS daysAway
         FROM Event
         WHERE eventID = ?"
    );
    if (!$stmt) {
        error_log('getEvent failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $eventID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ? normaliseEvent($row) : null;
}

// tidy up the columns and work out if it is upcoming or past and when
function normaliseEvent(array $row): array
{
    $row['eventID'] = (int) $row['eventID'];
    $row['isArchived'] = (bool) $row['isArchived'];
    // only matters when there is no date
    $row['dateToBeConfirmed'] = $row['eventDate'] === null && (bool) $row['dateToBeConfirmed'];
    $row['photoCount'] = (int) ($row['photoCount'] ?? 0);
    $row['coverImage'] = $row['coverImage'] ?? null;
    $row['daysAway'] = $row['daysAway'] === null ? null : (int) $row['daysAway'];

    // name of whoever added it
    if (isset($row['creatorUsername'])) {
        $row['createdByName'] = accountFullName($row['creatorFirstName'], $row['creatorLastName']) ?: $row['creatorUsername'];
    } else {
        $row['createdByName'] = null;
    }

    // upcoming or past and when
    $days = $row['daysAway'];
    $row['timing'] = $days === null
        ? ($row['dateToBeConfirmed'] ? 'upcoming' : 'past')
        : ($days < 0 ? 'past' : 'upcoming');
    $row['when'] = match (true) {
        $days === null => $row['dateToBeConfirmed'] ? 'Date to be confirmed' : 'No date',
        $days === 0    => 'Today',
        $days === 1    => 'Tomorrow',
        $days === -1   => 'Yesterday',
        $days > 1      => "In $days days",
        default        => abs($days) . ' days ago',
    };

    return $row;
}

// counts for the summary cards, archived events are left out like on the public site
function getEventCounts(array $events, int $soonDays = 7): array
{
    $active = array_filter($events, fn ($e) => !$e['isArchived']);

    return [
        'upcoming' => count(array_filter($active, fn ($e) => $e['timing'] === 'upcoming')),
        'soon'     => count(array_filter($active, fn ($e) => $e['daysAway'] !== null && $e['daysAway'] >= 0 && $e['daysAway'] <= $soonDays)),
        'past'     => count(array_filter($active, fn ($e) => $e['timing'] === 'past')),
        'archived' => count($events) - count($active),
    ];
}

// check the add and edit form, the date is optional
// without a date staff choose whether it is to be confirmed or past
function validateEventInput(array &$input): array
{
    $errors = [];

    $input['name'] = trim($input['name'] ?? '');
    $input['description'] = trim($input['description'] ?? '');
    $input['eventDate'] = trim($input['eventDate'] ?? '');
    $input['dateToBeConfirmed'] = $input['eventDate'] === '' && ($input['undatedStatus'] ?? '') === 'tbc';

    if ($input['name'] === '') {
        $errors[] = 'Event name is required.';
    } elseif (mb_strlen($input['name']) > 255) {
        $errors[] = 'Event name must be 255 characters or fewer.';
    }

    if ($input['eventDate'] !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input['eventDate']);
        if (!$date || $date->format('Y-m-d') !== $input['eventDate']) {
            $errors[] = 'Please enter a valid event date.';
        }
    }

    return $errors;
}

// add an event, the archivable entity is saved first then the event with the same id
function addEvent(mysqli $conn, array $input, int $accountID): ?int
{
    $conn->begin_transaction();

    if (!$conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Event')")) {
        $conn->rollback();
        return null;
    }

    $eventID = $conn->insert_id;
    $description = $input['description'] !== '' ? $input['description'] : null;
    $eventDate = $input['eventDate'] !== '' ? $input['eventDate'] : null;

    $toBeConfirmed = $input['dateToBeConfirmed'] ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO Event (eventID, createdBy, name, description, eventDate, dateToBeConfirmed, isArchived)
         VALUES (?, ?, ?, ?, ?, ?, FALSE)"
    );
    if (!$stmt) {
        $conn->rollback();
        return null;
    }

    $stmt->bind_param("iisssi", $eventID, $accountID, $input['name'], $description, $eventDate, $toBeConfirmed);

    if (!$stmt->execute()) {
        error_log('addEvent failed: ' . $stmt->error);
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $eventID;
}

// save changes to an event
function updateEvent(mysqli $conn, int $eventID, array $input): bool
{
    $description = $input['description'] !== '' ? $input['description'] : null;
    $eventDate = $input['eventDate'] !== '' ? $input['eventDate'] : null;

    $toBeConfirmed = $input['dateToBeConfirmed'] ? 1 : 0;

    $stmt = $conn->prepare(
        "UPDATE Event SET name = ?, description = ?, eventDate = ?, dateToBeConfirmed = ? WHERE eventID = ?"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("sssii", $input['name'], $description, $eventDate, $toBeConfirmed, $eventID);
    return $stmt->execute();
}

// archive an event or restore it and log who did it
function toggleEventArchive(mysqli $conn, int $eventID, int $accountID): ?string
{
    $event = getEvent($conn, $eventID);
    if (!$event) {
        return null;
    }

    $newState = $event['isArchived'] ? 0 : 1;
    $action = $newState ? 'archived' : 'restored';

    $conn->begin_transaction();

    $update = $conn->prepare("UPDATE Event SET isArchived = ? WHERE eventID = ?");
    $update->bind_param("ii", $newState, $eventID);

    $log = $conn->prepare("INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, ?)");
    $log->bind_param("iis", $eventID, $accountID, $action);

    if (!$update->execute() || !$log->execute()) {
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $action;
}
