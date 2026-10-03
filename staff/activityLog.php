<?php
    // activityLog.php is the admin activity log page
    // shows everything staff have done, filtered by user, category, date or text and downloadable as csv
    // archived accounts stay in the user filter so their history can always be found
    // the log is read only, rows are written by logActivity() in helpers/activityLog.php

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    // read the filters from the url
    $filters = readActivityFilters($_GET);

    // build a link that keeps the filters
    function activityPageUrl(array $filters, array $overrides = []): string
    {
        $params = array_merge($filters, $overrides);
        if ($params['page'] === 1) {
            unset($params['page']);
        }
        $params = array_filter($params, fn ($value) => $value !== '' && $value !== 0);

        return 'activityLog.php' . ($params ? '?' . http_build_query($params) : '');
    }

    // csv download

    if (($_GET['export'] ?? '') === 'csv') {
        $result = getActivityLogResult($conn, $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="activity-log-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        // so excel opens the file with the right characters
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date and time', 'Username', 'Name', 'Category', 'Action', 'Description', 'Record ID', 'IP address']);

        // stop spreadsheet apps treating a cell as a formula
        $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;

        while ($result && $row = $result->fetch_assoc()) {
            fputcsv($out, array_map($safe, [
                $row['createdAt'], $row['username'], $row['fullName'] ?? '', $row['category'],
                $row['action'], $row['description'], $row['targetID'], $row['ipAddress'],
            ]));
        }

        fclose($out);
        // record the download
        logActivity($conn, 'Account', 'export', 'Downloaded the activity log as CSV');
        exit;
    }

    // page data

    $result = getActivityLog($conn, $filters);
    $rows = $result['rows'];
    $filters['page'] = $result['page'];
    $totalPages = $result['pages'];

    // accounts for the user filter and the chosen user's summary
    $accounts = getActivityAccounts($conn);
    $selected = $filters['account'] ? getAccount($conn, $filters['account']) : null;
    $summary = $selected ? getAccountActivitySummary($conn, $filters['account']) : null;

    $hasFilters = $filters['account'] || $filters['category'] !== '' || $filters['search'] !== ''
        || $filters['from'] !== '' || $filters['to'] !== '';

    // page numbers to show, first, last and two either side of the current page
    $pageNumbers = array_unique(array_filter(
        [1, ...range(max(1, $filters['page'] - 2), min($totalPages, $filters['page'] + 2)), $totalPages],
        fn ($n) => $n >= 1 && $n <= $totalPages
    ));
    sort($pageNumbers);

    $flash = takeFlash();

    $pageTitle = $selected ? 'Activity: ' . accountDisplayName($selected) : 'Activity Log';
    $activePage = 'activity';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="registrations-page activity-page">

    <!-- success and error messages -->
    <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

    <!-- the chosen user's details -->
    <?php if ($selected) : ?>
        <section class="dashboard-panel activity-account">
            <div class="panel-body activity-account-body">
                <div class="activity-account-who">
                    <img src="<?= htmlspecialchars(profileImageSrc($selected['profileImageURL'])) ?>" alt="" class="activity-account-photo">
                    <div>
                        <h2><?= htmlspecialchars(accountDisplayName($selected)) ?></h2>
                        <p>
                            <?= htmlspecialchars($selected['username']) ?> · <?= htmlspecialchars($selected['email']) ?>
                        </p>
                    </div>
                </div>

                <dl class="activity-account-facts">
                    <div>
                        <dt>Role</dt>
                        <dd><?= htmlspecialchars($selected['role'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>
                            <?php if ($selected['isArchived']) : ?>
                                <span class="account-status status-archived">Archived <?= htmlspecialchars(substr($selected['archivedAt'], 0, 10)) ?></span>
                            <?php else : ?>
                                <span class="account-status status-<?= htmlspecialchars($selected['accountStatus']) ?>"><?= htmlspecialchars(ucfirst($selected['accountStatus'])) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd><?= htmlspecialchars(substr($selected['dateCreated'], 0, 10)) ?></dd>
                    </div>
                    <div>
                        <dt>Last login</dt>
                        <dd><?= $selected['lastLogin'] ? htmlspecialchars(substr($selected['lastLogin'], 0, 16)) : 'Never' ?></dd>
                    </div>
                    <div>
                        <dt>Logged actions</dt>
                        <dd><?= number_format($summary['total']) ?></dd>
                    </div>
                </dl>
            </div>
        </section>
    <?php endif; ?>

    <!-- activity log -->
    <section class="dashboard-panel">

        <div class="panel-header">
            <h2><?= $selected ? 'Their Activity' : 'All Activity' ?></h2>
            <a class="panel-button" href="<?= htmlspecialchars(activityPageUrl($filters, ['page' => 1, 'export' => 'csv'])) ?>">
                Download CSV
            </a>
        </div>

        <div class="panel-body">

            <!-- filters -->
            <form class="registration-filters" method="get" action="activityLog.php">

                <div class="filter-field filter-search">
                    <label for="activity-search">Search</label>
                    <input type="search" id="activity-search" name="search" maxlength="100" placeholder="What was done, or username" value="<?= htmlspecialchars($filters['search']) ?>">
                </div>

                <div class="filter-field">
                    <label for="activity-account">User</label>
                    <select id="activity-account" name="account">
                        <option value="">All users</option>
                        <?php foreach ($accounts as $option) : ?>
                            <option value="<?= (int) $option['accountID'] ?>"
                                <?= $filters['account'] === (int) $option['accountID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($option['username']) ?><?= $option['isArchived'] ? ' (archived)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="activity-category">Category</label>
                    <select id="activity-category" name="category">
                        <option value="">All categories</option>
                        <?php foreach (ACTIVITY_CATEGORIES as $value => $label) : ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $filters['category'] === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="activity-from">From</label>
                    <input type="date" id="activity-from" name="from" value="<?= htmlspecialchars($filters['from']) ?>">
                </div>

                <div class="filter-field">
                    <label for="activity-to">To</label>
                    <input type="date" id="activity-to" name="to" value="<?= htmlspecialchars($filters['to']) ?>">
                </div>

                <div class="filter-actions">
                    <button class="panel-button button-primary" type="submit">Apply filters</button>
                    <?php if ($hasFilters) : ?>
                        <a class="panel-button" href="activityLog.php">Clear</a>
                    <?php endif; ?>
                </div>

            </form>

            <!-- shown when nothing matches -->
            <?php if (!$rows) : ?>

                <div class="empty-state">
                    <?php if ($hasFilters) : ?>
                        <p>No activity matches these filters.</p>
                        <a class="panel-button" href="activityLog.php">Clear filters</a>
                    <?php else : ?>
                        <p>No activity has been logged yet.</p>
                    <?php endif; ?>
                </div>

            <?php else : ?>

                <p class="activity-count">
                    <?= number_format($result['total']) ?> <?= $result['total'] === 1 ? 'entry' : 'entries' ?>
                </p>

                <!-- log table, failed logins are highlighted -->
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table registrations-table activity-table">
                        <thead>
                            <tr>
                                <th scope="col">When</th>
                                <th scope="col">User</th>
                                <th scope="col">Category</th>
                                <th scope="col">What happened</th>
                                <th scope="col">IP address</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($rows as $row) : ?>
                                <tr class="<?= $row['action'] === 'failed' ? 'activity-row-failed' : '' ?>">
                                    <td class="registrant-date">
                                        <time datetime="<?= htmlspecialchars(str_replace(' ', 'T', $row['createdAt'])) ?>">
                                            <?= htmlspecialchars(date('j M Y, H:i', strtotime($row['createdAt']))) ?>
                                        </time>
                                    </td>
                                    <td class="registrant-name">
                                        <?php if ($row['accountID'] !== null) : ?>
                                            <a href="<?= htmlspecialchars(activityPageUrl($filters, ['account' => (int) $row['accountID'], 'page' => 1])) ?>">
                                                <?= htmlspecialchars($row['username']) ?>
                                            </a>
                                        <?php else : ?>
                                            <?= htmlspecialchars($row['username'] ?: '—') ?>
                                        <?php endif; ?>
                                        <?php if ($row['actorArchived']) : ?>
                                            <span class="activity-archived-tag">archived</span>
                                        <?php endif; ?>
                                        <?php if ($row['fullName']) : ?>
                                            <small><?= htmlspecialchars($row['fullName']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="activity-category activity-category-<?= htmlspecialchars(strtolower($row['category'])) ?>">
                                            <?= htmlspecialchars($row['category']) ?>
                                        </span>
                                    </td>
                                    <td class="activity-description"><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="activity-ip"><?= htmlspecialchars($row['ipAddress'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- page links -->
                <?php if ($totalPages > 1) : ?>
                    <nav class="registration-pagination" aria-label="Activity log pages">
                        <?php if ($filters['page'] > 1) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(activityPageUrl($filters, ['page' => $filters['page'] - 1])) ?>">Previous</a>
                        <?php endif; ?>

                        <?php $previous = 0; ?>
                        <?php foreach ($pageNumbers as $pageNumber) : ?>
                            <?php if ($pageNumber > $previous + 1) : ?>
                                <span class="activity-page-gap" aria-hidden="true">…</span>
                            <?php endif; ?>
                            <a class="panel-button <?= $pageNumber === $filters['page'] ? 'active' : '' ?>" href="<?= htmlspecialchars(activityPageUrl($filters, ['page' => $pageNumber])) ?>" <?= $pageNumber === $filters['page'] ? 'aria-current="page"' : '' ?>>
                                <?= $pageNumber ?>
                            </a>
                            <?php $previous = $pageNumber; ?>
                        <?php endforeach; ?>

                        <?php if ($filters['page'] < $totalPages) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(activityPageUrl($filters, ['page' => $filters['page'] + 1])) ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </section>

</div>
<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>