<?php
// profileData.php handles the staff member's own profile, passwords and activity
// admin account changes on accountData.php reuse some of these
// a role is whichever of the admin, marketing or customer service tables holds the account
// accounts are never deleted, they are deactivated or archived so their records keep their owner

// each role and its table, the order decides which wins if someone is in several
const ACCOUNT_ROLES = [
    'Admin'            => 'Admin',
    'Marketing'        => 'StaffMarketing',
    'Customer Service' => 'StaffCustomerService',
];

// account statuses
const ACCOUNT_STATUSES = ['active', 'suspended', 'deactivated'];

require_once __DIR__ . '/../../includes/helpers/imageStorage.php';

// profile photo size limit and allowed types, photos are saved on cloudinary
const PROFILE_PHOTO_MAX_BYTES = 2 * 1024 * 1024;
const PROFILE_PHOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// reading accounts

// one account with its role and who created it, never includes the password
function getAccount(mysqli $conn, int $accountID): ?array
{
    $stmt = $conn->prepare(
        "SELECT u.accountID, u.createdBy, u.firstName, u.lastName, u.email, u.username,
                u.profileImageURL, u.dateCreated, u.accountStatus,
                u.lastLogin, u.passwordChangedAt, u.mustChangePassword,
                u.isArchived, u.archivedAt,
                c.firstName AS creatorFirstName, c.lastName AS creatorLastName,
                c.username AS creatorUsername,
                CASE
                    WHEN a.accountID IS NOT NULL THEN 'Admin'
                    WHEN m.accountID IS NOT NULL THEN 'Marketing'
                    WHEN s.accountID IS NOT NULL THEN 'Customer Service'
                END AS role
         FROM UserAccount u
         LEFT JOIN UserAccount c ON c.accountID = u.createdBy
         LEFT JOIN Admin a ON a.accountID = u.accountID
         LEFT JOIN StaffMarketing m ON m.accountID = u.accountID
         LEFT JOIN StaffCustomerService s ON s.accountID = u.accountID
         WHERE u.accountID = ?"
    );

    if (!$stmt) {
        error_log('getAccount failed (does UserAccount match database/dbSetup.md?): ' . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();

    if (!$account) {
        return null;
    }

    // tidy up the columns and work out who created it
    $account['mustChangePassword'] = (bool) $account['mustChangePassword'];
    $account['isArchived'] = (bool) $account['isArchived'];
    $account['createdByName'] = $account['createdBy'] === null
        ? 'System'
        : (accountFullName($account['creatorFirstName'], $account['creatorLastName'])
            ?: ($account['creatorUsername'] ?? 'Unknown'));

    return $account;
}

// first and last name together
function accountFullName(?string $firstName, ?string $lastName): string
{
    return trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
}

// name to show, the username for accounts without a name yet
function accountDisplayName(array $account): string
{
    return accountFullName($account['firstName'] ?? '', $account['lastName'] ?? '') ?: $account['username'];
}

// image address for a profile photo from the staff pages
function profileImageSrc(?string $profileImageURL): string
{
    return imageSrc($profileImageURL ?: 'assets/images/placeholder.webp', '../');
}

// initials from the first and last name, the start of the username when there is no name yet
function accountInitials(array $account): string
{
    $firstName = trim($account['firstName'] ?? '');
    $lastName = trim($account['lastName'] ?? '');

    $initials = $firstName !== '' || $lastName !== ''
        ? mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1)
        : mb_substr($account['username'] ?? '', 0, 2);

    return mb_strtoupper($initials) ?: '?';
}

// profile photo, or a circle with the initials when no photo has been added
function profileAvatar(array $account, string $class): string
{
    if (!empty($account['profileImageURL'])) {
        return '<img src="' . htmlspecialchars(profileImageSrc($account['profileImageURL'])) . '" alt="" class="' . $class . '">';
    }

    return '<span class="' . $class . ' profile-initials" aria-hidden="true">' . htmlspecialchars(accountInitials($account)) . '</span>';
}

// checking the forms

// check the first and last name on my profile
function validatePersonalDetails(array &$input): array
{
    $errors = [];

    foreach (['firstName' => 'First name', 'lastName' => 'Last name'] as $field => $label) {
        $input[$field] = trim($input[$field] ?? '');

        if ($input[$field] === '') {
            $errors[] = "$label is required.";
        } elseif (mb_strlen($input[$field]) > 100) {
            $errors[] = "$label must be 100 characters or fewer.";
        }
    }

    return $errors;
}

// true if another account already uses this email
function isEmailTaken(mysqli $conn, string $email, int $ignoreAccountID): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM UserAccount WHERE email = ? AND accountID <> ?");
    $stmt->bind_param("si", $email, $ignoreAccountID);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

// password rules, at least 8 characters with a letter and a number
function validateNewPassword(string $password): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if (strlen($password) > 72) {
        // anything past 72 characters is ignored by the hashing
        $errors[] = 'New password must be 72 characters or fewer.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'New password must include at least one letter and one number.';
    }

    return $errors;
}

