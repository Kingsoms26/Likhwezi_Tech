<?php

session_start();

/* Temporary session values for the standalone framework. */
$_SESSION['username'] = $_SESSION['username'] ?? 'Name Surname';
$_SESSION['role'] = $_SESSION['role'] ?? 'Admin';

/* Accounts is an Admin page. */
if ($_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/tools/accountContent.php';

/*
 * Handle account actions before any HTML is sent.
 * Post/Redirect/Get prevents duplicate changes when the page is refreshed.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $notice = null;

    if (!hash_equals($_SESSION['csrfToken'], $_POST['csrfToken'] ?? '')) {
        $notice = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
    } elseif ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        $errors = validateAccountFields(
            $username,
            $email,
            $role,
            $status,
            $password,
            true
        );

        if ($errors) {
            $notice = ['type' => 'error', 'text' => implode(' ', $errors)];
        } else {
            createAccount($username, $email, $role, $status, $password);
            $notice = ['type' => 'success', 'text' => "Account '{$username}' was created."];
        }
    } elseif ($action === 'edit') {
        $account = getAccountById($id);

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        if (!$account) {
            $notice = ['type' => 'error', 'text' => 'The selected account could not be found.'];
        } else {
            $errors = validateAccountFields(
                $username,
                $email,
                $role,
                $account['status'],
                '',
                false,
                $id
            );

            if ($errors) {
                $notice = ['type' => 'error', 'text' => implode(' ', $errors)];
            } else {
                updateAccount(
                    $id,
                    $username,
                    $email,
                    $role,
                    $account['status']
                );
                $notice = ['type' => 'success', 'text' => "Account '{$username}' was updated."];
            }
        }
    } elseif ($action === 'manage') {
        $account = getAccountById($id);
        $status = $_POST['status'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$account) {
            $notice = ['type' => 'error', 'text' => 'The selected account could not be found.'];
        } elseif (!in_array($status, getAccountStatuses(), true)) {
            $notice = ['type' => 'error', 'text' => 'Select a valid account status.'];
        } elseif ($password !== '' && strlen($password) < 8) {
            $notice = ['type' => 'error', 'text' => 'New password must contain at least 8 characters.'];
        } else {
            updateAccount(
                $id,
                $account['username'],
                $account['email'],
                $account['role'],
                $status,
                $password
            );

            $message = "Account '{$account['username']}' was updated.";
            if ($password !== '') {
                $message .= ' The password was changed.';
            }

            $notice = ['type' => 'success', 'text' => $message];
        }
    } else {
        $notice = ['type' => 'error', 'text' => 'Unknown account action.'];
    }

    $_SESSION['accountNotice'] = $notice;

    $returnQuery = $_GET;
    unset($returnQuery['page']);

    $returnUrl = 'accounts.php';
    if ($returnQuery) {
        $returnUrl .= '?' . http_build_query($returnQuery);
    }

    header('Location: ' . $returnUrl);
    exit;
}

/*
 * Read search/filter values from the URL.
 *
 * Example:
 *     accounts.php?search=admin&role=Admin&status=active
 */
$search = trim(substr((string) ($_GET['search'] ?? ''), 0, 100));
$role = $_GET['role'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));

/* Invalid filter values fall back to their "all" options. */
if ($role !== 'all' && !in_array($role, getAccountRoles(), true)) {
    $role = 'all';
}

if ($status !== 'all' && !in_array($status, getAccountStatuses(), true)) {
    $status = 'all';
}

$accounts = getAccounts($search, $role, $status);

/*
 * Five rows per page keeps the prototype easy to test.
 * Use around 10-15 rows once the real database is connected.
 */
