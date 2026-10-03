<?php
// campaignData.php fetches and saves the campaigns and their donations for manageCampaigns.php
// active campaigns show on campaign.php and take donations, draft and closed ones are hidden
// archived campaigns are always hidden and a campaign's image is its first photo
// admin and marketing can both add, edit and archive campaigns

// campaign statuses
const CAMPAIGN_STATUSES = [
    'active' => 'Active',
    'draft'  => 'Draft',
    'closed' => 'Closed',
];

// labels for the donation payment status set by PayFast
const DONATION_STATUS_LABELS = [
    'complete'  => 'Paid',
    'pending'   => 'Awaiting payment',
    'cancelled' => 'Cancelled',
    'failed'    => 'Failed',
];

// largest goal the database can hold and the description limit
const CAMPAIGN_GOAL_MAX = 9999999999.99;
const CAMPAIGN_DESCRIPTION_MAX = 5000;

// every campaign newest first with its donation totals, photo count, cover photo and who added it
function getAllCampaigns(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT c.campaignID, c.name, c.description, c.goal, c.status, c.isArchived,
                u.firstName AS creatorFirstName, u.lastName AS creatorLastName, u.username AS creatorUsername,
                COALESCE(d.raised, 0) AS raised,
                COALESCE(d.recent, 0) AS raisedLast30Days,
                COALESCE(d.donations, 0) AS donations,
                d.lastDonation,
                (SELECT COUNT(*) FROM GalleryItem gi WHERE gi.campaignID = c.campaignID) AS photoCount,
                (SELECT gi.image FROM GalleryItem gi WHERE gi.campaignID = c.campaignID
                 ORDER BY gi.galleryItemID LIMIT 1) AS coverImage
         FROM Campaign c
         LEFT JOIN UserAccount u ON u.accountID = c.createdBy
         LEFT JOIN (
             SELECT campaignID,
                    SUM(amount) AS raised,
                    SUM(CASE WHEN donationDate >= NOW() - INTERVAL 30 DAY THEN amount ELSE 0 END) AS recent,
                    COUNT(*) AS donations,
                    MAX(donationDate) AS lastDonation
             FROM Donation
             WHERE paymentStatus = 'complete'
             GROUP BY campaignID
         ) d ON d.campaignID = c.campaignID
         ORDER BY c.campaignID DESC"
    );

    if (!$result) {
        error_log('getAllCampaigns failed: ' . $conn->error);
        return [];
    }

    return array_map('normaliseCampaign', $result->fetch_all(MYSQLI_ASSOC));
}

// one campaign
function getCampaign(mysqli $conn, int $campaignID): ?array
{
    foreach (getAllCampaigns($conn) as $campaign) {
        if ($campaign['campaignID'] === $campaignID) {
            return $campaign;
        }
    }

    return null;
}

// tidy up the columns and work out the percentage raised and who added it
function normaliseCampaign(array $row): array
{
    $row['campaignID'] = (int) $row['campaignID'];
    $row['isArchived'] = (bool) $row['isArchived'];
    $row['goal'] = (float) $row['goal'];
    $row['raised'] = (float) ($row['raised'] ?? 0);
    $row['raisedLast30Days'] = (float) ($row['raisedLast30Days'] ?? 0);
    $row['donations'] = (int) ($row['donations'] ?? 0);
    $row['photoCount'] = (int) ($row['photoCount'] ?? 0);
    $row['coverImage'] = $row['coverImage'] ?? null;
    $row['lastDonation'] = $row['lastDonation'] ?? null;
    $row['percentage'] = $row['goal'] > 0 ? (int) min(100, floor($row['raised'] / $row['goal'] * 100)) : 0;

    $row['createdByName'] = isset($row['creatorUsername'])
        ? (accountFullName($row['creatorFirstName'], $row['creatorLastName']) ?: $row['creatorUsername'])
        : null;

    return $row;
}

// counts and totals for the summary cards, archived campaigns are left out
function getCampaignCounts(array $campaigns): array
{
    $current = array_filter($campaigns, fn ($c) => !$c['isArchived']);
    $byStatus = fn (string $status) => count(array_filter($current, fn ($c) => $c['status'] === $status));

    return [
        'active'           => $byStatus('active'),
        'draft'            => $byStatus('draft'),
        'closed'           => $byStatus('closed'),
        'archived'         => count($campaigns) - count($current),
        'raised'           => array_sum(array_column($current, 'raised')),
        'raisedLast30Days' => array_sum(array_column($current, 'raisedLast30Days')),
        'donations'        => array_sum(array_column($current, 'donations')),
        'funded'           => count(array_filter($current, fn ($c) => $c['goal'] > 0 && $c['raised'] >= $c['goal'])),
    ];
}

