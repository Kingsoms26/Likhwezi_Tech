<?php
// accountData.php lists, creates, archives and changes staff accounts for the admin accounts page
// roles live in the admin, marketing and customer service tables and a username never changes
// accounts are never deleted, they are deactivated or archived once the person has left
// archived accounts cannot log in but keep their row so their history stays with them
// an admin cannot suspend, deactivate, archive or demote their own account
// a password set by an admin is temporary and must be changed at the next login

require_once __DIR__ . '/profileData.php';

// status filter that lists archived accounts instead of current ones
const ACCOUNT_ARCHIVED_FILTER = 'archived';

// reading accounts

// account query with the role, shared by the list and the count
const ACCOUNT_LIST_SQL =
    "SELECT u.accountID, u.username, u.email, u.accountStatus, u.dateCreated,
            u.isArchived, u.archivedAt,
            CASE
                WHEN a.accountID IS NOT NULL THEN 'Admin'
                WHEN m.accountID IS NOT NULL THEN 'Marketing'
                WHEN s.accountID IS NOT NULL THEN 'Customer Service'
            END AS role
     FROM UserAccount u
     LEFT JOIN Admin a ON a.accountID = u.accountID
     LEFT JOIN StaffMarketing m ON m.accountID = u.accountID
     LEFT JOIN StaffCustomerService s ON s.accountID = u.accountID";

// one page of accounts matching the search, role and status, newest first
// archived accounts are left out unless the archived filter is chosen
function getAccountList(mysqli $conn, string $search, string $role, string $status, int $page, int $perPage = 10): array
{
    $where = [];
    $types = '';
    $params = [];

    if ($search !== '') {
        $where[] = '(username LIKE ? OR email LIKE ?)';
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $types .= 'ss';
        array_push($params, $like, $like);
    }
    if ($role !== 'all') {
        $where[] = 'role = ?';
        $types .= 's';
        $params[] = $role;
    }
    if ($status === ACCOUNT_ARCHIVED_FILTER) {
        $where[] = 'isArchived = TRUE';
    } else {
        $where[] = 'isArchived = FALSE';
        if ($status !== 'all') {
            $where[] = 'accountStatus = ?';
            $types .= 's';
            $params[] = $status;
        }
    }

    // count the matching accounts
    $from = ' FROM (' . ACCOUNT_LIST_SQL . ') accounts' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

    $countStmt = $conn->prepare('SELECT COUNT(*)' . $from);
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int) $countStmt->get_result()->fetch_row()[0];

    // work out the pages then fetch this page
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare('SELECT *' . $from . ' ORDER BY dateCreated DESC, accountID DESC LIMIT ? OFFSET ?');
    $stmt->bind_param($types . 'ii', ...[...$params, $perPage, $offset]);
    $stmt->execute();

    return [
        'rows'  => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'total' => $total,
        'page'  => $page,
        'pages' => $pages,
    ];
}

// number of active accounts
function getActiveAccountCount(mysqli $conn): int
{
    $result = $conn->query("SELECT COUNT(*) FROM UserAccount WHERE accountStatus = 'active' AND isArchived = FALSE");

    return $result ? (int) $result->fetch_row()[0] : 0;
}

// true if the username is already used
function isUsernameTaken(mysqli $conn, string $username): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM UserAccount WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

// checking the forms

// check the email for the add and edit forms
function validateAccountEmail(mysqli $conn, array &$input, int $ignoreAccountID): array
{
    $input['email'] = strtolower(trim($input['email'] ?? ''));

    if (mb_strlen($input['email']) > 255 || filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
        return ['Enter a valid email address.'];
    }
    if (isEmailTaken($conn, $input['email'], $ignoreAccountID)) {
        return ['That email address is already in use.'];
    }

    return [];
}

// check the add account form
function validateNewAccount(mysqli $conn, array &$input): array
{
    $errors = [];

    $input['username'] = trim($input['username'] ?? '');
    if (mb_strlen($input['username']) < 3 || mb_strlen($input['username']) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    } elseif (isUsernameTaken($conn, $input['username'])) {
        $errors[] = 'That username is already in use.';
    }

    $errors = array_merge($errors, validateAccountEmail($conn, $input, 0));

    if (!isset(ACCOUNT_ROLES[$input['role'] ?? ''])) {
        $errors[] = 'Select a valid role.';
    }
    if (!in_array($input['accountStatus'] ?? '', ACCOUNT_STATUSES, true)) {
        $errors[] = 'Select a valid status.';
    }

    return array_merge($errors, validateNewPassword($input['password'] ?? ''));
}

// check the edit account form, email and role
function validateAccountEdit(mysqli $conn, array &$input, array $target, int $editorID): array
{
    if ($target['isArchived']) {
        return ['This account is archived. Restore it before making changes.'];
    }

    $errors = validateAccountEmail($conn, $input, (int) $target['accountID']);

    if (!isset(ACCOUNT_ROLES[$input['role'] ?? ''])) {
        $errors[] = 'Select a valid role.';
    } elseif ((int) $target['accountID'] === $editorID && $input['role'] !== 'Admin') {
        $errors[] = 'You cannot remove the Admin role from your own account.';
    }

    // changing role must not leave behind or delete the records tied to the old role
    if (!$errors && $target['role'] !== null && $target['role'] !== $input['role']) {
        $blocker = roleChangeBlocker($conn, (int) $target['accountID'], $target['role']);
        if ($blocker) {
            $errors[] = $blocker;
        }
    }

    return $errors;
}

