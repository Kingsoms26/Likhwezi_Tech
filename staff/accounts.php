<?php
    // accounts.php is the admin accounts page
    // create staff accounts, change their role, email, status and password, and archive people who have left
    // names and photos are edited by each staff member on their own profile
    // the rules live in helpers/accountData.php

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/accountData.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    $editorID = (int) $account['accountID'];

    // handle the account forms before any html then go back to the page so a refresh does not repeat them
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);
        $target = $id ? getAccount($conn, $id) : null;
        $errors = [];

        if (!verifyCsrf()) {
            $errors[] = 'Your session expired. Please try again.';
        // add an account
        } elseif ($action === 'add') {
            $input = [
                'username'      => $_POST['username'] ?? '',
                'email'         => $_POST['email'] ?? '',
                'role'          => $_POST['role'] ?? '',
                'accountStatus' => $_POST['status'] ?? 'active',
                // made here, never typed by the admin
                'password'      => generateTemporaryPassword(),
            ];

            $errors = validateNewAccount($conn, $input);

            if (!$errors) {
                $newID = createAccount($conn, $input, $editorID);
                if ($newID) {
                    setFlash('success', "Account '{$input['username']}' was created.");
                    logActivity($conn, 'Account', 'created',
                        "Created {$input['role']} account '{$input['username']}' ({$input['email']}), status {$input['accountStatus']}", $newID);

                    // shown once on the next page load then removed
                    $_SESSION['newAccountCredentials'] = [
                        'username' => $input['username'],
                        'email'    => $input['email'],
                        'password' => $input['password'],
                        'loginURL' => staffLoginURL(),
                    ];
                } else {
                    $errors[] = 'The account could not be created. Please try again.';
                }
            }
        // change the email and role
        } elseif ($action === 'edit') {
            $input = [
                'email' => $_POST['email'] ?? '',
                'role'  => $_POST['role'] ?? '',
            ];

            if (!$target) {
                $errors[] = 'The selected account could not be found.';
            } else {
                $errors = validateAccountEdit($conn, $input, $target, $editorID);

                if (!$errors) {
                    if (updateAccountDetails($conn, $target, $input)) {
                        setFlash('success', "Account '{$target['username']}' was updated.");

                        // log what changed
                        $changes = [];
                        if ($input['email'] !== $target['email']) {
                            $changes[] = "email {$target['email']} → {$input['email']}";
                        }
                        if ($input['role'] !== $target['role']) {
                            $changes[] = 'role ' . ($target['role'] ?? 'none') . " → {$input['role']}";
                        }
                        if ($changes) {
                            logActivity($conn, 'Account', 'updated',
                                "Changed account '{$target['username']}': " . implode(', ', $changes), $id);
                        }
                    } else {
                        $errors[] = 'The account could not be saved. Please try again.';
                    }
                }
            }
        // change the status and password
        } elseif ($action === 'manage') {
            $input = [
                'accountStatus' => $_POST['status'] ?? '',
                'password'      => $_POST['password'] ?? '',
            ];

            if (!$target) {
                $errors[] = 'The selected account could not be found.';
            } else {
                $errors = validateAccountAccess($input, $target, $editorID);

                if (!$errors) {
                    if (updateAccountAccess($conn, $id, $input['accountStatus'], $input['password'])) {
                        $message = "Account '{$target['username']}' was updated.";
                        if ($input['password'] !== '') {
                            $message .= ' The password was changed.';
                        }
                        setFlash('success', $message);

                        // log what changed
                        $changes = [];
                        if ($input['accountStatus'] !== $target['accountStatus']) {
                            $changes[] = "status {$target['accountStatus']} → {$input['accountStatus']}";
                        }
                        if ($input['password'] !== '') {
                            $changes[] = 'reset the password';
                        }
                        if ($changes) {
                            logActivity($conn, 'Account', 'access',
                                "Changed account '{$target['username']}': " . implode(', ', $changes), $id);
                        }
                    } else {
                        $errors[] = 'The account could not be saved. Please try again.';
                    }
                }
            }
        // archive an account
        } elseif ($action === 'archive') {
            $blocker = $target ? accountArchiveBlocker($target, $editorID) : 'The selected account could not be found.';

            if ($blocker) {
                $errors[] = $blocker;
            } else {
                $released = archiveAccount($conn, $id, $editorID);

                if ($released === null) {
                    $errors[] = 'The account could not be archived. Please try again.';
                } else {
                    $message = "Account '{$target['username']}' was archived. Their records and activity are kept.";
                    $description = "Archived account '{$target['username']}'";
                    if ($released) {
                        $noun = count($released) === 1 ? '1 open enquiry' : count($released) . ' open enquiries';
                        $message .= " $noun went back to Unclaimed.";
                        $description .= " and released $noun back to Unclaimed (#" . implode(', #', $released) . ')';
                    }
                    setFlash('success', $message);
                    logActivity($conn, 'Account', 'archived', $description, $id);
                }
            }
        // restore an archived account
        } elseif ($action === 'restore') {
            if (!$target || !$target['isArchived']) {
                $errors[] = 'The selected account is not archived.';
            } elseif (restoreAccount($conn, $id)) {
                setFlash('success', "Account '{$target['username']}' was restored. It is deactivated: use Manage to reactivate it and set a new password.");
                logActivity($conn, 'Account', 'restored', "Restored archived account '{$target['username']}'", $id);
            } else {
                $errors[] = 'The account could not be restored. Please try again.';
            }
        } else {
            $errors[] = 'Unknown account action.';
        }

        if ($errors) {
            setFlash('error', implode(' ', $errors));
        }

        // go back to the same filters
        $returnQuery = $_GET;
        unset($returnQuery['page']);

        $returnUrl = 'accounts.php';
        if ($returnQuery) {
            $returnUrl .= '?' . http_build_query($returnQuery);
        }

        header('Location: ' . $returnUrl);
        exit;
    }

    // read the search and filters from the url
    $search = trim(mb_substr((string) ($_GET['search'] ?? ''), 0, 100));
    $role = $_GET['role'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $page = max(1, (int) ($_GET['page'] ?? 1));

    // anything unexpected falls back to all
    if ($role !== 'all' && !isset(ACCOUNT_ROLES[$role])) {
        $role = 'all';
    }

    if ($status !== 'all' && $status !== ACCOUNT_ARCHIVED_FILTER && !in_array($status, ACCOUNT_STATUSES, true)) {
        $status = 'all';
    }
    $isArchivedView = $status === ACCOUNT_ARCHIVED_FILTER;

    // fetch this page of accounts
    $result = getAccountList($conn, $search, $role, $status, $page);
    $visibleAccounts = $result['rows'];
    $page = $result['page'];
    $totalPages = $result['pages'];

    $accountRoles = array_keys(ACCOUNT_ROLES);
    $flash = takeFlash();

    // a new account's temporary password, shown on this load only and never stored as plain text
    $newCredentials = $_SESSION['newAccountCredentials'] ?? null;
    unset($_SESSION['newAccountCredentials']);

    if ($newCredentials) {
        // stop the browser keeping a copy of the page that shows the password
        header('Cache-Control: no-store');

        $newMessage = newAccountMessage($newCredentials);
        // email and whatsapp links with the login details, the @ is left as is since some email apps get it wrong
        $newMailto = 'mailto:' . str_replace('%40', '@', rawurlencode($newCredentials['email']))
            . '?subject=' . rawurlencode('Your Likhwezi staff account')
            . '&body=' . rawurlencode($newMessage);
        $newWhatsApp = 'https://wa.me/?text=' . rawurlencode($newMessage);
    }

    $pageTitle = 'Accounts';
    $activePage = 'accounts';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';

    // build a page link that keeps the current filters
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

    <!-- success and error messages -->
    <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

    <!-- active accounts card -->
    <section class="dashboard-cards accounts-summary">
        <?php
        $cardTitle = 'Active Accounts';
        $cardValue = getActiveAccountCount($conn);
        $cardMeta = 'Currently active employee accounts';
        $cardClass = 'active-accounts-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
    </section>

    <!-- employee accounts -->
    <section class="dashboard-panel">

        <div class="panel-header">
            <h2>Employee Accounts</h2>
            <button class="panel-button button-primary accounts-add-button" type="button" data-bs-toggle="modal" data-bs-target="#add-account-modal">
                + Add Account
            </button>
        </div>

        <div class="panel-body">

            <!-- filters, matching registrations.php so both pages feel the same -->
            <form class="registration-filters" method="get" action="accounts.php">

                <div class="filter-field filter-search">
                    <label for="account-search">Search</label>
                    <input type="search" id="account-search" name="search" maxlength="100" placeholder="Username or email" value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="filter-field">
                    <label for="account-role">Role</label>
                    <select id="account-role" name="role">
                        <option value="all">All roles</option>
                        <?php foreach ($accountRoles as $availableRole) : ?>
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
                        <?php foreach (ACCOUNT_STATUSES as $availableStatus) : ?>
                            <option value="<?= htmlspecialchars($availableStatus) ?>"
                                <?= $status === $availableStatus ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($availableStatus)) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="<?= ACCOUNT_ARCHIVED_FILTER ?>" <?= $isArchivedView ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <!-- outline so add account stays the one filled button -->
                    <button class="panel-button" type="submit">Apply filters</button>
                    <?php if ($search !== '' || $role !== 'all' || $status !== 'all') : ?>
                        <a class="panel-button" href="accounts.php">Clear</a>
                    <?php endif; ?>
                </div>

            </form>

            <!-- shown when nothing matches -->
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

                <!-- accounts table -->
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table registrations-table">
                        <thead>
                            <tr>
                                <th scope="col">Username</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Status</th>
                                <th scope="col"><?= $isArchivedView ? 'Date Archived' : 'Date Created' ?></th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($visibleAccounts as $row) : ?>
                                <?php
                                // an account with no staff role shows a dash, each role gets its own colour
                                $row['role'] = $row['role'] ?? '—';
                                $roleClass = 'role-customer-service';

                                if ($row['role'] === '—') {
                                    $roleClass = '';
                                } elseif ($row['role'] === 'Admin') {
                                    $roleClass = 'role-admin';
                                } elseif ($row['role'] === 'Marketing') {
                                    $roleClass = 'role-marketing';
                                }

                                $statusClass = 'status-' . ($row['isArchived'] ? 'archived' : $row['accountStatus']);
                                $statusLabel = $row['isArchived'] ? 'Archived' : ucfirst($row['accountStatus']);
                                $isSelf = (int) $row['accountID'] === $editorID;
                                ?>

                                <tr>
                                    <td class="registrant-name">
                                        <?= htmlspecialchars($row['username']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <span class="account-role <?= $roleClass ?>">
                                            <?= htmlspecialchars($row['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="account-status <?= $statusClass ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td class="registrant-date">
                                        <?= htmlspecialchars(substr($row['isArchived'] ? $row['archivedAt'] : $row['dateCreated'], 0, 10)) ?>
                                    </td>
                                    <!-- activity, edit, manage, archive and restore buttons -->
                                    <td class="table-actions">
                                        <a class="table-action" href="activityLog.php?account=<?= (int) $row['accountID'] ?>">
                                            Activity<span class="visually-hidden"> for <?= htmlspecialchars($row['username']) ?></span>
                                        </a>

                                        <?php if ($row['isArchived']) : ?>
                                            <button type="button" class="table-action" data-bs-toggle="modal" data-bs-target="#restore-account-modal" data-account-id="<?= (int) $row['accountID'] ?>" data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>">
                                                Restore<span class="visually-hidden"> <?= htmlspecialchars($row['username']) ?></span>
                                            </button>
                                        <?php else : ?>
                                            <button type="button" class="table-action" data-bs-toggle="modal" data-bs-target="#edit-account-modal" data-account-id="<?= (int) $row['accountID'] ?>" data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>" data-role="<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>">
                                                Edit<span class="visually-hidden"> <?= htmlspecialchars($row['username']) ?></span>
                                            </button>

                                            <button type="button" class="table-action" data-bs-toggle="modal" data-bs-target="#manage-account-modal" data-account-id="<?= (int) $row['accountID'] ?>" data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>" data-status="<?= htmlspecialchars($row['accountStatus'], ENT_QUOTES) ?>">
                                                Manage<span class="visually-hidden"> <?= htmlspecialchars($row['username']) ?></span>
                                            </button>

                                            <?php if (!$isSelf) : ?>
                                                <button type="button" class="table-action table-action-danger" data-bs-toggle="modal" data-bs-target="#archive-account-modal" data-account-id="<?= (int) $row['accountID'] ?>" data-username="<?= htmlspecialchars($row['username'], ENT_QUOTES) ?>">
                                                    Archive<span class="visually-hidden"> <?= htmlspecialchars($row['username']) ?></span>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- page links -->
                <?php if ($totalPages > 1) : ?>
                    <nav class="registration-pagination" aria-label="Account pages">
                        <?php if ($page > 1) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(accountPageUrl($page - 1, $search, $role, $status)) ?>">Previous</a>
                        <?php endif; ?>

                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) : ?>
                            <a class="panel-button <?= $pageNumber === $page ? 'active' : '' ?>" href="<?= htmlspecialchars(accountPageUrl($pageNumber, $search, $role, $status)) ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>>
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

    <!-- add account popup -->
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
                        <span class="account-modal-kicker">New account</span>
                        <p class="modal-help">Create an employee account. A temporary password is generated and shown once, so you can send it to the new user by email or WhatsApp.</p>
                    </div>

                    <?= csrfField() ?>
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
                                <?php foreach ($accountRoles as $availableRole) : ?>
                                    <option value="<?= htmlspecialchars($availableRole) ?>">
                                        <?= htmlspecialchars($availableRole) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="account-form-field">
                            <label for="add-status">Status</label>
                            <select id="add-status" name="status" required>
                                <?php foreach (ACCOUNT_STATUSES as $availableStatus) : ?>
                                    <option value="<?= htmlspecialchars($availableStatus) ?>"
                                        <?= $availableStatus === 'active' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($availableStatus)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- edit account popup -->
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

                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit-account-id">

                    <div class="account-form-grid">
                        <div class="account-form-field">
                            <label for="edit-username">Username</label>
                            <!-- usernames cannot change once created -->
                            <input id="edit-username" type="text" readonly>
                        </div>

                        <div class="account-form-field">
                            <label for="edit-email">Email</label>
                            <input id="edit-email" name="email" type="email" maxlength="150" required>
                        </div>

                        <div class="account-form-field account-form-field-full">
                            <label for="edit-role">Role</label>
                            <select id="edit-role" name="role" required>
                                <?php foreach ($accountRoles as $availableRole) : ?>
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

    <!-- manage account popup, status and password -->
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

                    <?= csrfField() ?>
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
                                <?php foreach (ACCOUNT_STATUSES as $availableStatus) : ?>
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

    <!-- archive account popup -->
    <div class="modal fade registration-modal account-modal" id="archive-account-modal"
         tabindex="-1" aria-labelledby="archive-account-title" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered account-modal-dialog">
            <form class="modal-content" method="post" action="accounts.php">

                <div class="modal-header">
                    <h2 class="modal-title" id="archive-account-title">Archive Account</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="account-modal-intro">
                        <span class="account-modal-kicker">Remove access</span>
                        <p class="modal-help">Archive this account when the person no longer works here. They will not be able to log in, and the account moves to the Archived filter.</p>
                    </div>

                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="id" id="archive-account-id">

                    <div class="account-managed-name">
                        <span>Account</span>
                        <strong id="archive-account-name">Account</strong>
                    </div>

                    <ul class="account-archive-notes">
                        <li>Events, campaigns, reports and closed enquiries they worked on stay linked to them.</li>
                        <li>Their full history stays in the Activity Log.</li>
                        <li>Enquiries they claimed but did not close go back to Unclaimed.</li>
                        <li>You can restore the account later.</li>
                    </ul>
                </div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button button-danger">Archive Account</button>
                </div>

            </form>
        </div>
    </div>

    <!-- restore account popup -->
    <div class="modal fade registration-modal account-modal" id="restore-account-modal"
         tabindex="-1" aria-labelledby="restore-account-title" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered account-modal-dialog">
            <form class="modal-content" method="post" action="accounts.php">

                <div class="modal-header">
                    <h2 class="modal-title" id="restore-account-title">Restore Account</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="account-modal-intro">
                        <span class="account-modal-kicker">Bring back</span>
                        <p class="modal-help">The account returns to the list as Deactivated. To let them log in again, use Manage to set it to Active and give them a new password.</p>
                    </div>

                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="restore">
                    <input type="hidden" name="id" id="restore-account-id">

                    <div class="account-managed-name">
                        <span>Account</span>
                        <strong id="restore-account-name">Account</strong>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button accounts-modal-primary">Restore Account</button>
                </div>

            </form>
        </div>
    </div>

    <?php if ($newCredentials) : ?>
        <!-- new account login details popup, opens straight after an account is created
             the password is only on this page load and is gone after a refresh -->
        <div class="modal fade registration-modal account-modal" id="new-credentials-modal"
             tabindex="-1" aria-labelledby="new-credentials-title" aria-hidden="true"
             data-bs-backdrop="static" data-bs-keyboard="false">

            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable account-modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h2 class="modal-title" id="new-credentials-title">Send Login Details</h2>
                    </div>

                    <div class="modal-body">
                        <div class="account-modal-intro">
                            <span class="account-modal-kicker">Shown once</span>
                            <p class="modal-help">Send these details to the new user now. The password cannot be shown again after you close this window. If it is lost, set a new one under Manage.</p>
                        </div>

                        <div class="account-form-grid">
                            <div class="account-form-field">
                                <label for="new-credentials-username">Username</label>
                                <input id="new-credentials-username" type="text" readonly
                                       value="<?= htmlspecialchars($newCredentials['username']) ?>">
                            </div>

                            <div class="account-form-field">
                                <label for="new-credentials-password">Temporary Password</label>
                                <input id="new-credentials-password" type="text" readonly autocomplete="off"
                                       value="<?= htmlspecialchars($newCredentials['password']) ?>">
                            </div>

                            <div class="account-form-field account-form-field-full">
                                <label for="new-credentials-message">Message</label>
                                <textarea id="new-credentials-message" rows="8" readonly><?= htmlspecialchars($newMessage) ?></textarea>
                                <small>Email opens your email app addressed to <?= htmlspecialchars($newCredentials['email']) ?>. WhatsApp lets you choose who to send it to.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="panel-button" id="new-credentials-copy">Copy</button>
                        <a class="panel-button" href="<?= htmlspecialchars($newMailto) ?>">Email</a>
                        <a class="panel-button" href="<?= htmlspecialchars($newWhatsApp) ?>" target="_blank" rel="noopener">WhatsApp</a>
                        <button type="button" class="panel-button accounts-modal-primary" data-bs-dismiss="modal">Done</button>
                    </div>

                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>

<script src="assets/js/accounts.js?v=<?= filemtime(__DIR__ . '/assets/js/accounts.js') ?>"></script>
</body>
</html>