// one campaign's donations newest first, only complete ones count in the totals
function getCampaignDonations(mysqli $conn, int $campaignID): array
{
    $stmt = $conn->prepare(
        "SELECT donationID, firstName, lastName, email, phoneNumber, amount, donationDate, paymentReference,
                paymentStatus, isAnonymous
         FROM Donation
         WHERE campaignID = ?
         ORDER BY donationDate DESC, donationID DESC"
    );
    if (!$stmt) {
        error_log('getCampaignDonations failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $campaignID);
    $stmt->execute();

    return array_map(function ($row) {
        $row['amount'] = (float) $row['amount'];
        $row['isAnonymous'] = (bool) $row['isAnonymous'];
        return $row;
    }, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// check the add and edit form, the goal accepts spaces, commas and an R
function validateCampaignInput(array &$input): array
{
    $errors = [];

    $input['name'] = trim($input['name'] ?? '');
    $input['description'] = trim($input['description'] ?? '');
    $input['goal'] = trim($input['goal'] ?? '');
    $input['status'] = $input['status'] ?? '';

    if ($input['name'] === '') {
        $errors[] = 'Campaign name is required.';
    } elseif (mb_strlen($input['name']) > 255) {
        $errors[] = 'Campaign name must be 255 characters or fewer.';
    }

    if ($input['description'] === '') {
        $errors[] = 'Please add a description. It is shown on the website.';
    } elseif (mb_strlen($input['description']) > CAMPAIGN_DESCRIPTION_MAX) {
        $errors[] = 'The description must be ' . number_format(CAMPAIGN_DESCRIPTION_MAX) . ' characters or fewer.';
    }

    // check the goal
    $goal = preg_replace('/[\sR,]/i', '', $input['goal']);
    if ($input['goal'] === '') {
        $errors[] = 'Please enter a fundraising goal.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $goal) || (float) $goal <= 0) {
        $errors[] = 'The goal must be an amount in rand greater than zero, e.g. 25000.';
    } elseif ((float) $goal > CAMPAIGN_GOAL_MAX) {
        $errors[] = 'The goal is too large.';
    } else {
        $input['goalValue'] = round((float) $goal, 2);
    }

    if (!isset(CAMPAIGN_STATUSES[$input['status']])) {
        $errors[] = 'Please choose a status.';
    }

    return $errors;
}

// add a campaign, the archivable entity is saved first then the campaign with the same id
function addCampaign(mysqli $conn, array $input, int $accountID): ?int
{
    $conn->begin_transaction();

    if (!$conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Campaign')")) {
        $conn->rollback();
        return null;
    }

    $campaignID = $conn->insert_id;

    $stmt = $conn->prepare(
        "INSERT INTO Campaign (campaignID, createdBy, name, description, goal, status, isArchived)
         VALUES (?, ?, ?, ?, ?, ?, FALSE)"
    );
    if (!$stmt) {
        $conn->rollback();
        return null;
    }

    $stmt->bind_param("iissds", $campaignID, $accountID, $input['name'], $input['description'],
        $input['goalValue'], $input['status']);

    if (!$stmt->execute()) {
        error_log('addCampaign failed: ' . $stmt->error);
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $campaignID;
}

// save changes to a campaign
function updateCampaign(mysqli $conn, int $campaignID, array $input): bool
{
    $stmt = $conn->prepare(
        "UPDATE Campaign SET name = ?, description = ?, goal = ?, status = ? WHERE campaignID = ?"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ssdsi", $input['name'], $input['description'], $input['goalValue'],
        $input['status'], $campaignID);

    if (!$stmt->execute()) {
        error_log('updateCampaign failed: ' . $stmt->error);
        return false;
    }

    return true;
}

// close an active campaign so it leaves the website and stops taking donations, its donations are kept
function closeCampaign(mysqli $conn, int $campaignID): bool
{
    $stmt = $conn->prepare(
        "UPDATE Campaign SET status = 'closed' WHERE campaignID = ? AND status = 'active' AND isArchived = FALSE"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $campaignID);

    if (!$stmt->execute()) {
        error_log('closeCampaign failed: ' . $stmt->error);
        return false;
    }

    return $stmt->affected_rows === 1;
}

// archive a campaign or restore it and log who did it
function toggleCampaignArchive(mysqli $conn, int $campaignID, int $accountID): ?string
{
    $stmt = $conn->prepare("SELECT isArchived FROM Campaign WHERE campaignID = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $campaignID);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }

    $newState = $row['isArchived'] ? 0 : 1;
    $action = $newState ? 'archived' : 'restored';

    $conn->begin_transaction();

    $update = $conn->prepare("UPDATE Campaign SET isArchived = ? WHERE campaignID = ?");
    $update->bind_param("ii", $newState, $campaignID);

    $log = $conn->prepare("INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, ?)");
    $log->bind_param("iis", $campaignID, $accountID, $action);

    if (!$update->execute() || !$log->execute()) {
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $action;
}