// check the manage account form, status and an optional new password
function validateAccountAccess(array $input, array $target, int $editorID): array
{
    if ($target['isArchived']) {
        return ['This account is archived. Restore it before making changes.'];
    }

    $errors = [];

    if (!in_array($input['accountStatus'] ?? '', ACCOUNT_STATUSES, true)) {
        $errors[] = 'Select a valid account status.';
    }

    if ((int) $target['accountID'] === $editorID) {
        if (($input['accountStatus'] ?? '') !== 'active') {
            $errors[] = 'You cannot suspend or deactivate your own account.';
        }
        if (($input['password'] ?? '') !== '') {
            $errors[] = 'To change your own password, use My Profile.';
        }
    }

    if (!$errors && ($input['password'] ?? '') !== '') {
        $errors = validateNewPassword($input['password']);
    }

    return $errors;
}

// saving

// create the account and its role together, the password must be changed at first login
function createAccount(mysqli $conn, array $input, int $creatorID): ?int
{
    $hash = password_hash($input['password'], PASSWORD_DEFAULT);

    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "INSERT INTO UserAccount (createdBy, email, username, passwordHash, accountStatus, mustChangePassword)
         VALUES (?, ?, ?, ?, ?, TRUE)"
    );
    $stmt->bind_param("issss", $creatorID, $input['email'], $input['username'], $hash, $input['accountStatus']);
    $ok = $stmt->execute();
    $accountID = $conn->insert_id;

    if ($ok) {
        $roleTable = ACCOUNT_ROLES[$input['role']];
        $add = $conn->prepare("INSERT INTO `$roleTable` (accountID) VALUES (?)");
        $add->bind_param("i", $accountID);
        $ok = $add->execute();
    }

    if (!$ok) {
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $accountID;
}

// save the email and role, names and status stay as they are
function updateAccountDetails(mysqli $conn, array $target, array $input): bool
{
    return adminUpdateProfile($conn, $target, [
        'firstName'     => $target['firstName'],
        'lastName'      => $target['lastName'],
        'email'         => $input['email'],
        'role'          => $input['role'],
        'accountStatus' => $target['accountStatus'],
    ]);
}

// save the status and a new temporary password when given
function updateAccountAccess(mysqli $conn, int $accountID, string $status, string $password): bool
{
    if ($password === '') {
        $stmt = $conn->prepare("UPDATE UserAccount SET accountStatus = ? WHERE accountID = ?");
        $stmt->bind_param("si", $status, $accountID);
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "UPDATE UserAccount SET accountStatus = ?, passwordHash = ?, mustChangePassword = TRUE
             WHERE accountID = ?"
        );
        $stmt->bind_param("ssi", $status, $hash, $accountID);
    }

    return $stmt->execute();
}

// archiving

// why the account cannot be archived, or null if it can
function accountArchiveBlocker(array $target, int $editorID): ?string
{
    if ($target['isArchived']) {
        return 'That account is already archived.';
    }
    if ((int) $target['accountID'] === $editorID) {
        return 'You cannot archive your own account.';
    }

    return null;
}

// archive an account so it cannot log in and is hidden from the list
// open enquiries it claimed go back to unclaimed, closed ones keep it as a record of who dealt with them
// returns the enquiries released for the activity log
function archiveAccount(mysqli $conn, int $accountID, int $archivedBy): ?array
{
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "UPDATE UserAccount
         SET isArchived = TRUE, archivedAt = NOW(), archivedBy = ?, accountStatus = 'deactivated'
         WHERE accountID = ? AND isArchived = FALSE"
    );
    $stmt->bind_param("ii", $archivedBy, $accountID);
    $ok = $stmt->execute() && $stmt->affected_rows === 1;

    // release its open enquiries
    $released = [];
    if ($ok) {
        $open = $conn->prepare("SELECT enquiryID FROM Enquiry WHERE handledBy = ? AND status <> 'closed' FOR UPDATE");
        $open->bind_param("i", $accountID);
        $ok = $open->execute();
        $released = $ok ? array_map('intval', array_column($open->get_result()->fetch_all(MYSQLI_ASSOC), 'enquiryID')) : [];
    }
    if ($ok && $released) {
        $release = $conn->prepare("UPDATE Enquiry SET handledBy = NULL WHERE handledBy = ? AND status <> 'closed'");
        $release->bind_param("i", $accountID);
        $ok = $release->execute();
    }

    if (!$ok) {
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $released;
}

// bring an archived account back to the list, it stays deactivated until an admin reactivates it
function restoreAccount(mysqli $conn, int $accountID): bool
{
    $stmt = $conn->prepare(
        "UPDATE UserAccount SET isArchived = FALSE, archivedAt = NULL, archivedBy = NULL
         WHERE accountID = ? AND isArchived = TRUE"
    );
    $stmt->bind_param("i", $accountID);

    return $stmt->execute() && $stmt->affected_rows === 1;
}

// login details for new accounts
// new accounts get a password from generateTemporaryPassword() in profileData.php

// edit here, the staff login page address sent to new users in the email and whatsapp message
// leave it empty to use this site's own staffLogin.php
const STAFF_LOGIN_URL = '';

// full address of the staff login page, the site root is one folder up from staff
function staffLoginURL(): string
{
    if (STAFF_LOGIN_URL !== '') {
        return STAFF_LOGIN_URL;
    }

    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $root = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/', 2)), '/');

    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $root . '/staffLogin.php';
}

// the login details as plain text for the email and whatsapp messages
function newAccountMessage(array $credentials): string
{
    return "Hi,\n\n"
        . "A Likhwezi Technologies staff account has been created for you.\n\n"
        . "Login page: {$credentials['loginURL']}\n"
        . "Username: {$credentials['username']}\n"
        . "Temporary password: {$credentials['password']}\n\n"
        . "When you first log in, you will be asked for your name and to choose your own password.";
}
