<?php


function getAccountSeedData()
{
    return [
        [
            'id' => 1, 'username' => 'admin01', 'email' => 'admin@example.com',
            'role' => 'Admin', 'status' => 'active', 'dateCreated' => '2026-09-12',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 2, 'username' => 'marketing01', 'email' => 'marketing@example.com',
            'role' => 'Marketing', 'status' => 'active', 'dateCreated' => '2026-09-10',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 3, 'username' => 'support01', 'email' => 'support@example.com',
            'role' => 'Customer Service', 'status' => 'suspended', 'dateCreated' => '2026-09-08',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 4, 'username' => 'marketing02', 'email' => 'campaigns@example.com',
            'role' => 'Marketing', 'status' => 'active', 'dateCreated' => '2026-09-06',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 5, 'username' => 'support02', 'email' => 'support2@example.com',
            'role' => 'Customer Service', 'status' => 'active', 'dateCreated' => '2026-09-03',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 6, 'username' => 'admin02', 'email' => 'admin2@example.com',
            'role' => 'Admin', 'status' => 'active', 'dateCreated' => '2026-09-01',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 7, 'username' => 'marketing03', 'email' => 'content@example.com',
            'role' => 'Marketing', 'status' => 'active', 'dateCreated' => '2026-08-28',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 8, 'username' => 'support03', 'email' => 'helpdesk@example.com',
            'role' => 'Customer Service', 'status' => 'deactivated', 'dateCreated' => '2026-08-24',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 9, 'username' => 'admin03', 'email' => 'system@example.com',
            'role' => 'Admin', 'status' => 'active', 'dateCreated' => '2026-08-20',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 10, 'username' => 'marketing04', 'email' => 'digital@example.com',
            'role' => 'Marketing', 'status' => 'suspended', 'dateCreated' => '2026-08-18',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 11, 'username' => 'support04', 'email' => 'customer@example.com',
            'role' => 'Customer Service', 'status' => 'active', 'dateCreated' => '2026-08-12',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ],
        [
            'id' => 12, 'username' => 'admin04', 'email' => 'administrator@example.com',
            'role' => 'Admin', 'status' => 'active', 'dateCreated' => '2026-08-05',
            'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT)
        ]
    ];
}

function getAccountRecords()
{
    if (!isset($_SESSION['accountRecords']) || !is_array($_SESSION['accountRecords'])) {
        $_SESSION['accountRecords'] = getAccountSeedData();
    }

    return $_SESSION['accountRecords'];
}

function saveAccountRecords(array $accounts)
{
    $_SESSION['accountRecords'] = array_values($accounts);
}

function getAccountRoles()
{
    return ['Admin', 'Marketing', 'Customer Service'];
}

function getAccountStatuses()
{
    return ['active', 'suspended', 'deactivated'];
}

function getAccountById($id)
{
    foreach (getAccountRecords() as $account) {
        if ((int) $account['id'] === (int) $id) {
            return $account;
        }
    }

    return null;
}

function getAccounts($search = '', $role = 'all', $status = 'all')
{
    $search = trim((string) $search);
    $role = in_array($role, getAccountRoles(), true) ? $role : 'all';
    $status = in_array($status, getAccountStatuses(), true) ? $status : 'all';

    $filteredAccounts = [];

    foreach (getAccountRecords() as $account) {
        $searchMatches = (
            $search === ''
            || stripos($account['username'], $search) !== false
            || stripos($account['email'], $search) !== false
        );

        $roleMatches = $role === 'all' || $account['role'] === $role;
        $statusMatches = $status === 'all' || $account['status'] === $status;

        if ($searchMatches && $roleMatches && $statusMatches) {
            $filteredAccounts[] = $account;
        }
    }

    return $filteredAccounts;
}

function getActiveAccountCount()
{
    $activeCount = 0;

    foreach (getAccountRecords() as $account) {
        if ($account['status'] === 'active') {
            $activeCount++;
        }
    }

    return $activeCount;
}

/*
 * Validates the fields used by Add/Edit actions.
 * Returns an array of human-readable errors.
 */
function validateAccountFields($username, $email, $role, $status, $password = '', $passwordRequired = false, $ignoreId = 0)
{
    $errors = [];
    $username = trim((string) $username);
    $email = trim((string) $email);

    if ($username === '' || strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!in_array($role, getAccountRoles(), true)) {
        $errors[] = 'Select a valid role.';
    }

    if (!in_array($status, getAccountStatuses(), true)) {
        $errors[] = 'Select a valid status.';
    }

    if ($passwordRequired && strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $errors[] = 'New password must contain at least 8 characters.';
    }

    foreach (getAccountRecords() as $account) {
        if ((int) $account['id'] === (int) $ignoreId) {
            continue;
        }

        if (strcasecmp($account['username'], $username) === 0) {
            $errors[] = 'That username is already in use.';
        }

        if (strcasecmp($account['email'], $email) === 0) {
            $errors[] = 'That email address is already in use.';
        }
    }

    return $errors;
}

function createAccount($username, $email, $role, $status, $password)
{
    $accounts = getAccountRecords();
    $ids = array_column($accounts, 'id');
    $nextId = $ids ? max($ids) + 1 : 1;

    $accounts[] = [
        'id' => $nextId,
        'username' => trim($username),
        'email' => trim($email),
        'role' => $role,
        'status' => $status,
        'dateCreated' => date('Y-m-d'),
        'passwordHash' => password_hash($password, PASSWORD_DEFAULT)
    ];

    saveAccountRecords($accounts);
}

function updateAccount($id, $username, $email, $role, $status, $password = '')
{
    $accounts = getAccountRecords();

    foreach ($accounts as &$account) {
        if ((int) $account['id'] !== (int) $id) {
            continue;
        }

        $account['username'] = trim($username);
        $account['email'] = trim($email);
        $account['role'] = $role;
        $account['status'] = $status;

        if ($password !== '') {
            $account['passwordHash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        break;
    }
    unset($account);

    saveAccountRecords($accounts);
}
