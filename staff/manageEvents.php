<?php
    // manageEvents.php is the staff gallery and events page
    // contains the summary cards and the upcoming, past and archived events
    // upcoming and past match the public site, an event with no date is either to be confirmed or past
    // add and edit share one dialog with the details, current photos and new photos all saved together
    // the database work lives in helpers/eventData.php and helpers/galleryData.php

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/eventData.php';
    require __DIR__ . '/helpers/galleryData.php';

    // admin and marketing can both add, edit and archive events
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Marketing']);

    // show archived events when asked
    $showArchived = isset($_GET['showArchived']);
    $returnURL = 'manageEvents.php' . ($showArchived ? '?showArchived=1' : '');

    // old links to an event's photos page now open its edit dialog
    if (isset($_GET['event']) && ctype_digit($_GET['event'])) {
        header('Location: manageEvents.php?edit=' . (int) $_GET['event']);
        exit;
    }

    $errors = [];
    $formInput = null;

    // handle the forms

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // photos too big to upload together arrive empty
        if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > galleryPostLimitBytes()) {
            setFlash('error', 'Those photos are too large to upload together. Please upload fewer at a time.');
            header('Location: ' . $returnURL);
            exit;
        }

        if (!verifyCsrf()) {
            setFlash('error', 'Your session expired. Please try again.');
            header('Location: ' . $returnURL);
            exit;
        }

        // which form was sent
        $action = $_POST['action'] ?? '';
        $eventID = ctype_digit($_POST['eventID'] ?? '') ? (int) $_POST['eventID'] : null;

        // archive or restore
        if ($action === 'toggleArchive' && $eventID) {
            $result = toggleEventArchive($conn, $eventID, (int) $account['accountID']);

            if ($result) {
                setFlash('success', 'Event ' . $result . '.');
                logActivity($conn, 'Event', $result,
                    ucfirst($result) . ' event "' . (getEvent($conn, $eventID)['name'] ?? "#$eventID") . '"', $eventID);
            } else {
                setFlash('error', 'That event could not be updated.');
            }

            header('Location: ' . $returnURL);
            exit;
        }

        // add or edit
        if ($action === 'save') {
            $formInput = [
                'name'        => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'eventDate'   => $_POST['eventDate'] ?? '',
                'undatedStatus' => $_POST['undatedStatus'] ?? 'past',
            ];

            $existing = $eventID ? getEvent($conn, $eventID) : null;
            if ($eventID && !$existing) {
                $errors[] = 'That event no longer exists.';
            }

            $errors = array_merge($errors, validateEventInput($formInput));
            $photos = normaliseUploadedFiles($_FILES['photos'] ?? []);
            $removePhotoIDs = array_map('intval', array_filter((array) ($_POST['removePhotos'] ?? []), 'ctype_digit'));

            if (!$errors) {
                $newID = $existing ? null : addEvent($conn, $formInput, (int) $account['accountID']);
                $saved = $existing ? updateEvent($conn, $eventID, $formInput) : $newID !== null;

                if ($saved) {
                    logActivity($conn, 'Event', $existing ? 'updated' : 'created',
                        ($existing ? 'Updated' : 'Created') . " event \"{$formInput['name']}\"", $newID ?? $eventID);

                    // remove the marked photos first so a new first photo can become the cover
                    $removed = 0;
                    foreach ($existing ? $removePhotoIDs : [] as $galleryItemID) {
                        $item = getGalleryItem($conn, $galleryItemID);
                        if ($item && (int) $item['eventID'] === $eventID && deleteGalleryPhoto($conn, $galleryItemID)) {
                            $removed++;
                        }
                    }
                    $removedText = $removed === 1 ? '1 photo' : $removed . ' photos';
                    if ($removed) {
                        logActivity($conn, 'Event', 'photos removed', "Removed $removedText from event \"{$formInput['name']}\"", $eventID);
                    }

                    // add the new photos once the event is saved
                    $photoResult = $photos ? addGalleryPhotos($conn, $newID ?? $eventID, null, $photos) : null;
                    if ($photoResult && $photoResult['added']) {
                        $addedText = $photoResult['added'] === 1 ? '1 photo' : $photoResult['added'] . ' photos';
                        logActivity($conn, 'Event', 'photos', "Uploaded $addedText to event \"{$formInput['name']}\"", $newID ?? $eventID);
                    }

                    [$flashType, $flashMessage] = savedWithPhotosMessage(
                        '"' . $formInput['name'] . '" ' . ($existing ? 'updated' : 'added'),
                        $photoResult
                    );
                    setFlash($flashType, $flashMessage . ($removed ? ' Removed ' . $removedText . '.' : ''));
                    header('Location: ' . $returnURL);
                    exit;
                }

                $errors[] = 'Something went wrong saving the event. Please try again.';
            }

            // browsers cannot keep chosen files after the page reloads
            if ($errors && $photos) {
                $errors[] = 'Please choose your photos again.';
            }
        }
    }

    // page data

    $flash = takeFlash();

    // the event being edited
    $editingEvent = null;
    if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
        $editingEvent = getEvent($conn, (int) $_GET['edit']);
    }

    // after a failed save keep what was typed and stay in edit mode
    if ($formInput !== null && !empty($_POST['eventID'])) {
        $editingEvent = $editingEvent ?? getEvent($conn, (int) $_POST['eventID']);
    }
    $form = $formInput ?? $editingEvent ?? ['name' => '', 'description' => '', 'eventDate' => '', 'dateToBeConfirmed' => false];

    // fetch the events and counts
    $events = getAllEvents($conn);
    $counts = getEventCounts($events);

    // photos grouped by event, oldest first so the cover comes first
    $photosByEvent = [];
    foreach (getEventPhotos($conn) as $photo) {
        $photosByEvent[(int) $photo['eventID']][] = $photo;
    }
    $totalPhotos = array_sum(array_map('count', $photosByEvent));
    $needPhotos = count(array_filter($events, fn ($e) => !$e['isArchived'] && $e['timing'] === 'past' && !$e['photoCount']));

    // upcoming soonest first and to be confirmed last, past most recent first
    $upcomingEvents = array_values(array_filter($events, fn ($e) => !$e['isArchived'] && $e['timing'] === 'upcoming'));
    usort($upcomingEvents, fn ($a, $b) => ($a['daysAway'] === null) <=> ($b['daysAway'] === null)
        ?: $a['daysAway'] <=> $b['daysAway']);
    $pastEvents = array_values(array_filter($events, fn ($e) => !$e['isArchived'] && $e['timing'] === 'past'));
    $archivedEvents = array_values(array_filter($events, fn ($e) => $e['isArchived']));

    // photos marked for removal before a failed save stay marked
    $markedForRemoval = array_map('intval', (array) ($_POST['removePhotos'] ?? []));
    $editingPhotos = $editingEvent ? ($photosByEvent[(int) $editingEvent['eventID']] ?? []) : [];

    $openDialog = $errors || $editingEvent;

    $pageTitle = 'Gallery & Events';
    $activePage = 'events';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';

    // the date to show on a card
    $eventDateLabel = fn (array $event) => $event['eventDate']
        ? date('l, j F Y', strtotime($event['eventDate']))
        : ($event['dateToBeConfirmed'] ? 'Date to be confirmed' : 'No date');

    // everything the edit dialog needs to fill itself in
    $eventJson = fn (array $event) => json_encode([
        'id'          => $event['eventID'],
        'name'        => $event['name'],
        'description' => $event['description'] ?? '',
        'eventDate'   => $event['eventDate'] ?? '',
        'tbc'         => $event['dateToBeConfirmed'],
        'photos'      => array_map(fn ($photo) => [
            'id'  => (int) $photo['galleryItemID'],
            'src' => galleryImageSrc($photo['image']),
        ], $photosByEvent[(int) $event['eventID']] ?? []),
    ], JSON_INVALID_UTF8_SUBSTITUTE);

    // one current photo in the dialog, ticking remove deletes it when the form is saved
    $renderDialogPhoto = function (array $photo, int $index, bool $marked) {
        ?>
        <li class="event-current-photo">
            <img src="<?= htmlspecialchars($photo['src']) ?>" alt="" loading="lazy">
            <?php if ($index === 0) : ?>
                <span class="gallery-cover-badge" title="Used as the event's cover on the website">Cover</span>
            <?php endif; ?>
            <label class="event-photo-remove">
                <input type="checkbox" name="removePhotos[]" value="<?= $photo['id'] ?>" <?= $marked ? 'checked' : '' ?>>
                <span>Remove</span>
            </label>
        </li>
        <?php
    };

    // archive or restore button
    $renderArchiveForm = function (array $event) use ($returnURL) {
        ?>
        <form method="POST" action="<?= htmlspecialchars($returnURL) ?>"
              <?php if (!$event['isArchived']) : ?>onsubmit="return confirm('Archive this event? It will no longer be listed under Upcoming Events on the website; it stays under Past Events.');"<?php endif; ?>>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="toggleArchive">
            <input type="hidden" name="eventID" value="<?= $event['eventID'] ?>">
            <button type="submit" class="manage-action-button <?= $event['isArchived'] ? '' : 'is-danger' ?>">
                <?= $event['isArchived'] ? 'Restore' : 'Archive' ?>
            </button>
        </form>
        <?php
    };

    // one event card
    $renderEventCard = function (array $event) use ($eventDateLabel, $eventJson, $renderArchiveForm) {
        $isSoon = $event['daysAway'] !== null && $event['daysAway'] >= 0 && $event['daysAway'] <= 1;
        $editURL = 'manageEvents.php?edit=' . $event['eventID'];
        $editData = htmlspecialchars($eventJson($event));
        ?>
        <article class="manage-card event-manage-card timing-<?= $event['timing'] ?> <?= $event['dateToBeConfirmed'] ? 'is-tbc' : '' ?> <?= $event['isArchived'] ? 'is-archived' : '' ?>">

            <!-- cover photo -->
            <a class="manage-card-cover event-edit" href="<?= $editURL ?>" data-event="<?= $editData ?>"
               aria-haspopup="dialog" aria-label="Edit <?= htmlspecialchars($event['name']) ?>">
                <?php if ($event['coverImage']) : ?>
                    <img src="<?= htmlspecialchars(galleryImageSrc($event['coverImage'])) ?>" alt="" loading="lazy">
                <?php else : ?>
                    <span>No photos yet</span>
                <?php endif; ?>
            </a>

            <!-- name, date and description -->
            <div class="manage-card-title">
                <strong><?= htmlspecialchars($event['name']) ?></strong>
                <span><?= htmlspecialchars($eventDateLabel($event)) ?></span>
            </div>

            <p class="manage-card-text <?= ($event['description'] ?? '') === '' ? 'is-empty' : '' ?>"
               title="<?= htmlspecialchars($event['description'] ?? '') ?>">
                <?= ($event['description'] ?? '') === ''
                    ? 'No description yet.'
                    : htmlspecialchars(mb_strimwidth($event['description'], 0, 140, '...')) ?>
            </p>

            <!-- when, photos and who added it -->
            <dl class="manage-card-details">
                <div>
                    <dt>When</dt>
                    <dd><span class="when-badge <?= $isSoon ? 'when-soon' : '' ?>"><?= htmlspecialchars($event['when']) ?></span></dd>
                </div>
                <div>
                    <dt>Photos</dt>
                    <dd><?= $event['photoCount'] ?></dd>
                </div>
                <div>
                    <dt>Added by</dt>
                    <dd><?= htmlspecialchars($event['createdByName'] ?? 'Unknown') ?></dd>
                </div>
            </dl>

            <div class="manage-card-actions">
                <!-- edit and archive, edit works as a normal link without javascript -->
                <a class="manage-action-button event-edit" href="<?= $editURL ?>"
                   data-event="<?= $editData ?>" aria-haspopup="dialog">Edit</a>
                <?php $renderArchiveForm($event); ?>
            </div>

        </article>
        <?php
    };
