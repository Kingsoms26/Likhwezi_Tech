<?php
// dashboardData.php gathers everything adminDashboard.php shows into one array
// returns data only and reuses the enquiry, registration and marketing helpers

require_once __DIR__ . '/enquiryData.php';
require_once __DIR__ . '/registrationData.php';
require_once __DIR__ . '/marketingData.php';

// how many rows the open enquiries table and campaigns panel show
const ADMIN_RECENT_ENQUIRIES = 3;
const ADMIN_CAMPAIGNS = 5;

// open enquiries, active campaigns, funds raised, recent enquiries, registrations per programme and campaign progress
function getDashboardStats(mysqli $conn): array
{
    $enquiryCounts = getEnquiryCounts($conn);
    $campaignStats = getCampaignStats($conn);

    return [
        'activeEnquiries'      => $enquiryCounts['new'] + $enquiryCounts['contacted'],
        'activeCampaigns'      => $campaignStats['active'],
        'fundsRaised'          => $campaignStats['fundsRaised'],
        'recentEnquiries'      => getRecentOpenEnquiries($conn, ADMIN_RECENT_ENQUIRIES),
        'registrationInterest' => getRegistrationsByProgramme($conn),
        'campaigns'            => getActiveCampaignProgress($conn, ADMIN_CAMPAIGNS),
    ];
}

// newest open enquiries that are not archived
function getRecentOpenEnquiries(mysqli $conn, int $limit): array
{
    $stmt = $conn->prepare(
        "SELECT name, description, status
         FROM Enquiry
         WHERE isArchived = FALSE AND status <> 'closed'
         ORDER BY dateCreated DESC
         LIMIT ?"
    );
    if (!$stmt) {
        error_log('getRecentOpenEnquiries failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();

    return array_map(fn ($row) => [
        'subject'  => mb_strimwidth($row['description'], 0, 60, '...'),
        'customer' => $row['name'],
        'status'   => ucfirst($row['status']),
    ], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}