// password age

// passwords should be changed this often, staff are reminded not locked out
const PASSWORD_MAX_AGE_MONTHS = 6;

// the reminder turns amber this many days before it is due
const PASSWORD_REMINDER_DAYS = 30;

// when the password is next due to be changed, counted from the last change or when the account was made
// returns the due date, days left, how much of the 6 months has passed and whether it is ok, soon or overdue
function passwordAge(array $account): array
{
    $today = new DateTimeImmutable('today');
    $changed = (new DateTimeImmutable($account['passwordChangedAt'] ?: $account['dateCreated']))->setTime(0, 0);
    $dueDate = $changed->modify('+' . PASSWORD_MAX_AGE_MONTHS . ' months');

    $daysLeft = (int) $today->diff($dueDate)->format('%r%a');
    $totalDays = max(1, (int) $changed->diff($dueDate)->format('%a'));

    return [
        'dueDate'  => $dueDate,
        'daysLeft' => $daysLeft,
        'percent'  => max(0, min(100, round(($totalDays - $daysLeft) / $totalDays * 100))),
        'status'   => $daysLeft < 0 ? 'overdue' : ($daysLeft <= PASSWORD_REMINDER_DAYS ? 'soon' : 'ok'),
    ];
}

// short countdown like 42 days, due today or 3 days overdue
function passwordAgeLabel(array $age): string
{
    $days = abs($age['daysLeft']);
    $unit = $days === 1 ? 'day' : 'days';

    if ($age['daysLeft'] < 0) {
        return "$days $unit overdue";
    }

    return $age['daysLeft'] === 0 ? 'Due today' : "$days $unit";
}

// own profile

// save the name and photo, only these are written whatever else is sent
// pass no photo to keep the current one
function updateOwnProfile(mysqli $conn, int $accountID, array $input, ?string $photoPath): bool
{
    if ($photoPath !== null) {
        $stmt = $conn->prepare(
            "UPDATE UserAccount SET firstName = ?, lastName = ?, profileImageURL = ?
             WHERE accountID = ?"
        );
        $stmt->bind_param("sssi", $input['firstName'], $input['lastName'], $photoPath, $accountID);
    } else {
        $stmt = $conn->prepare(
            "UPDATE UserAccount SET firstName = ?, lastName = ? WHERE accountID = ?"
        );
        $stmt->bind_param("ssi", $input['firstName'], $input['lastName'], $accountID);
    }

    return $stmt->execute();
}

// check and save an uploaded profile photo under a random name, no path means no file was chosen
function saveProfilePhoto(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || ($file['error'] === UPLOAD_ERR_OK && $file['size'] > PROFILE_PHOTO_MAX_BYTES)) {
        return ['path' => null, 'error' => 'Profile photo must be 2 MB or smaller.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['path' => null, 'error' => 'The photo could not be uploaded. Please try again.'];
    }

    // check the real file contents, never the name or the type the browser says
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(PROFILE_PHOTO_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return ['path' => null, 'error' => 'Profile photo must be a JPG, PNG or WEBP image.'];
    }

    $url = uploadImage($file['tmp_name'], 'profiles');

    if (!$url) {
        return ['path' => null, 'error' => 'Failed to save the photo. Please try again.'];
    }

    return ['path' => $url, 'error' => null];
}

// delete a replaced profile photo so we do not keep personal images we no longer use
function deleteProfilePhoto(?string $path): void
{
    deleteImage($path);
}

// passwords

// change their own password after checking the current one, returns any errors
function changeOwnPassword(mysqli $conn, int $accountID, string $current, string $new, string $confirm): array
{
    $stmt = $conn->prepare("SELECT passwordHash FROM UserAccount WHERE accountID = ?");
    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['passwordHash'] ?? null;

    if (!$hash || !password_verify($current, $hash)) {
        return ['Current password is incorrect.'];
    }

    $errors = validateNewPassword($new);

    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }
    if (password_verify($new, $hash)) {
        $errors[] = 'New password must be different from your current password.';
    }

    if ($errors) {
        return $errors;
    }

    // save the new password
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $update = $conn->prepare(
        "UPDATE UserAccount
         SET passwordHash = ?, passwordChangedAt = NOW(), mustChangePassword = FALSE
         WHERE accountID = ?"
    );
    $update->bind_param("si", $newHash, $accountID);

    return $update->execute() ? [] : ['Your password could not be changed. Please try again.'];
}

