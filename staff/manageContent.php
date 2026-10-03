<?php
    // manageContent.php is the admin pages and site info page
    // contains five tabs, cym programmes, cym photos, partners, services and site details
    // each list has one add and edit dialog that opens by itself after a failed save or an edit link
    // the database work lives in helpers/contentData.php and helpers/partnerData.php
    // any staff save clears the public page cache so changes show straight away

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/contentData.php';
    require __DIR__ . '/helpers/partnerData.php';
    require_once __DIR__ . '/../includes/helpers/siteContent.php';

    // make sure the staff member is logged in and is an admin
    $account = requireStaff($conn);
    requireRole($account, 'Admin');

    // the tabs
    const CONTENT_TABS = [
        'programmes' => 'CYM programmes',
        'photos'     => 'CYM photos',
        'partners'   => 'Partners',
        'services'   => 'Services',
        'details'    => 'Site details',
    ];

    // which tab is open
    $tab = $_POST['tab'] ?? $_GET['tab'] ?? 'programmes';
    if (!isset(CONTENT_TABS[$tab])) {
        $tab = 'programmes';
    }
    $tabURL = 'manageContent.php?tab=' . $tab;

    // set when a save fails so the form reopens with what was typed
    $errors = [];
    $formInput = null;

    // handle the forms

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            setFlash('error', 'Your session expired. Please try again.');
            header('Location: ' . $tabURL);
            exit;
        }

        // which form was sent
        $action = $_POST['action'] ?? '';
        $id = ctype_digit($_POST['id'] ?? '') ? (int) $_POST['id'] : null;

        // move up or down, shared by every list
        $moveTables = ['programmes' => 'Programme', 'services' => 'Service', 'photos' => 'CymPhoto'];
        if ($action === 'move' && $id && isset($moveTables[$tab])) {
            if (!moveContentRow($conn, $moveTables[$tab], $id, $_POST['direction'] ?? '')) {
                setFlash('error', 'The order could not be changed. Please try again.');
            } else {
                logActivity($conn, 'Content', 'reordered', "Moved {$moveTables[$tab]} #$id " . ($_POST['direction'] ?? ''), $id);
            }
            header('Location: ' . $tabURL);
            exit;
        }

        // programmes
        if ($tab === 'programmes') {
            // show or hide
            if ($action === 'toggle' && $id) {
                $programme = getProgramme($conn, $id);
                if ($programme && toggleProgramme($conn, $id)) {
                    setFlash('success', 'Programme updated.');
                    logActivity($conn, 'Content', 'updated',
                        ($programme['isActive'] ? 'Hid' : 'Showed') . " programme \"{$programme['name']}\"", $id);
                } else {
                    setFlash('error', 'That programme could not be updated.');
                }
                header('Location: ' . $tabURL);
                exit;
            }

            // delete
            if ($action === 'delete' && $id) {
                $programme = getProgramme($conn, $id);
                if ($programme && deleteProgramme($conn, $id)) {
                    setFlash('success', '"' . $programme['name'] . '" deleted.');
                    logActivity($conn, 'Content', 'deleted', "Deleted programme \"{$programme['name']}\"", $id);
                } else {
                    setFlash('error', 'That programme could not be deleted.');
                }
                header('Location: ' . $tabURL);
                exit;
            }

            // add or rename
            if ($action === 'save') {
                $formInput = ['id' => $id, 'name' => trim($_POST['name'] ?? '')];
                $existing = $id ? getProgramme($conn, $id) : null;

                $errors = $id && !$existing
                    ? ['That programme no longer exists.']
                    : validateProgrammeName($conn, $formInput['name'], $id);

                if (!$errors) {
                    if ($existing ? renameProgramme($conn, $id, $formInput['name']) : addProgramme($conn, $formInput['name'])) {
                        setFlash('success', '"' . $formInput['name'] . '" ' . ($existing ? 'updated.' : 'added.'));
                        logActivity($conn, 'Content', $existing ? 'updated' : 'created', $existing
                            ? "Renamed programme \"{$existing['name']}\" to \"{$formInput['name']}\""
                            : "Added programme \"{$formInput['name']}\"", $id);
                        header('Location: ' . $tabURL);
                        exit;
                    }
                    $errors[] = 'Something went wrong saving the programme. Please try again.';
                }
            }
        }

        // services
        if ($tab === 'services') {
            // show or hide
            if ($action === 'toggle' && $id) {
                $service = getService($conn, $id);
                if ($service && toggleService($conn, $id)) {
                    setFlash('success', 'Service updated.');
                    logActivity($conn, 'Content', 'updated',
                        ($service['isActive'] ? 'Hid' : 'Showed') . " service \"{$service['name']}\"", $id);
                } else {
                    setFlash('error', 'That service could not be updated.');
                }
                header('Location: ' . $tabURL);
                exit;
            }

            // delete
            if ($action === 'delete' && $id) {
                $service = getService($conn, $id);
                if ($service && deleteService($conn, $id)) {
                    setFlash('success', '"' . $service['name'] . '" deleted.');
                    logActivity($conn, 'Content', 'deleted', "Deleted service \"{$service['name']}\"", $id);
                } else {
                    setFlash('error', 'That service could not be deleted.');
                }
                header('Location: ' . $tabURL);
                exit;
            }

            // add or edit
            if ($action === 'save') {
                $formInput = [
                    'id'          => $id,
                    'name'        => trim($_POST['name'] ?? ''),
                    'tagline'     => trim($_POST['tagline'] ?? ''),
                    'icon'        => $_POST['icon'] ?? '',
                    'description' => trim($_POST['description'] ?? ''),
                    'includes'    => cleanServiceIncludes($_POST['includes'] ?? ''),
                ];
                $existing = $id ? getService($conn, $id) : null;

                $errors = $id && !$existing
                    ? ['That service no longer exists.']
                    : validateServiceInput($conn, $formInput, $id);

                if (!$errors) {
                    if ($existing ? updateService($conn, $id, $formInput) : addService($conn, $formInput)) {
                        setFlash('success', '"' . $formInput['name'] . '" ' . ($existing ? 'updated.' : 'added.'));
                        logActivity($conn, 'Content', $existing ? 'updated' : 'created',
                            ($existing ? 'Updated' : 'Added') . " service \"{$formInput['name']}\"", $id);
                        header('Location: ' . $tabURL);
                        exit;
                    }
                    $errors[] = 'Something went wrong saving the service. Please try again.';
                }
            }
        }

        // cym photos
        if ($tab === 'photos') {
            // delete
            if ($action === 'delete' && $id) {
                $photo = getCymPhoto($conn, $id);
                if ($photo && deleteCymPhoto($conn, $id)) {
                    deleteCymPhotoFile($photo['src']);
                    setFlash('success', 'Photo deleted.');
                    logActivity($conn, 'Content', 'deleted', "Deleted CYM photo \"{$photo['altText']}\"", $id);
                } else {
                    setFlash('error', 'That photo could not be deleted.');
                }
                header('Location: ' . $tabURL);
                exit;
            }

            // add or edit
            if ($action === 'save') {
                $formInput = ['id' => $id, 'altText' => trim($_POST['altText'] ?? '')];
                $existing = $id ? getCymPhoto($conn, $id) : null;

                if ($id && !$existing) {
                    $errors[] = 'That photo no longer exists.';
                }
                if ($formInput['altText'] === '') {
                    $errors[] = 'Describe the photo for people using screen readers.';
                } elseif (mb_strlen($formInput['altText']) > 255) {
                    $errors[] = 'Photo description must be 255 characters or fewer.';
                }

                // only save the upload once the text fields are valid
                $src = null;
                if (!$errors) {
                    $upload = saveCymPhotoUpload($_FILES['photo'] ?? []);
                    $src = $upload['src'];

                    if ($upload['error']) {
                        $errors[] = $upload['error'];
                    } elseif (!$existing && !$src) {
                        $errors[] = 'Choose a photo to upload.';
                    }
                }

                if (!$errors) {
                    $saved = $existing
                        ? updateCymPhoto($conn, $id, $formInput['altText'], $src)
                        : addCymPhoto($conn, $src, $formInput['altText']);

                    if ($saved) {
                        if ($existing && $src) {
                            deleteCymPhotoFile($existing['src']);
                        }
                        setFlash('success', $existing ? 'Photo updated.' : 'Photo added.');
                        logActivity($conn, 'Content', $existing ? 'updated' : 'created',
                            ($existing ? 'Updated' : 'Added') . " CYM photo \"{$formInput['altText']}\"", $id);
                        header('Location: ' . $tabURL);
                        exit;
                    }

                    // the save failed so remove the upload
                    deleteCymPhotoFile($src);
                    $errors[] = 'Something went wrong saving the photo. Please try again.';
                }
            }
        }

        // partners, ordered by drag and drop so not part of move above
        if ($tab === 'partners') {
            $partnerID = ctype_digit($_POST['partnerID'] ?? '') ? (int) $_POST['partnerID'] : null;

            // archive or restore
            if ($action === 'toggleArchive' && $partnerID) {
                $result = togglePartnerArchive($conn, $partnerID, (int) $_SESSION['accountID']);

                if ($result) {
                    setFlash('success', 'Partner ' . $result . '.');
                    logActivity($conn, 'Partner', $result,
                        ucfirst($result) . ' partner "' . (getPartner($conn, $partnerID)['name'] ?? "#$partnerID") . '"', $partnerID);
                } else {
                    setFlash('error', 'That partner could not be updated.');
                }

                header('Location: ' . $tabURL);
                exit;
            }

            // save the new order
            if ($action === 'reorder') {
                $order = array_filter((array) ($_POST['order'] ?? []), 'ctype_digit');

                if ($order && savePartnerOrder($conn, $order)) {
                    setFlash('success', 'Partner order saved.');
                    logActivity($conn, 'Partner', 'reordered', 'Changed the order of partners');
                } else {
                    setFlash('error', 'The new order could not be saved. Please try again.');
                }

                header('Location: ' . $tabURL);
                exit;
            }

            // add or edit
            if ($action === 'save') {
                $formInput = [
                    'name'        => trim($_POST['name'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'websiteURL'  => trim($_POST['websiteURL'] ?? ''),
                ];

                $existing = $partnerID ? getPartner($conn, $partnerID) : null;
                if ($partnerID && !$existing) {
                    $errors[] = 'That partner no longer exists.';
                }

                $errors = array_merge($errors, validatePartnerInput($conn, $formInput, $partnerID));

                // only save the upload once the text fields are valid
                $logo = null;
                if (empty($errors)) {
                    $upload = savePartnerLogo($_FILES['logo'] ?? []);
                    $logo = $upload['filename'];

                    if ($upload['error']) {
                        $errors[] = $upload['error'];
                    } elseif (!$existing && !$logo) {
                        $errors[] = 'A logo image is required for a new partner.';
                    }
                }

                if (empty($errors)) {
                    if ($existing) {
                        $saved = updatePartner($conn, $partnerID, $formInput, $logo);
                        if ($saved && $logo) {
                            deletePartnerLogo($existing['logo']);
                        }
                    } else {
                        $newPartnerID = addPartner($conn, $formInput, $logo);
                        $saved = $newPartnerID !== null;
                    }

                    if ($saved) {
                        setFlash('success', '"' . $formInput['name'] . '" ' . ($existing ? 'updated.' : 'added.'));
                        logActivity($conn, 'Partner', $existing ? 'updated' : 'created',
                            ($existing ? 'Updated' : 'Added') . " partner \"{$formInput['name']}\"" . ($existing && $logo ? ' with a new logo' : ''),
                            $existing ? $partnerID : $newPartnerID);
                        header('Location: ' . $tabURL);
                        exit;
                    }

                    // the save failed so remove the upload
                    deletePartnerLogo($logo);
                    $errors[] = 'Something went wrong saving the partner. Please try again.';
                }
            }
        }

        // site details
        if ($tab === 'details' && $action === 'save') {
            $formInput = [];
            foreach (array_keys(SITE_SETTING_FIELDS) as $key) {
                $formInput[$key] = trim($_POST[$key] ?? '');
            }

            $errors = validateSiteSettings($formInput);

            if (!$errors) {
                if (saveSiteSettings($conn, $formInput)) {
                    setFlash('success', 'Site details saved.');
                    logActivity($conn, 'Content', 'updated', 'Saved site details (contact details and CYM page)');
                    header('Location: ' . $tabURL);
                    exit;
                }
                $errors[] = 'Something went wrong saving the site details. Please try again.';
            }
        }
    }

    // page data

    $flash = takeFlash();
    $editID = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? (int) $_GET['edit'] : null;

    // values for the open tab's dialog, what was typed after a failed save or the row being edited
    $dialogValues = null;

    // programmes tab
    if ($tab === 'programmes') {
        $programmes = getAllProgrammes($conn);
        $shownCount = count(array_filter($programmes, fn ($p) => (bool) $p['isActive']));
        if ($editID && ($row = getProgramme($conn, $editID))) {
            $dialogValues = ['id' => (int) $row['programmeID'], 'name' => $row['name']];
        }
    }

    // services tab
    if ($tab === 'services') {
        $services = getAllServices($conn);
        if ($editID && ($row = getService($conn, $editID))) {
            $dialogValues = ['id' => (int) $row['serviceID']] + $row;
        }
    }

    // photos tab
    if ($tab === 'photos') {
        $photos = getAllCymPhotos($conn);
        if ($editID && ($row = getCymPhoto($conn, $editID))) {
            $dialogValues = ['id' => (int) $row['photoID'], 'altText' => $row['altText'], 'src' => $row['src']];
        }
    }

    // partners tab
    if ($tab === 'partners') {
        $editingPartner = null;
        if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
            $editingPartner = getPartner($conn, (int) $_GET['edit']);
        }

        // after a failed save keep what was typed and stay in edit mode
        if ($formInput !== null && !empty($_POST['partnerID'])) {
            $editingPartner = $editingPartner ?? getPartner($conn, (int) $_POST['partnerID']);
        }
        $partnerForm = $formInput ?? $editingPartner ?? ['name' => '', 'description' => '', 'websiteURL' => ''];

        $partners = getAllPartners($conn);
        $counts = getPartnerCounts($partners);

        // open the dialog straight away for save errors or an edit link
        $openPartnerDialog = $errors || $editingPartner;
    }

    // site details tab, other tabs reopen their dialog after a failed save
    if ($tab === 'details') {
        $settings = $formInput ?? array_merge(DEFAULT_SITE_SETTINGS, getSiteSettings($conn));
    } elseif ($formInput !== null && $tab !== 'partners') {
        $dialogValues = $formInput;

        // keep showing the current photo when an edit fails
        if ($tab === 'photos' && !empty($formInput['id']) && ($row = getCymPhoto($conn, (int) $formInput['id']))) {
            $dialogValues['src'] = $row['src'];
        }
    }

    $openDialog = $dialogValues !== null;

    // up and down buttons for a row in one of the lists
    function moveButtons(string $tab, int $id, string $label, bool $isFirst, bool $isLast): void
    {
        foreach (['up' => $isFirst, 'down' => $isLast] as $direction => $disabled) : ?>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <input type="hidden" name="action" value="move">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="direction" value="<?= $direction ?>">
                <button type="submit" class="order-move" aria-label="Move <?= htmlspecialchars($label) ?> <?= $direction ?>" <?= $disabled ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="<?= $direction === 'up' ? 'm12 8-6 6 1.4 1.4 4.6-4.6 4.6 4.6L18 14z' : 'm12 16 6-6-1.4-1.4-4.6 4.6-4.6-4.6L6 10z' ?>"/>
                    </svg>
                </button>
            </form>
        <?php endforeach;
    }

    // a one button form for show, hide and delete
    function rowActionButton(string $tab, int $id, string $action, string $label, string $class = '', string $confirm = ''): void
    {
        ?>
        <form method="POST" <?= $confirm ? 'onsubmit="return confirm(' . htmlspecialchars(json_encode($confirm)) . ');"' : '' ?>>
            <?= csrfField() ?>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="panel-button <?= htmlspecialchars($class) ?>"><?= htmlspecialchars($label) ?></button>
        </form>
        <?php
    }

    // close icon for the dialogs
    $closeIcon = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/></svg>';

    $pageTitle = 'Pages & Site info';
    $activePage = 'content';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';
