<?php
    // archive.php is the admin archive page
    // contains every archived enquiry, registration, partner, campaign and event in one place
    // items can be restored or permanently deleted one at a time or several at once
    // archiving can be undone but deleting cannot, the rules live in helpers/archiveData.php

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/archiveData.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    // read the search and type filter from the url
    $type = $_GET['type'] ?? 'all';
    if ($type !== 'all' && !isset(ARCHIVE_TYPES[$type])) {
        $type = 'all';
    }
    $search = trim(mb_substr((string) ($_GET['search'] ?? ''), 0, 100));
    $page = max(1, (int) ($_GET['page'] ?? 1));

    // build a link that keeps the filters
    function archivePageUrl(array $overrides = []): string
    {
        global $type, $search, $page;

        $params = array_merge(['type' => $type, 'search' => $search, 'page' => $page], $overrides);
        $params = array_filter($params, fn ($value) => $value !== '' && $value !== 'all' && $value !== 1);

        return 'archive.php' . ($params ? '?' . http_build_query($params) : '');
    }

    // restore or delete the chosen items then go back to the page
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $items = parseArchiveItems((array) ($_POST['items'] ?? []));
        $errors = [];
        $done = 0;

        if (!verifyCsrf()) {
            $errors[] = 'Your session expired. Please try again.';
        } elseif (!in_array($action, ['restore', 'delete'], true)) {
            $errors[] = 'Unknown archive action.';
        } elseif (!$items) {
            $errors[] = 'Select at least one item.';
        } else {
            foreach ($items as [$itemType, $itemID]) {
                // get the name first since a delete removes it
                $itemName = getArchivedItemName($conn, $itemType, $itemID);
                $logName = in_array($itemType, ['enquiry', 'registration'], true) ? "#$itemID" : "\"$itemName\"";

                $error = $action === 'restore'
                    ? restoreArchivedItem($conn, $itemType, $itemID, (int) $account['accountID'])
                    : deleteArchivedItem($conn, $itemType, $itemID);

                if ($error === null) {
                    $done++;
                    $singular = strtolower(ARCHIVE_TYPES[$itemType]['singular']);
                    logActivity($conn, 'Archive', $action === 'restore' ? 'restored' : 'deleted',
                        ($action === 'restore' ? 'Restored ' : 'Permanently deleted ') . "$singular $logName", $itemID);
                } else {
                    $errors[] = $error;
                }
            }
        }

        // success or error message
        if ($done) {
            $noun = $done === 1 ? '1 item was' : "$done items were";
            $message = $action === 'restore' ? "$noun restored." : "$noun permanently deleted.";
            setFlash($errors ? 'error' : 'success', $message . ($errors ? ' ' . implode(' ', $errors) : ''));
        } elseif ($errors) {
            setFlash('error', implode(' ', $errors));
        }

        header('Location: ' . archivePageUrl());
        exit;
    }

    // fetch the counts and this page of archived items
    $counts = getArchiveCounts($conn);
    $result = getArchivedItems($conn, $type, $search, $page);
    $rows = $result['rows'];
    $page = $result['page'];
    $flash = takeFlash();

    $pageTitle = 'Archive';
    $activePage = 'archive';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="registrations-page archive-page">

    <!-- success and error messages -->
    <?php include __DIR__ . '/components/dashboardAlerts.php'; ?>

    <!-- archived items card -->
    <section class="dashboard-cards">
        <?php
        $cardTitle = 'Archived Items';
        $cardValue = $counts['all'];
        $cardMeta = 'Hidden from the website and dashboards';
        $cardClass = 'archived-items-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
    </section>

    <!-- archived items -->
    <section class="dashboard-panel">

        <div class="panel-header archive-panel-header">
            <h2>Archived Items</h2>

            <!-- type tabs with how many of each are archived -->
            <nav class="view-toggle archive-type-tabs" aria-label="Archived item type">
                <a href="<?= htmlspecialchars(archivePageUrl(['type' => 'all', 'page' => 1])) ?>"
                   class="<?= $type === 'all' ? 'active' : '' ?>" <?= $type === 'all' ? 'aria-current="page"' : '' ?>>
                    All <span class="view-count"><?= $counts['all'] ?></span>
                </a>
                <?php foreach (ARCHIVE_TYPES as $key => $config) : ?>
                    <a href="<?= htmlspecialchars(archivePageUrl(['type' => $key, 'page' => 1])) ?>"
                       class="<?= $type === $key ? 'active' : '' ?>" <?= $type === $key ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($config['label']) ?> <span class="view-count"><?= $counts[$key] ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="panel-body">

            <!-- search -->
            <form class="registration-filters" method="get" action="archive.php">
                <?php if ($type !== 'all') : ?>
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <?php endif; ?>

                <div class="filter-field filter-search">
                    <label for="archive-search">Search</label>
                    <input type="search" id="archive-search" name="search" maxlength="100"
                           placeholder="Name or details" value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="filter-actions">
                    <button class="panel-button" type="submit">Search</button>
                    <?php if ($search !== '') : ?>
                        <a class="panel-button" href="<?= htmlspecialchars(archivePageUrl(['search' => '', 'page' => 1])) ?>">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- shown when nothing matches -->
            <?php if (!$rows) : ?>

                <div class="empty-state">
                    <?php if ($search !== '') : ?>
                        <p>No archived items match your search.</p>
                        <a class="panel-button" href="<?= htmlspecialchars(archivePageUrl(['search' => '', 'page' => 1])) ?>">Clear search</a>
                    <?php else : ?>
                        <p>Nothing is archived<?= $type !== 'all' ? ' in ' . htmlspecialchars(strtolower(ARCHIVE_TYPES[$type]['label'])) : '' ?>. Archived items will appear here.</p>
                    <?php endif; ?>
                </div>

            <?php else : ?>

                <!-- shown when at least one item is ticked -->
                <div class="archive-bulk-bar" id="archive-bulk-bar" hidden>
                    <span id="archive-selected-count">0 selected</span>
                    <button type="button" class="panel-button js-archive-bulk" data-action="restore">Restore selected</button>
                    <button type="button" class="panel-button button-danger js-archive-bulk" data-action="delete">Delete selected</button>
                </div>

                <!-- archived items table -->
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table registrations-table archive-table">
                        <thead>
                            <tr>
                                <th scope="col" class="archive-select">
                                    <input type="checkbox" id="archive-select-all" aria-label="Select all on this page">
                                </th>
                                <th scope="col">Name</th>
                                <th scope="col">Type</th>
                                <th scope="col">Details</th>
                                <th scope="col">Archived</th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($rows as $row) : ?>
                                <?php
                                $itemKey = $row['type'] . ':' . $row['id'];
                                $typeLabel = ARCHIVE_TYPES[$row['type']]['singular'];

                                // campaigns with donations are kept, the server refuses the delete too
                                $deleteBlocked = $row['donations'] > 0;

                                // extra line for the delete confirmation
                                $deleteNote = $row['photos']
                                    ? ($row['photos'] === 1 ? 'Its 1 photo' : "Its {$row['photos']} photos") . ' will also be deleted.'
                                    : '';
                                ?>
                                <tr>
                                    <td class="archive-select">
                                        <input type="checkbox" class="js-archive-item" value="<?= htmlspecialchars($itemKey) ?>"
                                               data-name="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
                                               data-delete-blocked="<?= $deleteBlocked ? '1' : '0' ?>"
                                               aria-label="Select <?= htmlspecialchars($row['title'], ENT_QUOTES) ?>">
                                    </td>
                                    <td class="registrant-name"><?= htmlspecialchars($row['title']) ?></td>
                                    <td>
                                        <span class="archive-type archive-type-<?= htmlspecialchars($row['type']) ?>">
                                            <?= htmlspecialchars($typeLabel) ?>
                                        </span>
                                    </td>
                                    <td class="archive-detail">
                                        <?= htmlspecialchars($row['detail'] !== '' ? $row['detail'] : '—') ?>
                                        <?php if ($deleteBlocked) : ?>
                                            <span class="snapshot-sub">
                                                <?= $row['donations'] === 1 ? '1 donation' : $row['donations'] . ' donations' ?> &middot; cannot be deleted
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="registrant-date">
                                        <?php if ($row['archivedAt']) : ?>
                                            <?= htmlspecialchars(date('j M Y', strtotime($row['archivedAt']))) ?>
                                            <?php if ($row['archivedBy']) : ?>
                                                <span class="snapshot-sub">by <?= htmlspecialchars($row['archivedBy']) ?></span>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-actions">
                                        <button type="button" class="table-action js-archive-action"
                                                data-action="restore" data-item="<?= htmlspecialchars($itemKey) ?>"
                                                data-name="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>">
                                            Restore<span class="visually-hidden"> <?= htmlspecialchars($row['title']) ?></span>
                                        </button>
                                        <?php if (!$deleteBlocked) : ?>
                                            <button type="button" class="table-action table-action-danger js-archive-action"
                                                    data-action="delete" data-item="<?= htmlspecialchars($itemKey) ?>"
                                                    data-name="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
                                                    data-note="<?= htmlspecialchars($deleteNote, ENT_QUOTES) ?>">
                                                Delete<span class="visually-hidden"> <?= htmlspecialchars($row['title']) ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- page links -->
                <?php if ($result['pages'] > 1) : ?>
                    <nav class="registration-pagination" aria-label="Archive pages">
                        <?php if ($page > 1) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(archivePageUrl(['page' => $page - 1])) ?>">Previous</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $result['pages']; $p++) : ?>
                            <a class="panel-button <?= $p === $page ? 'active' : '' ?>"
                               href="<?= htmlspecialchars(archivePageUrl(['page' => $p])) ?>"
                               <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $result['pages']) : ?>
                            <a class="panel-button" href="<?= htmlspecialchars(archivePageUrl(['page' => $page + 1])) ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </section>

    <!-- confirmation popup used by the row buttons and the selected items bar
         the script fills in the wording and the items to send -->
    <div class="modal fade registration-modal registration-confirm-modal" id="archive-confirm"
         tabindex="-1" aria-labelledby="archive-confirm-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="<?= htmlspecialchars(archivePageUrl()) ?>">

                <div class="modal-header">
                    <h2 class="modal-title" id="archive-confirm-title"></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="confirm-message" id="archive-confirm-message"></p>
                    <p class="archive-confirm-warning" id="archive-confirm-warning" hidden>
                        This cannot be undone. The item and its history are removed for good.
                    </p>
                </div>

                <?= csrfField() ?>
                <input type="hidden" name="action" id="archive-confirm-action" value="">
                <div id="archive-confirm-items"></div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button" id="archive-confirm-submit"></button>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
