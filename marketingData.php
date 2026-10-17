<?php
// marketingData.php fetches the campaign, donation and event figures for staffDashboard.php
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

// paid donations per day for the last $days days, oldest first, days with none count as 0
// each day has a label like 3 Oct and its total in rand, for the dashboard trend chart
function getDonationsPerDay(mysqli $conn, int $days = 30): array
{
    // days ago and today come from the database so they match the other donation totals
    $stmt = $conn->prepare(
        "SELECT DATEDIFF(CURDATE(), DATE(d.donationDate)) AS daysAgo, SUM(d.amount) AS total
         FROM Donation d
         JOIN Campaign c ON c.campaignID = d.campaignID
         WHERE c.isArchived = FALSE AND d.paymentStatus = 'complete' AND d.donationDate >= CURDATE() - INTERVAL ? DAY
         GROUP BY daysAgo"
    );
    $today = $conn->query("SELECT CURDATE() AS today");

    if (!$stmt || !$today) {
        error_log('getDonationsPerDay failed: ' . $conn->error);
        return [];
    }

    $back = $days - 1;
    $stmt->bind_param("i", $back);
    $stmt->execute();

    $totals = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'total', 'daysAgo');
    $todayDate = new DateTimeImmutable($today->fetch_assoc()['today']);

    $perDay = [];
    for ($daysAgo = $back; $daysAgo >= 0; $daysAgo--) {
        $perDay[] = [
            'label' => $todayDate->modify("-$daysAgo days")->format('j M'),
            'total' => (float) ($totals[$daysAgo] ?? 0),
        ];
    }

    return $perDay;
}

// how much the active campaigns have raised against their combined goal
function getActiveCampaignTotals(mysqli $conn): array
{
    $totals = ['campaigns' => 0, 'goal' => 0.0, 'raised' => 0.0, 'percentage' => 0];

    $result = $conn->query(
        "SELECT COUNT(*) AS campaigns, COALESCE(SUM(c.goal), 0) AS goal, COALESCE(SUM(r.raised), 0) AS raised
         FROM Campaign c
         LEFT JOIN (
             SELECT campaignID, SUM(amount) AS raised
             FROM Donation
             WHERE paymentStatus = 'complete'
             GROUP BY campaignID
         ) r ON r.campaignID = c.campaignID
         WHERE c.isArchived = FALSE AND c.status = 'active'"
    );
    if (!$result) {
        error_log('getActiveCampaignTotals failed: ' . $conn->error);
        return $totals;
    }

    $row = $result->fetch_assoc();
    $totals['campaigns'] = (int) $row['campaigns'];
    $totals['goal'] = (float) $row['goal'];
    $totals['raised'] = (float) $row['raised'];
    $totals['percentage'] = $totals['goal'] > 0 ? (int) min(100, round($totals['raised'] / $totals['goal'] * 100)) : 0;

    return $totals;
}

// dated events from today up to $days days away, soonest first, for the dashboard timeline
function getEventsWithinDays(mysqli $conn, int $days = 30): array
{
    $stmt = $conn->prepare(
        "SELECT eventID, name, eventDate, DATEDIFF(eventDate, CURDATE()) AS daysAway
         FROM Event
         WHERE isArchived = FALSE AND eventDate >= CURDATE() AND eventDate <= CURDATE() + INTERVAL ? DAY
         ORDER BY eventDate, eventID"
    );
    if (!$stmt) {
        error_log('getEventsWithinDays failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $days);
    $stmt->execute();

    return array_map(function ($row) {
        $row['daysAway'] = (int) $row['daysAway'];
        return $row;
    }, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// format a rand amount for display
function formatRand(float $amount): string
{
    return 'R' . number_format($amount, 2);
}
