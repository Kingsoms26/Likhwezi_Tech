<?php
// announcements.php fetches the messages admins push from staff/announcements.php
// website ones show as a banner under the navbar on the public pages, staff ones show on the staff portal
// the public lookup is cached like the rest of the site content but the dates are checked on every visit
require_once __DIR__ . '/cache.php';

const ANNOUNCEMENT_CACHE_SECONDS = 3600;

// dates are checked in south african time so an announcement starts and ends at local midnight
const ANNOUNCEMENT_TIMEZONE = 'Africa/Johannesburg';

// every shown announcement for one side, 'website' or 'staff', newest first
// returns null when the lookup fails so a failure is never cached
function shownAnnouncements(mysqli $conn, string $side): ?array {
    $stmt = $conn->prepare(
        "SELECT announcementID, title, message, linkURL, startDate, endDate
         FROM Announcement
         WHERE isActive = TRUE AND audience IN ('everyone', ?)
         ORDER BY dateCreated DESC"
    );
    if (!$stmt) {
        error_log('shownAnnouncements failed (has the Announcement table from database/dbSetup.md been created?): ' . $conn->error);
        return null;
    }

    $stmt->bind_param("s", $side);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// leave out announcements that have not started yet or have already ended
function currentAnnouncements(array $announcements): array {
    $today = (new DateTime('now', new DateTimeZone(ANNOUNCEMENT_TIMEZONE)))->format('Y-m-d');

    return array_values(array_filter($announcements, fn ($announcement) =>
        ($announcement['startDate'] === null || $announcement['startDate'] <= $today)
        && ($announcement['endDate'] === null || $announcement['endDate'] >= $today)
    ));
}

// announcements for the banner on the public pages
function siteAnnouncements(): array {
    $announcements = cached('site_announcements', ANNOUNCEMENT_CACHE_SECONDS, function () {
        $conn = db();
        return $conn ? shownAnnouncements($conn, 'website') : null;
    });

    return currentAnnouncements($announcements ?? []);
}

// announcements for the staff portal
function staffAnnouncements(mysqli $conn): array {
    return currentAnnouncements(shownAnnouncements($conn, 'staff') ?? []);
}

// key that changes when the announcement is edited so a dismissed one shows again after a change
function announcementKey(array $announcement): string {
    return $announcement['announcementID'] . '-'
        . substr(sha1($announcement['title'] . '|' . $announcement['message'] . '|' . $announcement['linkURL']), 0, 8);
}