// the row buttons and the selected items bar both open the same confirmation popup
document.addEventListener('DOMContentLoaded', function () {

    const modalElement = document.getElementById('archive-confirm');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const title = document.getElementById('archive-confirm-title');
    const message = document.getElementById('archive-confirm-message');
    const warning = document.getElementById('archive-confirm-warning');
    const actionInput = document.getElementById('archive-confirm-action');
    const itemsBox = document.getElementById('archive-confirm-items');
    const submit = document.getElementById('archive-confirm-submit');

    // fill in the confirmation popup for the chosen items and open it
    function openConfirm(action, items, note) {
        const isDelete = action === 'delete';
        const what = items.length === 1 ? '"' + items[0].name + '"' : items.length + ' items';

        title.textContent = isDelete ? 'Delete permanently?' : 'Restore from archive?';
        message.textContent = isDelete
            ? 'Permanently delete ' + what + '?' + (note ? ' ' + note : '')
            : 'Restore ' + what + '? It will show on its own page again.';
        warning.hidden = !isDelete;

        actionInput.value = action;
        itemsBox.replaceChildren(...items.map(function (item) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'items[]';
            input.value = item.key;
            return input;
        }));

        submit.textContent = isDelete ? 'Delete permanently' : 'Restore';
        submit.classList.toggle('button-danger', isDelete);
        submit.classList.toggle('button-primary', !isDelete);

        modal.show();
    }

    // restore and delete buttons on each row
    document.querySelectorAll('.js-archive-action').forEach(function (button) {
        button.addEventListener('click', function () {
            openConfirm(button.dataset.action, [{ key: button.dataset.item, name: button.dataset.name }], button.dataset.note);
        });
    });

    // ticking items

    const selectAll = document.getElementById('archive-select-all');
    const checkboxes = Array.from(document.querySelectorAll('.js-archive-item'));
    const bulkBar = document.getElementById('archive-bulk-bar');
    const selectedCount = document.getElementById('archive-selected-count');

    if (!selectAll) return;

    function selected() {
        return checkboxes.filter(function (box) { return box.checked; });
    }

    // show the selected items bar and keep the select all box in step
    function updateBulkBar() {
        const count = selected().length;
        bulkBar.hidden = count === 0;
        selectedCount.textContent = count + ' selected';
        selectAll.checked = count === checkboxes.length;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }

    // select all
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (box) { box.checked = selectAll.checked; });
        updateBulkBar();
    });

    checkboxes.forEach(function (box) { box.addEventListener('change', updateBulkBar); });

    // restore or delete the selected items
    document.querySelectorAll('.js-archive-bulk').forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.dataset.action;
            let boxes = selected();
            let note = '';

            // campaigns with donations are skipped when deleting, the server refuses them too
            if (action === 'delete') {
                const blocked = boxes.filter(function (box) { return box.dataset.deleteBlocked === '1'; });
                boxes = boxes.filter(function (box) { return box.dataset.deleteBlocked !== '1'; });
                if (blocked.length) {
                    note = blocked.length + (blocked.length === 1 ? ' campaign has' : ' campaigns have')
                        + ' donations and will be kept.';
                }
                if (!boxes.length) {
                    alert('The selected campaigns have donations, so they cannot be deleted.');
                    return;
                }
                note = (note ? note + ' ' : '') + 'Any photos belonging to these items will also be deleted.';
            }

            openConfirm(action, boxes.map(function (box) {
                return { key: box.value, name: box.dataset.name };
            }), note);
        });
    });

});
</script>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>