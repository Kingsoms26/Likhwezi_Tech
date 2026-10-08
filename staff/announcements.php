<?php
    // announcements.php is the admin page for pushing messages to the website and the staff portal
    // website ones show as a banner under the navbar on every public page
    // staff ones show above every staff page and in the notifications bell
    // the database work lives in helpers/announcementData.php

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/announcementData.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    $pageURL = 'announcements.php';

    // set when a save fails so the dialog reopens with what was typed
    $errors = [];
    $formInput = null;

    // handle the forms

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            setFlash('error', 'Your session expired. Please try again.');
            header('Location: ' . $pageURL);
            exit;
        }

        // which form was sent
        $action = $_POST['action'] ?? '';
        $id = ctype_digit($_POST['id'] ?? '') ? (int) $_POST['id'] : null;
        $existing = $id ? getAnnouncement($conn, $id) : null;

        // show or hide
        if ($action === 'toggle' && $id) {
            if ($existing && toggleAnnouncement($conn, $id)) {
                setFlash('success', 'Announcement updated.');
                logActivity($conn, 'Announcement', 'updated',
                    ($existing['isActive'] ? 'Hid' : 'Showed') . " announcement \"{$existing['title']}\"", $id);
            } else {
                setFlash('error', 'That announcement could not be updated.');
            }
            header('Location: ' . $pageURL);
            exit;
        }

        // delete
        if ($action === 'delete' && $id) {
            if ($existing && deleteAnnouncement($conn, $id)) {
                setFlash('success', '"' . $existing['title'] . '" deleted.');
                logActivity($conn, 'Announcement', 'deleted', "Deleted announcement \"{$existing['title']}\"", $id);
            } else {
                setFlash('error', 'That announcement could not be deleted.');
            }
            header('Location: ' . $pageURL);
            exit;
        }

        // add or edit
        if ($action === 'save') {
            $formInput = ['id' => $id] + readAnnouncementInput($_POST);

            $errors = $id && !$existing
                ? ['That announcement no longer exists.']
                : validateAnnouncementInput($formInput);

            if (!$errors) {
                $savedID = $existing
                    ? (updateAnnouncement($conn, $id, $formInput) ? $id : null)
                    : addAnnouncement($conn, $formInput, (int) $account['accountID']);

                if ($savedID) {
                    setFlash('success', '"' . $formInput['title'] . '" ' . ($existing ? 'updated.' : 'posted.'));
                    logActivity($conn, 'Announcement', $existing ? 'updated' : 'created',
                        ($existing ? 'Updated' : 'Posted') . " announcement \"{$formInput['title']}\" ("
                        . strtolower(ANNOUNCEMENT_AUDIENCES[$formInput['audience']]) . ')', $savedID);
                    header('Location: ' . $pageURL);
                    exit;
                }
                $errors[] = 'Something went wrong saving the announcement. Please try again.';
            }
        }
    }

    // page data

    $flash = takeFlash();
    $announcements = getAllAnnouncements($conn);

    // values for the dialog, what was typed after a failed save or the announcement being edited
    $dialogValues = $formInput;
    if ($dialogValues === null && isset($_GET['edit']) && ctype_digit($_GET['edit'])
        && ($row = getAnnouncement($conn, (int) $_GET['edit']))) {
        $dialogValues = ['id' => (int) $row['announcementID']] + $row;
    }
    $openDialog = $dialogValues !== null;

    // a one button form for show, hide and delete
    function announcementButton(int $id, string $action, string $label, string $class = '', string $confirm = ''): void
    {
        ?>
        <form method="POST" <?= $confirm ? 'data-confirm="' . htmlspecialchars($confirm) . '"' : '' ?>>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="panel-button <?= htmlspecialchars($class) ?>"><?= htmlspecialchars($label) ?></button>
        </form>
        <?php
    }

    // close icon for the dialog
    $closeIcon = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/></svg>';

    $pageTitle = 'Announcements';
    $activePage = 'announcements';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="admin-dashboard content-page announcements-page">

    <?php
    // success messages, save errors show inside the dialog instead
    $pageErrors = $errors;
    $errors = [];
    include __DIR__ . '/components/dashboardAlerts.php';
    $errors = $pageErrors;
    ?>

    <section class="dashboard-panel">
        <div class="panel-header">
            <h2>Announcements</h2>
            <div class="panel-header-actions">
                <button type="button" class="form-button" data-dialog-open="content-dialog" aria-haspopup="dialog">New announcement</button>
            </div>
        </div>

        <p class="panel-padding panel-intro">
            Website announcements show as a banner under the menu on every page of the public site, for example an upcoming event.
            Staff announcements show at the top of every staff page and in the notifications bell.
            Visitors and staff can close a banner, it shows again if the announcement is edited.
        </p>

        <?php if (!$announcements) : ?>
            <p class="panel-padding panel-empty">No announcements yet.</p>
        <?php else : ?>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table announcements-table">
                    <thead>
                        <tr>
                            <th>Announcement</th>
                            <th>Shows on</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements as $announcement) : ?>
                            <?php
                            $announcementID = (int) $announcement['announcementID'];
                            [$statusLabel, $statusClass] = announcementStatus($announcement);
                            $editValues = [
                                'id'        => $announcementID,
                                'title'     => $announcement['title'],
                                'message'   => $announcement['message'],
                                'audience'  => $announcement['audience'],
                                'linkURL'   => $announcement['linkURL'],
                                'startDate' => $announcement['startDate'],
                                'endDate'   => $announcement['endDate'],
                            ];
                            ?>
                            <tr class="<?= $statusLabel === 'Showing' ? '' : 'is-hidden-row' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                    <div class="field-hint announcement-preview"><?= htmlspecialchars($announcement['message']) ?></div>
                                    <div class="field-hint">
                                        Posted <?= htmlspecialchars(date('j M Y', strtotime($announcement['dateCreated']))) ?>
                                        <?php if ($announcement['username']) : ?>
                                            by <?= htmlspecialchars(accountDisplayName($announcement)) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars(ANNOUNCEMENT_AUDIENCES[$announcement['audience']] ?? $announcement['audience']) ?></td>
                                <td><?= htmlspecialchars(announcementDates($announcement)) ?></td>
                                <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="panel-button" href="announcements.php?edit=<?= $announcementID ?>" data-dialog-open="content-dialog"
                                           data-values="<?= htmlspecialchars(json_encode($editValues)) ?>" aria-haspopup="dialog">Edit</a>
                                        <?php announcementButton($announcementID, 'toggle', $announcement['isActive'] ? 'Hide' : 'Show'); ?>
                                        <?php announcementButton($announcementID, 'delete', 'Delete', 'button-danger', 'Delete this announcement? It will stop showing straight away.'); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- add and edit announcement dialog, run by manageContent.js which fills in every data-field -->
    <dialog class="dashboard-dialog" id="content-dialog" aria-labelledby="content-dialog-title" data-tab-url="<?= $pageURL ?>"
            data-add-title="New announcement" data-edit-title="Edit announcement" data-add-submit="Post announcement"
            <?= $openDialog ? 'data-open-on-load="1"' : '' ?>>
        <form method="POST" action="announcements.php" class="dashboard-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" data-field value="<?= htmlspecialchars((string) ($dialogValues['id'] ?? '')) ?>">

            <div class="dialog-header">
                <h2 id="content-dialog-title"><?= !empty($dialogValues['id']) ? 'Edit announcement' : 'New announcement' ?></h2>
                <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close"><?= $closeIcon ?></button>
            </div>

            <div class="dialog-body">
                <div class="dialog-errors" <?= $errors ? '' : 'hidden' ?>>
                    <?php $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                </div>

                <div class="form-field">
                    <label for="announcement-title">Title</label>
                    <input type="text" id="announcement-title" name="title" data-field maxlength="<?= ANNOUNCEMENT_TITLE_MAX ?>" required
                           value="<?= htmlspecialchars($dialogValues['title'] ?? '') ?>">
                </div>

                <div class="form-field">
                    <label for="announcement-message">Message</label>
                    <textarea id="announcement-message" name="message" data-field rows="4" maxlength="<?= ANNOUNCEMENT_MESSAGE_MAX ?>" required><?= htmlspecialchars($dialogValues['message'] ?? '') ?></textarea>
                </div>

                <div class="form-field">
                    <label for="announcement-audience">Show on</label>
                    <select id="announcement-audience" name="audience" data-field data-default="everyone" required>
                        <?php foreach (ANNOUNCEMENT_AUDIENCES as $audience => $audienceLabel) : ?>
                            <option value="<?= $audience ?>" <?= ($dialogValues['audience'] ?? 'everyone') === $audience ? 'selected' : '' ?>><?= htmlspecialchars($audienceLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="announcement-link">Link <span class="field-hint">(optional, adds a "Find out more" link)</span></label>
                    <input type="url" id="announcement-link" name="linkURL" data-field maxlength="255" placeholder="https://example.com"
                           value="<?= htmlspecialchars($dialogValues['linkURL'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="announcement-start">First day <span class="field-hint">(optional)</span></label>
                        <input type="date" id="announcement-start" name="startDate" data-field
                               value="<?= htmlspecialchars($dialogValues['startDate'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="announcement-end">Last day <span class="field-hint">(optional)</span></label>
                        <input type="date" id="announcement-end" name="endDate" data-field
                               value="<?= htmlspecialchars($dialogValues['endDate'] ?? '') ?>">
                    </div>
                </div>
                <p class="field-hint">Leave the days empty to show it from now until you hide or delete it.</p>
            </div>

            <div class="dialog-footer">
                <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                <button type="submit" class="form-button" data-dialog-submit><?= !empty($dialogValues['id']) ? 'Save changes' : 'Post announcement' ?></button>
            </div>
        </form>
    </dialog>

</div>

<script src="assets/js/manageContent.js?v=<?= filemtime(__DIR__ . '/assets/js/manageContent.js') ?>"></script>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>
