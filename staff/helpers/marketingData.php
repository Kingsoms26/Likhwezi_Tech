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

// format a rand amount for display
function formatRand(float $amount): string
{
    return 'R' . number_format($amount, 2);
}

// paid donations for the overview card
// the all-time total and the period total come from the same query with the same filters,
// so the period figure can never exceed the all-time figure
function getDonationTotals(mysqli $conn, string $period = 'week'): array
{
    $days = match ($period) {
        'year'  => 365,
        'month' => 30,
        default => 7,
    };

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(d.amount), 0) AS all_time,
                COALESCE(SUM(CASE WHEN d.donationDate >= CURDATE() - INTERVAL ? DAY THEN d.amount END), 0) AS period
         FROM Donation d
         JOIN Campaign c ON c.campaignID = d.campaignID
         WHERE c.isArchived = FALSE AND d.paymentStatus = 'complete'"
    );
    if (!$stmt) {
        error_log('getDonationTotals failed: ' . $conn->error);
        return ['allTime' => 0.0, 'period' => 0.0, 'days' => $days];
    }

    $stmt->bind_param("i", $days);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $allTime = (float) ($row['all_time'] ?? 0);

    return [
        // the clamp keeps the invariant even if the two figures ever drift apart again
        'allTime' => $allTime,
        'period'  => min((float) ($row['period'] ?? 0), $allTime),
        'days'    => $days,
    ];
}

// active campaigns and what they have raised against their combined goal
function getActiveCampaignTotals(mysqli $conn): array
{
    $stmt = $conn->prepare(
        "SELECT
            (SELECT COUNT(*) FROM Campaign WHERE isArchived = FALSE AND status = 'active') AS campaigns,
            (SELECT COALESCE(SUM(goal), 0) FROM Campaign WHERE isArchived = FALSE AND status = 'active') AS goal,
            (SELECT COALESCE(SUM(d.amount), 0)
               FROM Donation d
               JOIN Campaign c ON c.campaignID = d.campaignID
              WHERE c.isArchived = FALSE AND c.status = 'active' AND d.paymentStatus = 'complete') AS raised"
    );
    if (!$stmt) {
        error_log('getActiveCampaignTotals failed: ' . $conn->error);
        return ['campaigns' => 0, 'raised' => 0.0, 'goal' => 0.0];
    }

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return [
        'campaigns' => (int) ($row['campaigns'] ?? 0),
        'raised'    => (float) ($row['raised'] ?? 0),
        'goal'      => (float) ($row['goal'] ?? 0),
    ];
}

// how many events fall in the next few days, for the hero pill
function getUpcomingDatedEventCount(mysqli $conn, int $days = 7): int
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM Event
         WHERE isArchived = FALSE AND eventDate >= CURDATE() AND eventDate <= CURDATE() + INTERVAL ? DAY"
    );
    if (!$stmt) {
        error_log('getUpcomingDatedEventCount failed: ' . $conn->error);
        return 0;
    }

    $stmt->bind_param("i", $days);
    $stmt->execute();

    return (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
}
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