?>

<div class="manage-page events-page">

    <?php
    // success messages, save errors show inside the dialog instead
    $pageErrors = $errors;
    $errors = [];
    include __DIR__ . '/components/dashboardAlerts.php';
    $errors = $pageErrors;
    ?>

    <!-- summary cards -->
    <section class="dashboard-cards">
        <?php
        $cardTitle = 'Upcoming';
        $cardValue = $counts['upcoming'];
        $cardMeta = $counts['soon'] . ' in the next 7 days';
        $cardClass = 'events-upcoming-card';
        include __DIR__ . '/components/dashboardCard.php';

        $cardTitle = 'Past';
        $cardValue = $counts['past'];
        $cardMeta = 'Already happened';
        $cardClass = 'events-past-card';
        include __DIR__ . '/components/dashboardCard.php';

        $cardTitle = 'Photos';
        $cardValue = $totalPhotos;
        $cardMeta = $needPhotos === 1 ? '1 past event has none yet' : $needPhotos . ' past events have none yet';
        $cardClass = 'gallery-photos-card';
        include __DIR__ . '/components/dashboardCard.php';

        $cardTitle = 'Archived';
        $cardValue = $counts['archived'];
        $cardMeta = $showArchived ? 'Shown below' : 'Select to show';
        $cardClass = 'events-archived-card';
        $cardLink = $showArchived ? 'manageEvents.php' : 'manageEvents.php?showArchived=1#archived-events';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
    </section>

    <!-- upcoming events -->
    <section class="dashboard-panel manage-section" id="upcoming-events">
        <div class="panel-header">
            <h2>Upcoming Events</h2>
            <button type="button" class="form-button" id="event-add" aria-haspopup="dialog" aria-controls="event-dialog">
                Add event
            </button>
        </div>

        <div class="manage-grid">
            <?php if (!$upcomingEvents) : ?>
                <p class="manage-empty">
                    No upcoming events. Use "Add event" to schedule one.
                </p>
            <?php endif; ?>
            <?php foreach ($upcomingEvents as $event) $renderEventCard($event); ?>
        </div>
    </section>

    <!-- past events -->
    <section class="dashboard-panel manage-section" id="past-events">
        <div class="panel-header">
            <h2>Past Events</h2>
        </div>

        <div class="manage-grid">
            <?php if (!$pastEvents) : ?>
                <p class="manage-empty">No past events yet.</p>
            <?php endif; ?>
            <?php foreach ($pastEvents as $event) $renderEventCard($event); ?>
        </div>
    </section>

    <!-- archived events, only when asked -->
    <?php if ($showArchived) : ?>
        <section class="dashboard-panel manage-section" id="archived-events">
            <div class="panel-header">
                <h2>Archived Events</h2>
                <a class="panel-button" href="manageEvents.php">Hide archived</a>
            </div>

            <div class="manage-grid">
                <?php if (!$archivedEvents) : ?>
                    <p class="manage-empty">No archived events.</p>
                <?php endif; ?>
                <?php foreach ($archivedEvents as $event) $renderEventCard($event); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- add and edit dialog, filled in by the server after a failed save or by the script when add or edit is clicked -->
    <dialog class="dashboard-dialog event-dialog" id="event-dialog" aria-labelledby="event-dialog-title"
            <?= $openDialog ? 'data-open-on-load="1"' : '' ?>>
        <form method="POST" action="<?= htmlspecialchars($returnURL) ?>" enctype="multipart/form-data" class="dashboard-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="eventID" id="event-id" value="<?= $editingEvent ? $editingEvent['eventID'] : '' ?>">

            <div class="dialog-header">
                <h2 id="event-dialog-title"><?= $editingEvent ? 'Edit event' : 'Add an event' ?></h2>
                <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/>
                    </svg>
                </button>
            </div>

            <div class="dialog-body">
                <!-- save errors -->
                <div class="dialog-errors" <?= $errors ? '' : 'hidden' ?>>
                    <?php $flash = null; include __DIR__ . '/components/dashboardAlerts.php'; ?>
                </div>

                <div class="form-field">
                    <label for="event-name">Event name</label>
                    <input type="text" id="event-name" name="name" maxlength="255" required
                           value="<?= htmlspecialchars($form['name'] ?? '') ?>">
                </div>

                <div class="form-field">
                    <label for="event-date">Date <span class="field-hint">(optional)</span></label>
                    <input type="date" id="event-date" name="eventDate"
                           value="<?= htmlspecialchars($form['eventDate'] ?? '') ?>">
                </div>

                <!-- only shown while the date is empty -->
                <?php $formTbc = (bool) ($form['dateToBeConfirmed'] ?? false); ?>
                <fieldset class="form-field event-undated" id="event-undated" <?= ($form['eventDate'] ?? '') !== '' ? 'hidden' : '' ?>>
                    <legend>No date yet. Is this event:</legend>
                    <label class="event-undated-option">
                        <input type="radio" name="undatedStatus" value="tbc" id="event-undated-tbc" <?= $formTbc ? 'checked' : '' ?>>
                        <span>
                            <strong>Still to be confirmed</strong>
                            <span class="field-hint">Listed under Upcoming Events as "Date to be announced"</span>
                        </span>
                    </label>
                    <label class="event-undated-option">
                        <input type="radio" name="undatedStatus" value="past" id="event-undated-past" <?= $formTbc ? '' : 'checked' ?>>
                        <span>
                            <strong>A past event</strong>
                            <span class="field-hint">Listed under Past Events</span>
                        </span>
                    </label>
                </fieldset>

                <div class="form-field">
                    <label for="event-description">Description <span class="field-hint">(optional, shown on the website)</span></label>
                    <textarea id="event-description" name="description" rows="5"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                </div>

                <!-- current photos when editing, the script refills the list for each event -->
                <div class="form-field event-current-photos" id="event-current-photos" <?= $editingPhotos ? '' : 'hidden' ?>>
                    <span class="form-label">Current photos <span class="field-hint">(the first photo is the cover on the website)</span></span>
                    <ul class="event-current-photo-list" id="event-current-photo-list">
                        <?php foreach ($editingPhotos as $index => $photo) {
                            $renderDialogPhoto(
                                ['id' => (int) $photo['galleryItemID'], 'src' => galleryImageSrc($photo['image'])],
                                $index,
                                in_array((int) $photo['galleryItemID'], $markedForRemoval, true)
                            );
                        } ?>
                    </ul>
                </div>

                <!-- template the script copies for each current photo -->
                <template id="event-photo-template">
                    <?php $renderDialogPhoto(['id' => 0, 'src' => ''], 0, false); ?>
                </template>

                <!-- new photos -->
                <?php
                $uploaderID = 'event-photos';
                $uploaderLabel = 'Add photos';
                $uploaderHint = '(optional)';
                include __DIR__ . '/components/galleryUploader.php';
                ?>
            </div>

            <div class="dialog-footer">
                <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                <button type="submit" class="form-button" id="event-submit"><?= $editingEvent ? 'Save changes' : 'Add event' ?></button>
            </div>
        </form>
    </dialog>

