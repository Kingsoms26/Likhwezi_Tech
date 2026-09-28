<?php
/*
 * ENQUIRY DATA
 *
 * Follows the dashboard framework rule: tools own data.
 * These functions talk to the database; they never output HTML.
 * The page (manageEnquiries.php) decides how to display the results.
 *
 * Every function takes the mysqli connection ($conn) from
 * tools/dbConnection.php as its first argument.
 */

/* Counts of active (non-archived) enquiries per status, for the summary cards. */
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

/* All enquiries, newest first. Archived ones only when asked for. */
function getEnquiries(mysqli $conn, bool $includeArchived = false): array
{
    $sql = "SELECT enquiryID, name, companyName, email, phoneNumber,
                   description, meetingType, status, isArchived, dateCreated
            FROM Enquiry";

    if (!$includeArchived) {
        $sql .= " WHERE isArchived = FALSE";
    }

    $sql .= " ORDER BY dateCreated DESC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/*
 * Change an enquiry's status.
 *
 * handledBy has a foreign key to StaffCustomerService, so it can only hold
 * the account ID of a Customer Service staff member. The subquery sets it
 * only when the logged-in account really is one, and otherwise leaves the
 * existing handler untouched. Without this, the whole update would be
 * rejected for anyone else (Admin, Marketing, or a test account).
 */
function updateEnquiryStatus(mysqli $conn, int $enquiryID, string $status, int $accountID): bool
{
    if (!in_array($status, ['new', 'contacted', 'closed'], true)) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE Enquiry
         SET status = ?,
             handledBy = COALESCE(
                 (SELECT accountID FROM StaffCustomerService WHERE accountID = ?),
                 handledBy
             )
         WHERE enquiryID = ?"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("sii", $status, $accountID, $enquiryID);

    return $stmt->execute();
}

/* Archive an enquiry, or restore it if it is already archived. Logs the action. */
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