// first login or after an admin reset, save the name and new password together
// the temporary password is not asked for again since they just logged in with it
function completeAccountSetup(mysqli $conn, int $accountID, array &$input, string $new, string $confirm): array
{
    $errors = array_merge(validatePersonalDetails($input), validateNewPassword($new));

    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    // the new password must differ from the temporary one
    $stmt = $conn->prepare("SELECT passwordHash FROM UserAccount WHERE accountID = ?");
    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $hash = $stmt->get_result()->fetch_assoc()['passwordHash'] ?? null;

    if ($hash && password_verify($new, $hash)) {
        $errors[] = 'New password must be different from your temporary password.';
    }

    if ($errors) {
        return $errors;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $update = $conn->prepare(
        "UPDATE UserAccount
         SET firstName = ?, lastName = ?, passwordHash = ?, passwordChangedAt = NOW(), mustChangePassword = FALSE
         WHERE accountID = ? AND mustChangePassword = TRUE"
    );
    $update->bind_param("sssi", $input['firstName'], $input['lastName'], $newHash, $accountID);

    return ($update->execute() && $update->affected_rows === 1)
        ? []
        : ['Your details could not be saved. Please try again.'];
}

// random temporary password that meets the password rules
function generateTemporaryPassword(int $length = 12): string
{
    // no look alike characters so it can be read out safely
    $letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $digits = '23456789';
    $all = $letters . $digits;

    do {
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
    } while (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password));

    return $password;
}

// activity

// the staff member's most recent actions, newest first
// campaigns have no date so they sort last and events show the event's own date
function getRecentActivity(mysqli $conn, int $accountID, int $limit = 10): array
{
    $stmt = $conn->prepare(
        "SELECT * FROM (
            SELECT 'Archive' AS type,
                   CONCAT(IF(l.action = 'archived', 'Archived ', 'Restored '), LOWER(e.entityType), ': ',
                          COALESCE(p.name, ev.name, ca.name, q.name, CONCAT('#', l.entityID))) AS summary,
                   l.timestamp AS happenedAt
            FROM ArchiveLog l
            JOIN ArchivableEntity e ON e.entityID = l.entityID
            LEFT JOIN Partner p ON p.partnerID = l.entityID
            LEFT JOIN Event ev ON ev.eventID = l.entityID
            LEFT JOIN Campaign ca ON ca.campaignID = l.entityID
            LEFT JOIN Enquiry q ON q.enquiryID = l.entityID
            WHERE l.performedBy = ?

            UNION ALL
            SELECT 'Enquiry', CONCAT('Handling enquiry from ', name, ' (', status, ')'), dateCreated
            FROM Enquiry WHERE handledBy = ?

            UNION ALL
            SELECT 'Event', CONCAT('Created event: ', name), CAST(eventDate AS DATETIME)
            FROM Event WHERE createdBy = ?

            UNION ALL
            SELECT 'Campaign', CONCAT('Created campaign: ', name), NULL
            FROM Campaign WHERE createdBy = ?

            UNION ALL
            SELECT 'Report', CONCAT('Generated ', reportType, ' report'), dateGenerated
            FROM Report WHERE generatedBy = ?
         ) activity
         ORDER BY happenedAt IS NULL, happenedAt DESC
         LIMIT ?"
    );

    if (!$stmt) {
        error_log('getRecentActivity failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("iiiiii", $accountID, $accountID, $accountID, $accountID, $accountID, $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// admin role and account changes, used by accountData.php

// stop a role change while records are still linked to the old role
// returns an error message or null if the change is safe
function roleChangeBlocker(mysqli $conn, int $accountID, string $currentRole): ?string
{
    $checks = [
        'Admin'            => ["SELECT COUNT(*) FROM Report WHERE generatedBy = ?", 'reports'],
        // marketing has none since events and campaigns link to the account not the role
        'Customer Service' => ["SELECT COUNT(*) FROM Enquiry WHERE handledBy = ?", 'enquiries'],
    ];

    if (!isset($checks[$currentRole])) {
        return null;
    }

    [$sql, $label] = $checks[$currentRole];
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_row()[0];

    return $count > 0
        ? "This user's role can't be changed from $currentRole while they still have $label linked to that role ($count). Reassign them first."
        : null;
}

// save an admin's changes to an account, the username can never be changed
function adminUpdateProfile(mysqli $conn, array $target, array $input): bool
{
    $accountID = (int) $target['accountID'];

    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "UPDATE UserAccount
         SET firstName = ?, lastName = ?, email = ?, accountStatus = ?
         WHERE accountID = ?"
    );
    $stmt->bind_param("ssssi", $input['firstName'], $input['lastName'], $input['email'],
        $input['accountStatus'], $accountID);
    $ok = $stmt->execute();

    // changing role moves the account from one role table to the other
    if ($ok && $target['role'] !== $input['role']) {
        if ($target['role'] !== null) {
            $oldTable = ACCOUNT_ROLES[$target['role']];
            $remove = $conn->prepare("DELETE FROM `$oldTable` WHERE accountID = ?");
            $remove->bind_param("i", $accountID);
            $ok = $remove->execute();
        }

        if ($ok) {
            $newTable = ACCOUNT_ROLES[$input['role']];
            $add = $conn->prepare("INSERT INTO `$newTable` (accountID) VALUES (?)");
            $add->bind_param("i", $accountID);
            $ok = $add->execute();
        }
    }

    if (!$ok) {
        $conn->rollback();
        return false;
    }

    $conn->commit();
    return true;
}