</div>

<script>
    // close buttons, clicking outside the dialog also closes it
    document.querySelectorAll('.dashboard-dialog').forEach((dialog) => {
        dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    // add and edit dialog
    (() => {
        const dialog = document.getElementById('event-dialog');
        const field = (id) => document.getElementById(id);

        // fill in the dialog for an event or a new one and open it
        const open = (event) => {
            dialog.querySelector('.dialog-errors').hidden = true;
            dialog.querySelector('.gallery-uploader').galleryReset();

            field('event-id').value = event ? event.id : '';
            field('event-name').value = event ? event.name : '';
            field('event-date').value = event ? event.eventDate : '';
            field(event && event.tbc ? 'event-undated-tbc' : 'event-undated-past').checked = true;
            syncUndated();
            field('event-description').value = event ? event.description : '';
            showPhotos(event ? event.photos : []);

            field('event-dialog-title').textContent = event ? 'Edit event' : 'Add an event';
            field('event-submit').textContent = event ? 'Save changes' : 'Add event';

            dialog.showModal();
            field('event-name').focus();
        };

        // the event's current photos, each with a remove tick box and the first is the cover
        const showPhotos = (photos) => {
            const template = field('event-photo-template').content.firstElementChild;
            field('event-current-photo-list').replaceChildren(...photos.map((photo, index) => {
                const item = template.cloneNode(true);
                item.querySelector('img').src = photo.src;
                item.querySelector('input').value = photo.id;
                if (index !== 0) item.querySelector('.gallery-cover-badge').remove();
                return item;
            }));
            field('event-current-photos').hidden = photos.length === 0;
        };

        // the to be confirmed or past choice only applies while there is no date
        const syncUndated = () => {
            field('event-undated').hidden = field('event-date').value !== '';
        };
        field('event-date').addEventListener('input', syncUndated);

        // add and edit buttons
        field('event-add')?.addEventListener('click', () => open(null));

        document.querySelectorAll('.event-edit').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                open(JSON.parse(link.dataset.event));
            });
        });

        // remove edit from the address so a refresh does not reopen it
        dialog.addEventListener('close', () => {
            const url = new URL(location.href);
            if (url.searchParams.has('edit')) {
                url.searchParams.delete('edit');
                history.replaceState(null, '', url);
            }
        });

        // open straight away after a failed save or an edit link
        if (dialog.dataset.openOnLoad) dialog.showModal();
    })();
</script>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>