$rowsPerPage = 5;
$totalAccounts = count($accounts);
$totalPages = max(1, (int) ceil($totalAccounts / $rowsPerPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$startIndex = ($page - 1) * $rowsPerPage;
$visibleAccounts = array_slice($accounts, $startIndex, $rowsPerPage);

$notice = $_SESSION['accountNotice'] ?? null;
unset($_SESSION['accountNotice']);

$pageTitle = 'Accounts';
$activePage = 'accounts';

include __DIR__ . '/components/dashboard.php';

/* Keeps pagination links readable and preserves the current filters. */
function accountPageUrl($page, $search, $role, $status)
{
    $params = [
        'page' => $page,
        'search' => $search,
        'role' => $role,
        'status' => $status
    ];

    $params = array_filter($params, fn($value) => $value !== '' && $value !== 'all' && $value !== 1);

    return 'accounts.php' . ($params ? '?' . http_build_query($params) : '');
}
?>

<div class="registrations-page">

    <?php if ($notice) : ?>
        <div class="page-notice page-notice-<?= htmlspecialchars($notice['type']) ?>" role="status">
            <?= htmlspecialchars($notice['text']) ?>
        </div>
    <?php endif; ?>

    <section class="dashboard-cards accounts-summary">
        <?php
        $cardTitle = 'Active Accounts';
        $cardValue = getActiveAccountCount();
        $cardMeta = 'Currently active employee accounts';
        $cardClass = 'active-accounts-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
    </section>

    <section class="dashboard-panel">

        <div class="panel-header">
            <h2>Employee Accounts</h2>
        </div>

        <div class="panel-body">

            <!-- Filters intentionally follow registration.php so both pages feel like one system. -->
            <form class="registration-filters" method="get" action="accounts.php">

                <div class="filter-field filter-search">
                    <label for="account-search">Search</label>
                    <input
                        type="search"
                        id="account-search"
                        name="search"
                        maxlength="100"
                        placeholder="Username or email"
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="filter-field">
                    <label for="account-role">Role</label>
                    <select id="account-role" name="role">
                        <option value="all">All roles</option>
                        <?php foreach (getAccountRoles() as $availableRole) : ?>
                            <option value="<?= htmlspecialchars($availableRole) ?>"
                                <?= $role === $availableRole ? 'selected' : '' ?>>
                                <?= htmlspecialchars($availableRole) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="account-status">Status</label>
                    <select id="account-status" name="status">
                        <option value="all">All statuses</option>
                        <?php foreach (getAccountStatuses() as $availableStatus) : ?>
                            <option value="<?= htmlspecialchars($availableStatus) ?>"
                                <?= $status === $availableStatus ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($availableStatus)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="panel-button button-primary" type="submit">Apply filters</button>
                    <?php if ($search !== '' || $role !== 'all' || $status !== 'all') : ?>
                        <a class="panel-button" href="accounts.php">Clear</a>
                    <?php endif; ?>
                    <button
                        class="panel-button button-primary"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#add-account-modal"
                    >
                        + Add Account
                    </button>
                </div>

            </form>

            <?php if (!$visibleAccounts) : ?>

                <div class="empty-state">
                    <?php if ($search !== '' || $role !== 'all' || $status !== 'all') : ?>
                        <p>No accounts match these filters.</p>
                        <a class="panel-button" href="accounts.php">Clear filters</a>
                    <?php else : ?>
                        <p>No employee accounts are available.</p>
                    <?php endif; ?>
                </div>

            <?php else : ?>

                <div class="dashboard-table-wrap">
                    <table class="dashboard-table registrations-table">
                        <thead>
                            <tr>
                                <th scope="col">Username</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date Created</th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($visibleAccounts as $account) : ?>
                                <?php
                                $roleClass = 'role-customer-service';

                                if ($account['role'] === 'Admin') {
                                    $roleClass = 'role-admin';
                                } elseif ($account['role'] === 'Marketing') {
                                    $roleClass = 'role-marketing';
                                }

                                $statusClass = 'status-' . $account['status'];
                                ?>

                                <tr>
                                    <td class="registrant-name">
                                        <?= htmlspecialchars($account['username']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($account['email']) ?></td>
                                    <td>
                                        <span class="account-role <?= $roleClass ?>">
                                            <?= htmlspecialchars($account['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="account-status <?= $statusClass ?>">
                                            <?= htmlspecialchars(ucfirst($account['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="registrant-date">
                                        <?= htmlspecialchars($account['dateCreated']) ?>
                                    </td>
                                    <td class="table-actions">
                                        <button
                                            type="button"
                                            class="table-action"
                                            data-bs-toggle="modal"
                                            data-bs-target="#edit-account-modal"
                                            data-account-id="<?= (int) $account['id'] ?>"
                                            data-username="<?= htmlspecialchars($account['username'], ENT_QUOTES) ?>"
                                            data-email="<?= htmlspecialchars($account['email'], ENT_QUOTES) ?>"
                                            data-role="<?= htmlspecialchars($account['role'], ENT_QUOTES) ?>"
                                        >
                                            Edit<span class="visually-hidden"> <?= htmlspecialchars($account['username']) ?></span>
                                        </button>

                                        <button
                                            type="button"
                                            class="table-action"
                                            data-bs-toggle="modal"
                                            data-bs-target="#manage-account-modal"
                                            data-account-id="<?= (int) $account['id'] ?>"
                                            data-username="<?= htmlspecialchars($account['username'], ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($account['status'], ENT_QUOTES) ?>"
                                        >
                                            Manage<span class="visually-hidden"> <?= htmlspecialchars($account['username']) ?></span>
                                        </button>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1) : ?>
                    <nav class="registration-pagination" aria-label="Account pages">
                        <?php if ($page > 1) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(accountPageUrl($page - 1, $search, $role, $status)) ?>">Previous</a>
                        <?php endif; ?>

                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) : ?>
                            <a
                                class="panel-button <?= $pageNumber === $page ? 'active' : '' ?>"
                                href="<?= htmlspecialchars(accountPageUrl($pageNumber, $search, $role, $status)) ?>"
                                <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>
                            >
                                <?= $pageNumber ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(accountPageUrl($page + 1, $search, $role, $status)) ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </section>

    <!-- ==============================================================
         ADD ACCOUNT MODAL
         ============================================================== -->
    <div class="modal fade registration-modal account-modal" id="add-account-modal"
         tabindex="-1" aria-labelledby="add-account-title" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable account-modal-dialog">
            <form class="modal-content" method="post" action="accounts.php">

                <div class="modal-header">
                    <h2 class="modal-title" id="add-account-title">Add Account</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="account-modal-intro">
                        <span class="account-modal-kicker">New employee</span>
                        <p class="modal-help">Create an employee account. Passwords are stored as hashes and are never displayed.</p>
                    </div>

                    <input type="hidden" name="csrfToken" value="<?= htmlspecialchars($_SESSION['csrfToken']) ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="account-form-grid">
                        <div class="account-form-field">
                            <label for="add-username">Username</label>
                            <input id="add-username" name="username" type="text" maxlength="50" required>
                        </div>

                        <div class="account-form-field">
                            <label for="add-email">Email</label>
                            <input id="add-email" name="email" type="email" maxlength="150" required>
                        </div>

                        <div class="account-form-field">
                            <label for="add-role">Role</label>
                            <select id="add-role" name="role" required>
                                <?php foreach (getAccountRoles() as $availableRole) : ?>
                                    <option value="<?= htmlspecialchars($availableRole) ?>">
                                        <?= htmlspecialchars($availableRole) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="account-form-field">
                            <label for="add-status">Status</label>
                            <select id="add-status" name="status" required>
                                <?php foreach (getAccountStatuses() as $availableStatus) : ?>
                                    <option value="<?= htmlspecialchars($availableStatus) ?>"
                                        <?= $availableStatus === 'active' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($availableStatus)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="account-form-field account-form-field-full">
                            <label for="add-password">Temporary Password</label>
                            <input id="add-password" name="password" type="password"
                                   minlength="8" autocomplete="new-password" required>
                            <small>Minimum 8 characters. The password cannot be recovered after it is hashed.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button accounts-modal-primary">Create Account</button>
                </div>

            </form>
        </div>
    </div>


    <!-- ==============================================================
         EDIT ACCOUNT MODAL
         ============================================================== -->
    <div class="modal fade registration-modal account-modal" id="edit-account-modal"
         tabindex="-1" aria-labelledby="edit-account-title" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable account-modal-dialog">
            <form class="modal-content" method="post" action="accounts.php">

                <div class="modal-header">
                    <h2 class="modal-title" id="edit-account-title">Edit Account</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="account-modal-intro">
                        <span class="account-modal-kicker">Account details</span>
                        <p class="modal-help">Update the account details below. Password changes are handled under Manage.</p>
                    </div>

                    <input type="hidden" name="csrfToken" value="<?= htmlspecialchars($_SESSION['csrfToken']) ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-account-id">

                    <div class="account-form-grid">
                        <div class="account-form-field">
                            <label for="edit-username">Username</label>
                            <input id="edit-username" name="username" type="text" maxlength="50" required>
                        </div>

                        <div class="account-form-field">
                            <label for="edit-email">Email</label>
                            <input id="edit-email" name="email" type="email" maxlength="150" required>
                        </div>

                        <div class="account-form-field account-form-field-full">
                            <label for="edit-role">Role</label>
                            <select id="edit-role" name="role" required>
                                <?php foreach (getAccountRoles() as $availableRole) : ?>
                                    <option value="<?= htmlspecialchars($availableRole) ?>">
                                        <?= htmlspecialchars($availableRole) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button accounts-modal-primary">Save Changes</button>
                </div>

            </form>
        </div>
    </div>


    <!-- ==============================================================
         MANAGE ACCOUNT MODAL
         ============================================================== -->
    <div class="modal fade registration-modal account-modal" id="manage-account-modal"
         tabindex="-1" aria-labelledby="manage-account-title" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable account-modal-dialog">
            <form class="modal-content" method="post" action="accounts.php">

                <div class="modal-header">
                    <h2 class="modal-title" id="manage-account-title">Manage Account</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="account-modal-intro">
                        <span class="account-modal-kicker">Access & security</span>
                        <p class="modal-help">Change account access or reset the password. No password is shown here.</p>
                    </div>

                    <input type="hidden" name="csrfToken" value="<?= htmlspecialchars($_SESSION['csrfToken']) ?>">
                    <input type="hidden" name="action" value="manage">
                    <input type="hidden" name="id" id="manage-account-id">

                    <div class="account-managed-name">
                        <span>Account</span>
                        <strong id="manage-account-name">Account</strong>
                    </div>

                    <div class="account-form-grid">
                        <div class="account-form-field account-form-field-full">
                            <label for="manage-status">Account Status</label>
                            <select id="manage-status" name="status" required>
                                <?php foreach (getAccountStatuses() as $availableStatus) : ?>
                                    <option value="<?= htmlspecialchars($availableStatus) ?>">
                                        <?= htmlspecialchars(ucfirst($availableStatus)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="account-form-field account-form-field-full">
                            <label for="manage-password">New Password <span>(optional)</span></label>
                            <input id="manage-password" name="password" type="password"
                                   minlength="8" autocomplete="new-password">
                            <small>Leave blank to keep the current password.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button accounts-modal-primary">Save Changes</button>
                </div>

            </form>
        </div>
    </div>

</div>
</main>
        </div>
    </div>

<script>
/*
 * ACCOUNTS PAGE MODALS
 *
 * Bootstrap handles opening, closing, focus and the backdrop.
 * This script only copies the selected row's non-sensitive values into
 * the appropriate form.
 */
document.addEventListener('DOMContentLoaded', function () {

    const editModal = document.getElementById('edit-account-modal');
    const manageModal = document.getElementById('manage-account-modal');

    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            editModal.querySelector('#edit-account-id').value = button.dataset.accountId || '';
            editModal.querySelector('#edit-username').value = button.dataset.username || '';
            editModal.querySelector('#edit-email').value = button.dataset.email || '';
            editModal.querySelector('#edit-role').value = button.dataset.role || '';
        });
    }

    if (manageModal) {
        manageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            manageModal.querySelector('#manage-account-id').value = button.dataset.accountId || '';
            manageModal.querySelector('#manage-account-name').textContent = button.dataset.username || 'Account';
            manageModal.querySelector('#manage-status').value = button.dataset.status || 'active';
            manageModal.querySelector('#manage-password').value = '';
        });
    }

});
</script>

</body>
</html>
