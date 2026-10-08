<?php
// announcementData.php fetches and saves the announcements edited on announcements.php
// the public pages and the staff portal read them through includes/helpers/announcements.php

require_once __DIR__ . '/../../includes/helpers/announcements.php';

// who can see an announcement and the label shown for each
const ANNOUNCEMENT_AUDIENCES = [
    'everyone' => 'Website and staff',
    'website'  => 'Website only',
    'staff'    => 'Staff only',
];

const ANNOUNCEMENT_TITLE_MAX = 150;
const ANNOUNCEMENT_MESSAGE_MAX = 1000;

// every announcement, newest first, with the name of whoever posted it
function getAllAnnouncements(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT a.announcementID, a.title, a.message, a.audience, a.linkURL, a.startDate, a.endDate,
                a.isActive, a.dateCreated, u.firstName, u.lastName, u.username
         FROM Announcement a
         LEFT JOIN UserAccount u ON u.accountID = a.createdBy
         ORDER BY a.dateCreated DESC, a.announcementID DESC"
    );

    if (!$result) {
        error_log('getAllAnnouncements failed (has the Announcement table from database/dbSetup.md been created?): ' . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// one announcement
function getAnnouncement(mysqli $conn, int $announcementID): ?array
{
    $stmt = $conn->prepare(
        "SELECT announcementID, title, message, audience, linkURL, startDate, endDate, isActive
         FROM Announcement WHERE announcementID = ?"
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $announcementID);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// read the form into clean values, empty link and dates become null
function readAnnouncementInput(array $post): array
{
    $optional = fn ($key) => trim((string) ($post[$key] ?? '')) ?: null;

    return [
        'title'     => trim((string) ($post['title'] ?? '')),
        'message'   => trim(str_replace("\r\n", "\n", (string) ($post['message'] ?? ''))),
        'audience'  => (string) ($post['audience'] ?? ''),
        'linkURL'   => $optional('linkURL'),
        'startDate' => $optional('startDate'),
        'endDate'   => $optional('endDate'),
    ];
}

// check the announcement and return any error messages
function validateAnnouncementInput(array $input): array
{
    $errors = [];
    $isDate = fn ($value) => $value === null
        || (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && DateTime::createFromFormat('Y-m-d', $value)?->format('Y-m-d') === $value);

    if ($input['title'] === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($input['title']) > ANNOUNCEMENT_TITLE_MAX) {
        $errors[] = 'Title must be ' . ANNOUNCEMENT_TITLE_MAX . ' characters or fewer.';
    }

    if ($input['message'] === '') {
        $errors[] = 'Message is required.';
    } elseif (mb_strlen($input['message']) > ANNOUNCEMENT_MESSAGE_MAX) {
        $errors[] = 'Message must be ' . ANNOUNCEMENT_MESSAGE_MAX . ' characters or fewer.';
    }

    if (!isset(ANNOUNCEMENT_AUDIENCES[$input['audience']])) {
        $errors[] = 'Choose who should see the announcement.';
    }

    // links open from the website and the staff portal so only full web addresses work in both
    if ($input['linkURL'] !== null
        && (mb_strlen($input['linkURL']) > 255 || !filter_var($input['linkURL'], FILTER_VALIDATE_URL)
            || !preg_match('#^https?://#i', $input['linkURL']))) {
        $errors[] = 'Link must be a full web address starting with https://, 255 characters or fewer.';
    }

    if (!$isDate($input['startDate']) || !$isDate($input['endDate'])) {
        $errors[] = 'Dates must be real dates.';
    } elseif ($input['startDate'] && $input['endDate'] && $input['endDate'] < $input['startDate']) {
        $errors[] = 'The last day cannot be before the first day.';
    }

    return $errors;
}

// add an announcement posted by this account
function addAnnouncement(mysqli $conn, array $input, int $accountID): ?int
{
    $stmt = $conn->prepare(
        "INSERT INTO Announcement (createdBy, title, message, audience, linkURL, startDate, endDate)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        error_log('addAnnouncement failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param("issssss", $accountID, $input['title'], $input['message'], $input['audience'],
        $input['linkURL'], $input['startDate'], $input['endDate']);

    return $stmt->execute() ? $conn->insert_id : null;
}

// save changes to an announcement
function updateAnnouncement(mysqli $conn, int $announcementID, array $input): bool
{
    $stmt = $conn->prepare(
        "UPDATE Announcement SET title = ?, message = ?, audience = ?, linkURL = ?, startDate = ?, endDate = ?
         WHERE announcementID = ?"
    );
    if (!$stmt) {
        error_log('updateAnnouncement failed: ' . $conn->error);
        return false;
    }

    $stmt->bind_param("ssssssi", $input['title'], $input['message'], $input['audience'],
        $input['linkURL'], $input['startDate'], $input['endDate'], $announcementID);

    return $stmt->execute();
}

// show or hide an announcement
function toggleAnnouncement(mysqli $conn, int $announcementID): bool
{
    $stmt = $conn->prepare("UPDATE Announcement SET isActive = NOT isActive WHERE announcementID = ?");
    $stmt->bind_param("i", $announcementID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// delete an announcement for good
function deleteAnnouncement(mysqli $conn, int $announcementID): bool
{
    $stmt = $conn->prepare("DELETE FROM Announcement WHERE announcementID = ?");
    $stmt->bind_param("i", $announcementID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// status label and badge class, hidden ones first then whether the dates have it showing now
function announcementStatus(array $announcement): array
{
    if (!$announcement['isActive']) {
        return ['Hidden', 'status-archived'];
    }
    if (!currentAnnouncements([$announcement])) {
        $today = (new DateTime('now', new DateTimeZone(ANNOUNCEMENT_TIMEZONE)))->format('Y-m-d');
        return $announcement['startDate'] !== null && $announcement['startDate'] > $today
            ? ['Scheduled', 'status-scheduled']
            : ['Ended', 'status-archived'];
    }

    return ['Showing', 'status-active'];
}

// dates as one short line like "1 Oct to 12 Oct 2026", empty dates mean no limit
function announcementDates(array $announcement): string
{
    $format = fn ($date) => date('j M Y', strtotime($date));
    $start = $announcement['startDate'];
    $end = $announcement['endDate'];

    return match (true) {
        $start && $end => $format($start) . ' to ' . $format($end),
        (bool) $start  => 'From ' . $format($start),
        (bool) $end    => 'Until ' . $format($end),
        default        => 'No end date',
    };
}
