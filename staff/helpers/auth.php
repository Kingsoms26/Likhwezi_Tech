<?php
// auth.php checks the staff member is logged in on every dashboard page
// also contains the csrf token and flash message helpers used by the forms

require_once __DIR__ . '/profileData.php';
require_once __DIR__ . '/activityLog.php';
require_once __DIR__ . '/../../includes/helpers/cache.php';

// page that forced password changes are sent to
const CHANGE_PASSWORD_PAGE = 'profile.php';

// reload the logged in account on every request so a blocked account is logged out straight away
// sends them to the login page or the change password page when needed
function requireStaff(mysqli $conn): array
{
    $account = isset($_SESSION['accountID']) ? getAccount($conn, (int) $_SESSION['accountID']) : null;

    // log out anyone not logged in, not active, archived or without a staff role
    if (!$account || $account['accountStatus'] !== 'active' || $account['isArchived'] || $account['role'] === null) {
        $_SESSION = [];
        session_destroy();
        header('Location: ../staffLogin.php');
        exit;
    }

    // refresh the session details
    $_SESSION['username'] = $account['username'];
    $_SESSION['role'] = $account['role'];
    $_SESSION['firstName'] = $account['firstName'];
    $_SESSION['lastName'] = $account['lastName'];
    $_SESSION['profileImage'] = $account['profileImageURL'];

    // block every other page until a required password change is done
    if ($account['mustChangePassword'] && basename($_SERVER['SCRIPT_NAME']) !== CHANGE_PASSWORD_PAGE) {
        header('Location: ' . CHANGE_PASSWORD_PAGE);
        exit;
    }

    // any staff save clears the public page cache so changes show straight away
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        clearCache();
    }

    return $account;
}

// send staff without the right role to their profile with a message
// keep in step with the links in components/dashboardNavbar.php
function requireRole(array $account, string|array $roles): void
{
    if (!in_array($account['role'], (array) $roles, true)) {
        setFlash('error', 'You do not have access to that page.');
        header('Location: profile.php');
        exit;
    }
}

// csrf

// one token per session shared by every form
function csrfToken(): string
{
    $_SESSION['csrfToken'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrfToken'];
}

// hidden input to place inside every post form
function csrfField(): string
{
    return '<input type="hidden" name="csrfToken" value="' . htmlspecialchars(csrfToken()) . '">';
}

// check the submitted token matches
function verifyCsrf(): bool
{
    return hash_equals(csrfToken(), $_POST['csrfToken'] ?? '');
}

// flash messages, shown once after a redirect

// save a message for the next page
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// read the message and clear it
function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