?>

<div class="admin-dashboard content-page">

    <!-- tabs -->
    <nav class="content-tabs" aria-label="Pages and site info sections">
        <?php foreach (CONTENT_TABS as $key => $label) : ?>
            <a class="content-tab <?= $tab === $key ? 'active' : '' ?>" href="manageContent.php?tab=<?= $key ?>"
               <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php
    // success messages, save errors show inside the dialog or above the site details form instead
    $pageErrors = $errors;
    $errors = [];
    include __DIR__ . '/components/dashboardAlerts.php';
    $errors = $pageErrors;
    ?>

    <!-- programmes tab -->
    <?php if ($tab === 'programmes') : ?>

        <section class="dashboard-panel">
            <div class="panel-header">
                <h2>CYM programmes</h2>
                <div class="panel-header-actions">
                    <button type="button" class="form-button" data-dialog-open="content-dialog" aria-haspopup="dialog">Add programme</button>
                </div>
            </div>

            <p class="panel-padding panel-intro">
                Shown programmes appear, in this order, in the Cyber Young Minds registration form, the scrolling banner and the
                "Programmes to participate in" count. Renaming or deleting a programme does not change registrations already made for it.
                <?php if ($programmes && !$shownCount) : ?>
                    <strong>Every programme is hidden, so the form is showing its built-in list.</strong>
                <?php endif; ?>
            </p>

            <?php if (!$programmes) : ?>
                <p class="panel-padding panel-empty">No programmes yet. Until one is added, the registration form shows its built-in list.</p>
            <?php else : ?>
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table content-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Programme</th>
                                <th>Status</th>
                                <th>Registrations</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programmes as $i => $programme) : ?>
                                <?php $programmeID = (int) $programme['programmeID']; ?>
                                <tr class="<?= $programme['isActive'] ? '' : 'is-hidden-row' ?>">
                                    <td><div class="content-order"><?php moveButtons($tab, $programmeID, $programme['name'], $i === 0, $i === count($programmes) - 1); ?></div></td>
                                    <td><strong><?= htmlspecialchars($programme['name']) ?></strong></td>
                                    <td>
                                        <span class="status-badge <?= $programme['isActive'] ? 'status-active' : 'status-archived' ?>">
                                            <?= $programme['isActive'] ? 'Shown' : 'Hidden' ?>
                                        </span>
                                    </td>
                                    <td><?= (int) $programme['registrations'] ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="panel-button" href="manageContent.php?tab=programmes&amp;edit=<?= $programmeID ?>" data-dialog-open="content-dialog"
                                               data-values="<?= htmlspecialchars(json_encode(['id' => $programmeID, 'name' => $programme['name']])) ?>" aria-haspopup="dialog">Edit</a>
                                            <?php rowActionButton($tab, $programmeID, 'toggle', $programme['isActive'] ? 'Hide' : 'Show'); ?>
                                            <?php rowActionButton($tab, $programmeID, 'delete', 'Delete', 'button-danger', 'Delete this programme? It will no longer appear on the registration form.'); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- add and edit programme dialog -->
        <dialog class="dashboard-dialog" id="content-dialog" aria-labelledby="content-dialog-title"
                data-add-title="Add a programme" data-edit-title="Edit programme" data-add-submit="Add programme"
                <?= $openDialog ? 'data-open-on-load="1"' : '' ?>>
            <form method="POST" action="manageContent.php" class="dashboard-form">
                <?= csrfField() ?>
                <input type="hidden" name="tab" value="programmes">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" data-field value="<?= htmlspecialchars((string) ($dialogValues['id'] ?? '')) ?>">

                <div class="dialog-header">
                    <h2 id="content-dialog-title"><?= !empty($dialogValues['id']) ? 'Edit programme' : 'Add a programme' ?></h2>
                    <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close"><?= $closeIcon ?></button>
                </div>

                <div class="dialog-body">
                    <div class="dialog-errors" <?= $errors ? '' : 'hidden' ?>>
                        <?php $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                    </div>

                    <div class="form-field">
                        <label for="content-name">Programme name</label>
                        <input type="text" id="content-name" name="name" data-field maxlength="255" required
                               value="<?= htmlspecialchars($dialogValues['name'] ?? '') ?>">
                    </div>
                </div>

                <div class="dialog-footer">
                    <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                    <button type="submit" class="form-button" data-dialog-submit><?= !empty($dialogValues['id']) ? 'Save changes' : 'Add programme' ?></button>
                </div>
            </form>
        </dialog>

    <!-- services tab -->
    <?php elseif ($tab === 'services') : ?>

        <section class="dashboard-panel">
            <div class="panel-header">
                <h2>Services</h2>
                <div class="panel-header-actions">
                    <button type="button" class="form-button" data-dialog-open="content-dialog" aria-haspopup="dialog">Add service</button>
                </div>
            </div>

            <p class="panel-padding panel-intro">
                Shown services appear as cards, in this order, on the Services page.
            </p>

            <?php if (!$services) : ?>
                <p class="panel-padding panel-empty">No services yet. Until one is added, the Services page shows its built-in cards.</p>
            <?php else : ?>
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table content-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Service</th>
                                <th>Includes</th>
                                <th>Status</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $i => $service) : ?>
                                <?php
                                $serviceID = (int) $service['serviceID'];
                                $includes = array_filter(explode("\n", $service['includes']));
                                ?>
                                <tr class="<?= $service['isActive'] ? '' : 'is-hidden-row' ?>">
                                    <td><div class="content-order"><?php moveButtons($tab, $serviceID, $service['name'], $i === 0, $i === count($services) - 1); ?></div></td>
                                    <td>
                                        <div class="content-service">
                                            <i class="bi <?= htmlspecialchars($service['icon']) ?> content-service-icon" aria-hidden="true"></i>
                                            <div>
                                                <strong><?= htmlspecialchars($service['name']) ?></strong>
                                                <div class="field-hint"><?= htmlspecialchars($service['tagline']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= count($includes) ?> item<?= count($includes) === 1 ? '' : 's' ?></td>
                                    <td>
                                        <span class="status-badge <?= $service['isActive'] ? 'status-active' : 'status-archived' ?>">
                                            <?= $service['isActive'] ? 'Shown' : 'Hidden' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="panel-button" href="manageContent.php?tab=services&amp;edit=<?= $serviceID ?>" data-dialog-open="content-dialog"
                                               data-values="<?= htmlspecialchars(json_encode(['id' => $serviceID] + $service)) ?>" aria-haspopup="dialog">Edit</a>
                                            <?php rowActionButton($tab, $serviceID, 'toggle', $service['isActive'] ? 'Hide' : 'Show'); ?>
                                            <?php rowActionButton($tab, $serviceID, 'delete', 'Delete', 'button-danger', 'Delete this service? Its card will be removed from the Services page.'); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- add and edit service dialog -->
        <dialog class="dashboard-dialog" id="content-dialog" aria-labelledby="content-dialog-title"
                data-add-title="Add a service" data-edit-title="Edit service" data-add-submit="Add service"
                <?= $openDialog ? 'data-open-on-load="1"' : '' ?>>
            <form method="POST" action="manageContent.php" class="dashboard-form">
                <?= csrfField() ?>
                <input type="hidden" name="tab" value="services">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" data-field value="<?= htmlspecialchars((string) ($dialogValues['id'] ?? '')) ?>">

                <div class="dialog-header">
                    <h2 id="content-dialog-title"><?= !empty($dialogValues['id']) ? 'Edit service' : 'Add a service' ?></h2>
                    <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close"><?= $closeIcon ?></button>
                </div>

                <div class="dialog-body">
                    <div class="dialog-errors" <?= $errors ? '' : 'hidden' ?>>
                        <?php $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                    </div>

                    <div class="form-field">
                        <label for="content-name">Service name</label>
                        <input type="text" id="content-name" name="name" data-field maxlength="255" required
                               value="<?= htmlspecialchars($dialogValues['name'] ?? '') ?>">
                    </div>

                    <div class="form-field">
                        <label for="content-tagline">Tagline <span class="field-hint">(small text above the name, e.g. "The Game Plan")</span></label>
                        <input type="text" id="content-tagline" name="tagline" data-field maxlength="255" required
                               value="<?= htmlspecialchars($dialogValues['tagline'] ?? '') ?>">
                    </div>

                    <div class="form-field">
                        <label for="content-icon">Icon</label>
                        <div class="content-icon-picker">
                            <i class="bi <?= htmlspecialchars($dialogValues['icon'] ?? array_key_first(SERVICE_ICONS)) ?> content-service-icon" id="content-icon-preview" aria-hidden="true"></i>
                            <select id="content-icon" name="icon" data-field data-default="<?= array_key_first(SERVICE_ICONS) ?>" required>
                                <?php foreach (SERVICE_ICONS as $icon => $iconLabel) : ?>
                                    <option value="<?= $icon ?>" <?= ($dialogValues['icon'] ?? '') === $icon ? 'selected' : '' ?>><?= htmlspecialchars($iconLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="content-description">Description</label>
                        <textarea id="content-description" name="description" data-field rows="3" required><?= htmlspecialchars($dialogValues['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-field">
                        <label for="content-includes">Includes <span class="field-hint">(one per line)</span></label>
                        <textarea id="content-includes" name="includes" data-field rows="5" required><?= htmlspecialchars($dialogValues['includes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="dialog-footer">
                    <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                    <button type="submit" class="form-button" data-dialog-submit><?= !empty($dialogValues['id']) ? 'Save changes' : 'Add service' ?></button>
                </div>
            </form>
        </dialog>

    <!-- cym photos tab -->
    <?php elseif ($tab === 'photos') : ?>

        <section class="dashboard-panel">
            <div class="panel-header">
                <h2>CYM photos</h2>
                <div class="panel-header-actions">
                    <button type="button" class="form-button" data-dialog-open="content-dialog" aria-haspopup="dialog">Add photo</button>
                </div>
            </div>

            <p class="panel-padding panel-intro">
                These photos rotate, in this order, in the carousel at the top of the Cyber Young Minds page.
            </p>

            <?php if (!$photos) : ?>
                <p class="panel-padding panel-empty">No photos yet. Until one is added, the CYM page shows its built-in photos.</p>
            <?php else : ?>
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table content-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Photo</th>
                                <th>Description</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($photos as $i => $photo) : ?>
                                <?php $photoID = (int) $photo['photoID']; ?>
                                <tr>
                                    <td><div class="content-order"><?php moveButtons($tab, $photoID, 'photo ' . ($i + 1), $i === 0, $i === count($photos) - 1); ?></div></td>
                                    <td><img class="content-photo-thumb" src="../<?= htmlspecialchars($photo['src']) ?>" alt=""></td>
                                    <td><?= htmlspecialchars($photo['altText']) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="panel-button" href="manageContent.php?tab=photos&amp;edit=<?= $photoID ?>" data-dialog-open="content-dialog"
                                               data-values="<?= htmlspecialchars(json_encode(['id' => $photoID, 'altText' => $photo['altText'], 'src' => $photo['src']])) ?>" aria-haspopup="dialog">Edit</a>
                                            <?php rowActionButton($tab, $photoID, 'delete', 'Delete', 'button-danger', 'Delete this photo? It will be removed from the CYM page.'); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- add and edit photo dialog -->
        <dialog class="dashboard-dialog" id="content-dialog" aria-labelledby="content-dialog-title"
                data-add-title="Add a photo" data-edit-title="Edit photo" data-add-submit="Add photo"
                <?= $openDialog ? 'data-open-on-load="1"' : '' ?>>
            <form method="POST" action="manageContent.php" enctype="multipart/form-data" class="dashboard-form">
                <?= csrfField() ?>
                <input type="hidden" name="tab" value="photos">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" data-field value="<?= htmlspecialchars((string) ($dialogValues['id'] ?? '')) ?>">

                <div class="dialog-header">
                    <h2 id="content-dialog-title"><?= !empty($dialogValues['id']) ? 'Edit photo' : 'Add a photo' ?></h2>
                    <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close"><?= $closeIcon ?></button>
                </div>

                <div class="dialog-body">
                    <div class="dialog-errors" <?= $errors ? '' : 'hidden' ?>>
                        <?php $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                    </div>

                    <div class="form-field" data-edit-only <?= !empty($dialogValues['id']) ? '' : 'hidden' ?>>
                        <span class="field-hint">Current photo</span>
                        <img class="content-photo-preview" data-current-photo alt=""
                             src="<?= !empty($dialogValues['src']) ? '../' . htmlspecialchars($dialogValues['src']) : '' ?>">
                    </div>

                    <?php
                    $dropzoneID = 'content-photo';
                    $dropzoneName = 'photo';
                    $dropzoneLabel = 'Photo';
                    $dropzoneHint = !empty($dialogValues['id']) ? '(leave empty to keep the current photo)' : '(required)';
                    $dropzoneMaxMB = 5;
                    include __DIR__ . '/components/photoDropzone.php';
                    ?>

                    <div class="form-field">
                        <label for="content-alt">Description <span class="field-hint">(read out by screen readers, e.g. "Learners building a robot")</span></label>
                        <input type="text" id="content-alt" name="altText" data-field maxlength="255" required
                               data-default="Cyber Young Minds school session"
                               value="<?= htmlspecialchars($dialogValues['altText'] ?? 'Cyber Young Minds school session') ?>">
                    </div>
                </div>

                <div class="dialog-footer">
                    <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                    <button type="submit" class="form-button" data-dialog-submit><?= !empty($dialogValues['id']) ? 'Save changes' : 'Add photo' ?></button>
                </div>
            </form>
        </dialog>

    <!-- partners tab -->
    <?php elseif ($tab === 'partners') : ?>

        <section class="dashboard-cards">
            <?php
            $cardTitle = 'Total Partners';
            $cardValue = $counts['total'];
            $cardMeta = 'Including archived';
            $cardClass = 'partners-total-card';
            include __DIR__ . '/components/dashboardCard.php';

            $cardTitle = 'Active';
            $cardValue = $counts['active'];
            $cardMeta = 'Shown on the public site';
            $cardClass = 'partners-active-card';
            include __DIR__ . '/components/dashboardCard.php';

            $cardTitle = 'Archived';
            $cardValue = $counts['archived'];
            $cardMeta = 'Hidden, can be restored';
            $cardClass = 'partners-archived-card';
            include __DIR__ . '/components/dashboardCard.php';
            ?>
        </section>

        <!-- every partner in display order, active ones can be reordered and archived ones sit at the bottom -->
        <section class="dashboard-panel partners-panel">
            <div class="panel-header">
                <h2>All partners</h2>

                <div class="panel-header-actions">
                    <!-- appears once the order has been changed -->
                    <div class="partner-order-bar" id="partner-order-bar" hidden>
                        <span class="partner-order-status">Order changed</span>
                        <button type="button" class="panel-button" id="partner-order-undo">Undo</button>
                        <button type="submit" form="partner-order-form" class="form-button">Save order</button>
                    </div>

                    <button type="button" class="form-button" id="partner-add" aria-haspopup="dialog" aria-controls="partner-dialog">
                        Add partner
                    </button>
                </div>
            </div>

            <?php if (!$partners) : ?>
                <p class="panel-padding panel-empty">No partners yet. Use "Add partner" to create the first one.</p>
            <?php else : ?>
                <?php if ($counts['active'] > 1) : ?>
                    <p class="panel-padding panel-intro partner-order-hint">
                        Drag a partner by its handle, or use the arrows, to change the order they appear on the website.
                    </p>
                <?php endif; ?>

                <div class="dashboard-table-wrap">
                    <table class="dashboard-table partners-table">
                        <thead>
                            <tr>
                                <th class="partner-order-heading">Order</th>
                                <th>Logo</th>
                                <th>Partner</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $position = 0; ?>
                            <?php foreach ($partners as $partner) : ?>
                                <?php
                                $isArchived = (bool) $partner['isArchived'];

                                // everything the dialog needs to fill itself in for editing
                                $partnerJson = json_encode([
                                    'id'          => (int) $partner['partnerID'],
                                    'name'        => $partner['name'],
                                    'description' => $partner['description'] ?? '',
                                    'websiteURL'  => $partner['websiteURL'] ?? '',
                                    'logo'        => PARTNER_LOGO_URL . $partner['logo'],
                                ]);
                                ?>
                                <tr class="<?= $isArchived ? 'is-archived' : 'partner-row' ?>"
                                    <?php if (!$isArchived) : ?>data-partner-id="<?= (int) $partner['partnerID'] ?>"<?php endif; ?>>
                                    <td class="partner-order-cell">
                                        <?php if ($isArchived) : ?>
                                            <span class="field-hint">&mdash;</span>
                                        <?php else : ?>
                                            <div class="partner-order-controls">
                                                <span class="drag-handle" title="Drag to reorder" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" width="18" height="18" focusable="false">
                                                        <path fill="currentColor" d="M9 5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM18 5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm-1.5 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM18 19a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z"/>
                                                    </svg>
                                                </span>
                                                <span class="partner-position"><?= ++$position ?></span>
                                                <span class="order-move-buttons">
                                                    <button type="button" class="order-move" data-move="up" aria-label="Move <?= htmlspecialchars($partner['name']) ?> up">
                                                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="m12 8-6 6 1.4 1.4 4.6-4.6 4.6 4.6L18 14z"/></svg>
                                                    </button>
                                                    <button type="button" class="order-move" data-move="down" aria-label="Move <?= htmlspecialchars($partner['name']) ?> down">
                                                        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="m12 16 6-6-1.4-1.4-4.6 4.6-4.6-4.6L6 10z"/></svg>
                                                    </button>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <img class="partner-logo-thumb" src="<?= htmlspecialchars(PARTNER_LOGO_URL . $partner['logo']) ?>" alt="">
                                    </td>
                                    <td>
                                        <div class="partner-name"><?= htmlspecialchars($partner['name']) ?></div>
                                        <?php if ($partner['websiteURL']) : ?>
                                            <a class="partner-link" href="<?= htmlspecialchars($partner['websiteURL']) ?>" target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars(parse_url($partner['websiteURL'], PHP_URL_HOST) ?: $partner['websiteURL']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $isArchived ? 'status-archived' : 'status-active' ?>">
                                            <?= $isArchived ? 'Archived' : 'Active' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('j M Y', strtotime($partner['dateAdd']))) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <!-- edit works as a normal link without javascript -->
                                            <a class="panel-button partner-edit" href="manageContent.php?tab=partners&amp;edit=<?= (int) $partner['partnerID'] ?>"
                                               data-partner="<?= htmlspecialchars($partnerJson) ?>" aria-haspopup="dialog">Edit</a>

                                            <form method="POST"
                                                  <?php if (!$isArchived) : ?>onsubmit="return confirm('Archive this partner? It will be hidden from the public site.');"<?php endif; ?>>
                                                <?= csrfField() ?>
                                                <input type="hidden" name="tab" value="partners">
                                                <input type="hidden" name="action" value="toggleArchive">
                                                <input type="hidden" name="partnerID" value="<?= (int) $partner['partnerID'] ?>">
                                                <button type="submit" class="panel-button <?= $isArchived ? '' : 'button-danger' ?>">
                                                    <?= $isArchived ? 'Restore' : 'Archive' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- sends the new order, the script adds each partner first to last -->
        <form method="POST" id="partner-order-form" hidden>
            <?= csrfField() ?>
            <input type="hidden" name="tab" value="partners">
            <input type="hidden" name="action" value="reorder">
        </form>

        <!-- add and edit partner dialog, filled in by the server after a failed save or by the script when add or edit is clicked -->
        <dialog class="dashboard-dialog partner-dialog" id="partner-dialog" aria-labelledby="partner-dialog-title"
                <?= $openPartnerDialog ? 'data-open-on-load="1"' : '' ?>>
            <form method="POST" action="manageContent.php" enctype="multipart/form-data" class="dashboard-form">
                <?= csrfField() ?>
                <input type="hidden" name="tab" value="partners">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="partnerID" id="partner-id" value="<?= $editingPartner ? (int) $editingPartner['partnerID'] : '' ?>">

                <div class="dialog-header">
                    <h2 id="partner-dialog-title"><?= $editingPartner ? 'Edit partner' : 'Add a partner' ?></h2>
                    <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/>
                        </svg>
                    </button>
                </div>

                <div class="dialog-body">
                    <div class="dialog-errors" <?= $errors ? '' : 'hidden' ?>>
                        <?php $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                    </div>

                    <div class="form-field">
                        <label for="partner-name">Partner name</label>
                        <input type="text" id="partner-name" name="name" maxlength="255" required
                               value="<?= htmlspecialchars($partnerForm['name'] ?? '') ?>">
                    </div>

                    <div class="form-field">
                        <label for="partner-description">Description <span class="field-hint">(optional)</span></label>
                        <textarea id="partner-description" name="description" rows="3"><?= htmlspecialchars($partnerForm['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-field">
                        <label for="partner-website">Website <span class="field-hint">(optional)</span></label>
                        <input type="url" id="partner-website" name="websiteURL" maxlength="255" placeholder="https://example.com"
                               value="<?= htmlspecialchars($partnerForm['websiteURL'] ?? '') ?>">
                    </div>

                    <div class="form-field" id="partner-current-logo" <?= $editingPartner ? '' : 'hidden' ?>>
                        <span class="field-hint">Current logo</span>
                        <img class="partner-logo-preview" alt=""
                             src="<?= $editingPartner ? htmlspecialchars(PARTNER_LOGO_URL . $editingPartner['logo']) : '' ?>">
                    </div>

                    <?php
                    $dropzoneID = 'partner-logo';
                    $dropzoneName = 'logo';
                    $dropzoneLabel = 'Logo';
                    $dropzoneHint = $editingPartner ? '(leave empty to keep the current logo)' : '(required)';
                    $dropzoneClass = 'logo-dropzone';
                    include __DIR__ . '/components/photoDropzone.php';
                    ?>
                </div>

                <div class="dialog-footer">
                    <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                    <button type="submit" class="form-button" id="partner-submit"><?= $editingPartner ? 'Save changes' : 'Add partner' ?></button>
                </div>
            </form>
        </dialog>

    <!-- site details tab -->
    <?php else : ?>

        <section class="dashboard-panel">
            <div class="panel-header">
                <h2>Site details</h2>
            </div>

            <form method="POST" action="manageContent.php" class="dashboard-form panel-padding content-details-form">
                <?= csrfField() ?>
                <input type="hidden" name="tab" value="details">
                <input type="hidden" name="action" value="save">

                <?php
                // errors for this form show here rather than at the top of the page
                $flash = null;
                include __DIR__ . '/components/dashboardAlerts.php';
                ?>

                <?php foreach (SITE_SETTING_FIELDS as $key => [$label, $type, $hint]) : ?>
                    <div class="form-field">
                        <label for="setting-<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                        <input type="<?= $type ?>" id="setting-<?= $key ?>" name="<?= $key ?>" maxlength="255" required
                               value="<?= htmlspecialchars($settings[$key] ?? '') ?>">
                        <p class="field-hint"><?= htmlspecialchars($hint) ?></p>
                    </div>
                <?php endforeach; ?>

                <div>
                    <button type="submit" class="form-button">Save details</button>
                </div>
            </form>
        </section>

    <?php endif; ?>

</div>

<script>
    // add and edit dialog for programmes, services and photos, add opens it empty and edit fills it in
    (() => {
        const dialog = document.getElementById('content-dialog');
        if (!dialog) return;

        // keep the icon preview in step with the chosen icon
        const iconSelect = document.getElementById('content-icon');
        const iconPreview = document.getElementById('content-icon-preview');
        const syncIcon = () => { if (iconSelect) iconPreview.className = 'bi ' + iconSelect.value + ' content-service-icon'; };
        iconSelect?.addEventListener('change', syncIcon);

        // fill in the dialog and open it
        const open = (values) => {
            const editing = Boolean(values.id);

            dialog.querySelector('.dialog-errors').hidden = true;
            dialog.querySelector('.photo-dropzone-remove')?.click();

            dialog.querySelectorAll('[data-field]').forEach((field) => {
                field.value = values[field.name] ?? field.dataset.default ?? '';
            });
            syncIcon();

            document.getElementById('content-dialog-title').textContent = editing ? dialog.dataset.editTitle : dialog.dataset.addTitle;
            dialog.querySelector('[data-dialog-submit]').textContent = editing ? 'Save changes' : dialog.dataset.addSubmit;

            dialog.querySelectorAll('[data-edit-only]').forEach((element) => { element.hidden = !editing; });
            const currentPhoto = dialog.querySelector('[data-current-photo]');
            if (currentPhoto) currentPhoto.src = values.src ? '../' + values.src : '';

            const photoHint = dialog.querySelector('.photo-dropzone')?.closest('.form-field').querySelector('label .field-hint');
            if (photoHint) photoHint.textContent = editing ? '(leave empty to keep the current photo)' : '(required)';

            dialog.showModal();
            dialog.querySelector('input[type="text"]')?.focus();
        };

        // add and edit buttons
        document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                open(trigger.dataset.values ? JSON.parse(trigger.dataset.values) : {});
            });
        });

        // close buttons
        dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        // clicking outside the dialog closes it
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });

        // remove edit from the address so a refresh does not reopen it
        dialog.addEventListener('close', () => {
            if (location.search.includes('edit=')) history.replaceState(null, '', <?= json_encode($tabURL) ?>);
        });

        // open straight away after a failed save or an edit link
        if (dialog.dataset.openOnLoad) dialog.showModal();
    })();
</script>

<?php if ($tab === 'partners') : ?>
    <script>
        // add and edit partner dialog
        (() => {
            const dialog = document.getElementById('partner-dialog');
            const field = (id) => document.getElementById(id);
            const logoHint = dialog.querySelector('.photo-dropzone').closest('.form-field').querySelector('label .field-hint');

            // fill in the dialog for a partner or a new one and open it
            const open = (partner) => {
                dialog.querySelector('.dialog-errors').hidden = true;
                dialog.querySelector('.photo-dropzone-remove').click();

                field('partner-id').value = partner ? partner.id : '';
                field('partner-name').value = partner ? partner.name : '';
                field('partner-description').value = partner ? partner.description : '';
                field('partner-website').value = partner ? partner.websiteURL : '';

                field('partner-dialog-title').textContent = partner ? 'Edit partner' : 'Add a partner';
                field('partner-submit').textContent = partner ? 'Save changes' : 'Add partner';
                logoHint.textContent = partner ? '(leave empty to keep the current logo)' : '(required)';

                field('partner-current-logo').hidden = !partner;
                if (partner) field('partner-current-logo').querySelector('img').src = partner.logo;

                dialog.showModal();
                field('partner-name').focus();
            };

            // add and edit buttons
            field('partner-add').addEventListener('click', () => open(null));

            document.querySelectorAll('.partner-edit').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    open(JSON.parse(link.dataset.partner));
                });
            });

            // close buttons
            dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
                button.addEventListener('click', () => dialog.close());
            });

            // clicking outside the dialog closes it
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });

            // remove edit from the address so a refresh does not reopen it
            dialog.addEventListener('close', () => {
                if (location.search.includes('edit=')) history.replaceState(null, '', <?= json_encode($tabURL) ?>);
            });

            // open straight away after a failed save or an edit link
            if (dialog.dataset.openOnLoad) dialog.showModal();
        })();

        // reorder partners by dragging the handle or using the arrows
        (() => {
            const tbody = document.querySelector('.partners-table tbody');
            const bar = document.getElementById('partner-order-bar');
            if (!tbody || !bar) return;

            const rows = () => [...tbody.querySelectorAll('tr[data-partner-id]')];
            const initialRows = rows();
            const orderKey = (list) => list.map((row) => row.dataset.partnerId).join();
            const initialKey = orderKey(initialRows);
            let saving = false;

            // renumber the rows and show the save bar when the order changed
            const refresh = () => {
                const list = rows();
                list.forEach((row, index) => {
                    row.querySelector('.partner-position').textContent = index + 1;
                    row.querySelector('[data-move="up"]').disabled = index === 0;
                    row.querySelector('[data-move="down"]').disabled = index === list.length - 1;
                });
                bar.hidden = orderKey(list) === initialKey;
            };

            // arrow buttons
            tbody.addEventListener('click', (event) => {
                const button = event.target.closest('.order-move');
                if (!button) return;

                const row = button.closest('tr');
                if (button.dataset.move === 'up' && row.previousElementSibling) {
                    row.previousElementSibling.before(row);
                }
                if (button.dataset.move === 'down' && row.nextElementSibling?.dataset.partnerId) {
                    row.nextElementSibling.after(row);
                }

                refresh();
                // keep focus on the row that moved even if this button is now disabled
                (button.disabled ? row.querySelector('.order-move:not(:disabled)') : button)?.focus();
            });

            // dragging only starts from the handle so text in the row can still be selected
            let dragging = null;

            tbody.addEventListener('pointerdown', (event) => {
                const handle = event.target.closest('.drag-handle');
                if (handle) handle.closest('tr').draggable = true;
            });

            // a click on the handle without dragging leaves the row as it was
            tbody.addEventListener('pointerup', () => {
                if (!dragging) rows().forEach((row) => { row.draggable = false; });
            });

            // pick up the row
            tbody.addEventListener('dragstart', (event) => {
                dragging = event.target.closest('tr[data-partner-id]');
                if (!dragging) return;
                dragging.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', dragging.dataset.partnerId);
            });

            // move the row above or below the one it is over
            tbody.addEventListener('dragover', (event) => {
                if (!dragging) return;
                event.preventDefault();

                const over = event.target.closest('tr[data-partner-id]');
                if (!over || over === dragging) return;

                const box = over.getBoundingClientRect();
                if (event.clientY > box.top + box.height / 2) {
                    over.after(dragging);
                } else {
                    over.before(dragging);
                }
            });

            tbody.addEventListener('drop', (event) => event.preventDefault());

            // drop the row
            tbody.addEventListener('dragend', () => {
                if (!dragging) return;
                dragging.classList.remove('is-dragging');
                dragging.draggable = false;
                dragging = null;
                refresh();
            });

            // undo puts the rows back in the order the page loaded with
            document.getElementById('partner-order-undo').addEventListener('click', () => {
                const firstArchived = tbody.querySelector('tr:not([data-partner-id])');
                initialRows.forEach((row) => tbody.insertBefore(row, firstArchived));
                refresh();
            });

            // send the new order
            document.getElementById('partner-order-form').addEventListener('submit', (event) => {
                const form = event.target;
                form.querySelectorAll('input[name="order[]"]').forEach((input) => input.remove());

                rows().forEach((row) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'order[]';
                    input.value = row.dataset.partnerId;
                    form.append(input);
                });

                saving = true;
            });

            // warn before leaving with an unsaved order
            window.addEventListener('beforeunload', (event) => {
                if (!saving && !bar.hidden) event.preventDefault();
            });

            refresh();
        })();
    </script>
<?php endif; ?>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>