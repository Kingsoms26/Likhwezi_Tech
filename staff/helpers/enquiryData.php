<?php
// enquiryData.php fetches and saves the enquiries for manageEnquiries.php
// returns data only, the page decides how to show it

// count the enquiries that are not archived by status for the summary cards
function getEnquiryCounts(mysqli $conn): array
{
    $counts = ['new' => 0, 'contacted' => 0, 'closed' => 0];

    $result = $conn->query(
        "SELECT status, COUNT(*) AS total
         FROM Enquiry
         WHERE isArchived = FALSE
         GROUP BY status"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $counts[$row['status']] = (int) $row['total'];
        }
    }

    return $counts;
}

// all enquiries newest first with the name of whoever claimed it, archived ones only when asked
function getEnquiries(mysqli $conn, bool $includeArchived = false): array
{
    $sql = "SELECT e.enquiryID, e.name, e.companyName, e.email, e.phoneNumber,
                   e.description, e.meetingType, e.status, e.isArchived, e.dateCreated,
                   e.handledBy,
                   COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.firstName, u.lastName)), ''), u.username) AS handledByName
            FROM Enquiry e
            LEFT JOIN UserAccount u ON u.accountID = e.handledBy";

    if (!$includeArchived) {
        $sql .= " WHERE e.isArchived = FALSE";
    }

    $sql .= " ORDER BY e.dateCreated DESC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// change an enquiry's status
// if customer service changes an unclaimed enquiry it is claimed for them
// an enquiry someone already claimed keeps its handler
function updateEnquiryStatus(mysqli $conn, int $enquiryID, string $status, int $accountID): bool
{
    if (!in_array($status, ['new', 'contacted', 'closed'], true)) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE Enquiry
         SET status = ?,
             handledBy = COALESCE(
                 handledBy,
                 (SELECT accountID FROM StaffCustomerService WHERE accountID = ?)
             )
         WHERE enquiryID = ?"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("sii", $status, $accountID, $enquiryID);

    return $stmt->execute();
}

// claim an unclaimed enquiry for a customer service account
// returns false if someone already claimed it, it is archived or the account is not customer service
function claimEnquiry(mysqli $conn, int $enquiryID, int $accountID): bool
{
    $stmt = $conn->prepare(
        "UPDATE Enquiry
         SET handledBy = ?
         WHERE enquiryID = ?
           AND handledBy IS NULL
           AND isArchived = FALSE
           AND status <> 'closed'
           AND EXISTS (SELECT 1 FROM StaffCustomerService WHERE accountID = ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("iii", $accountID, $enquiryID, $accountID);

    return $stmt->execute() && $stmt->affected_rows === 1;
}

// give a claimed enquiry back to unclaimed, only its handler can do this
function releaseEnquiry(mysqli $conn, int $enquiryID, int $accountID): bool
{
    $stmt = $conn->prepare("UPDATE Enquiry SET handledBy = NULL WHERE enquiryID = ? AND handledBy = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $enquiryID, $accountID);

    return $stmt->execute() && $stmt->affected_rows === 1;
}

// run the status, claim, release or archive action from an enquiries table and set a message
// shared by manageEnquiries.php and closedEnquiries.php
function handleEnquiryAction(mysqli $conn, int $accountID): void
{
    $enquiryID = (int) ($_POST['enquiryID'] ?? 0);
    $saved = true;

    if (!verifyCsrf()) {
        setFlash('error', 'Your session expired. Please try again.');
    } elseif (isset($_POST['updateStatus'])) {
        $status = $_POST['status'] ?? '';
        $saved = updateEnquiryStatus($conn, $enquiryID, $status, $accountID);
        if ($saved) {
            logActivity($conn, 'Enquiry', 'status', "Set enquiry #$enquiryID to $status", $enquiryID);
        }
    } elseif (isset($_POST['claim'])) {
        if (claimEnquiry($conn, $enquiryID, $accountID)) {
            setFlash('success', 'Enquiry claimed. It is now in My Enquiries.');
            logActivity($conn, 'Enquiry', 'claimed', "Claimed enquiry #$enquiryID", $enquiryID);
        } else {
            setFlash('error', 'That enquiry could not be claimed. Someone may have claimed it already.');
        }
    } elseif (isset($_POST['release'])) {
        if (releaseEnquiry($conn, $enquiryID, $accountID)) {
            setFlash('success', 'Enquiry released back to Unclaimed.');
            logActivity($conn, 'Enquiry', 'released', "Released enquiry #$enquiryID back to Unclaimed", $enquiryID);
        } else {
            setFlash('error', 'That enquiry could not be released.');
        }
    } elseif (isset($_POST['toggleArchive'])) {
        // check first so the log says whether it was archived or restored
        $result = $conn->query("SELECT isArchived FROM Enquiry WHERE enquiryID = $enquiryID");
        $wasArchived = $result ? (bool) ($result->fetch_row()[0] ?? false) : false;

        $saved = toggleEnquiryArchive($conn, $enquiryID, $accountID);
        if ($saved) {
            logActivity($conn, 'Enquiry', $wasArchived ? 'restored' : 'archived',
                ($wasArchived ? 'Restored' : 'Archived') . " enquiry #$enquiryID", $enquiryID);
        }
    }

    if (!$saved) {
        setFlash('error', 'That enquiry could not be updated.');
    }
}

// archive an enquiry or restore it if it is already archived and log it
function toggleEnquiryArchive(mysqli $conn, int $enquiryID, int $accountID): bool
{
    $stmt = $conn->prepare("SELECT isArchived FROM Enquiry WHERE enquiryID = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $enquiryID);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    if (!$current) {
        return false;
    }

    $newState = $current['isArchived'] ? 0 : 1;
    $action = $newState ? 'archived' : 'restored';

    $update = $conn->prepare("UPDATE Enquiry SET isArchived = ? WHERE enquiryID = ?");
    $update->bind_param("ii", $newState, $enquiryID);

    if (!$update->execute()) {
        return false;
    }

    $log = $conn->prepare(
        "INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, ?)"
    );
    $log->bind_param("iis", $enquiryID, $accountID, $action);

    return $log->execute();
}