<?php
// marketingData.php fetches the campaign, donation and event figures for marketingDashboard.php
// returns data only and archived campaigns and events are left out everywhere

// upcoming events within this many days count as this week on the events card
const MARKETING_SOON_DAYS = 7;

// campaign counts by status and the donation totals
function getCampaignStats(mysqli $conn): array
{
    $stats = [
        'active' => 0, 'draft' => 0, 'closed' => 0,
        'fundsRaised' => 0.0, 'raisedLast30Days' => 0.0, 'donations' => 0,
    ];

    // count the campaigns by status
    $result = $conn->query(
        "SELECT status, COUNT(*) AS total
         FROM Campaign
         WHERE isArchived = FALSE
         GROUP BY status"
    );
    if (!$result) {
        error_log('getCampaignStats (campaigns) failed: ' . $conn->error);
        return $stats;
    }
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $stats[$row['status']] = (int) $row['total'];
    }

    // total the paid donations
    $result = $conn->query(
        "SELECT COALESCE(SUM(d.amount), 0) AS raised,
                COALESCE(SUM(CASE WHEN d.donationDate >= NOW() - INTERVAL 30 DAY THEN d.amount END), 0) AS recent,
                COUNT(*) AS donations
         FROM Donation d
         JOIN Campaign c ON c.campaignID = d.campaignID
         WHERE c.isArchived = FALSE AND d.paymentStatus = 'complete'"
    );
    if (!$result) {
        error_log('getCampaignStats (donations) failed: ' . $conn->error);
        return $stats;
    }
    $totals = $result->fetch_assoc();
    $stats['fundsRaised'] = (float) $totals['raised'];
    $stats['raisedLast30Days'] = (float) $totals['recent'];
    $stats['donations'] = (int) $totals['donations'];

    return $stats;
}

// active campaigns with how much they have raised against their goal, closest to the goal first
function getActiveCampaignProgress(mysqli $conn, int $limit = 5): array
{
    $stmt = $conn->prepare(
        "SELECT c.campaignID, c.name, c.goal,
                COALESCE(SUM(d.amount), 0) AS raised,
                COUNT(d.donationID) AS donations
         FROM Campaign c
         LEFT JOIN Donation d ON d.campaignID = c.campaignID AND d.paymentStatus = 'complete'
         WHERE c.isArchived = FALSE AND c.status = 'active'
         GROUP BY c.campaignID, c.name, c.goal
         ORDER BY raised / NULLIF(c.goal, 0) DESC, c.name
         LIMIT ?"
    );
    if (!$stmt) {
        error_log('getActiveCampaignProgress failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();

    return array_map(function ($row) {
        $row['goal'] = (float) $row['goal'];
        $row['raised'] = (float) $row['raised'];
        $row['donations'] = (int) $row['donations'];
        $row['percentage'] = $row['goal'] > 0 ? (int) min(100, round($row['raised'] / $row['goal'] * 100)) : 0;
        return $row;
    }, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// number of upcoming events and how many are happening this week
// upcoming matches includes/helpers/events.php
function getUpcomingEventCounts(mysqli $conn): array
{
    $days = MARKETING_SOON_DAYS;

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS upcoming,
                COALESCE(SUM(eventDate <= CURDATE() + INTERVAL ? DAY), 0) AS soon
         FROM Event
         WHERE isArchived = FALSE AND (eventDate >= CURDATE() OR (eventDate IS NULL AND dateToBeConfirmed = TRUE))"
    );
    if (!$stmt) {
        error_log('getUpcomingEventCounts failed: ' . $conn->error);
        return ['upcoming' => 0, 'soon' => 0];
    }

    $stmt->bind_param("i", $days);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return ['upcoming' => (int) $row['upcoming'], 'soon' => (int) $row['soon']];
}

// next upcoming events, soonest first and to be confirmed ones last
// each one says when it is like today, tomorrow or in 5 days
function getUpcomingEvents(mysqli $conn, int $limit = 5): array
{
    $stmt = $conn->prepare(
        "SELECT eventID, name, eventDate, DATEDIFF(eventDate, CURDATE()) AS daysAway
         FROM Event
         WHERE isArchived = FALSE AND (eventDate >= CURDATE() OR (eventDate IS NULL AND dateToBeConfirmed = TRUE))
         ORDER BY eventDate IS NULL, eventDate, eventID
         LIMIT ?"
    );
    if (!$stmt) {
        error_log('getUpcomingEvents failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();

    // days away comes from the database so today matches the query even if php's timezone differs
    return array_map(function ($row) {
        if ($row['eventDate'] === null) {
            $row['when'] = 'Date to be confirmed';
            return $row;
        }

        $row['when'] = match ((int) $row['daysAway']) {
            0       => 'Today',
            1       => 'Tomorrow',
            default => "In {$row['daysAway']} days",
        };
        return $row;
    }, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// format a rand amount for display
function formatRand(float $amount): string
{
    return 'R' . number_format($amount, 2);
}
