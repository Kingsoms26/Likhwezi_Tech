<?php
    // registrations.php is the staff registrations page for admin and customer service
    // contains the summary cards and the cym sign ups from cym.php with search, filters and sorting
    // customer service can archive but only admin can restore

    require_once __DIR__ . '/../includes/security.php';

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/registrationData.php';

    // make sure the staff member is logged in and has access
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Customer Service']);

    $isAdmin = $account['role'] === 'Admin';
    $programmes = getRegistrationProgrammes($conn);

    // build a link back to this page that keeps the current filters
    function registrationsUrl(array $filters, array $overrides = [])
    {
        $params = array_merge($filters, $overrides);

        // leave defaults out to keep the address short
        if ($params['view'] === 'active') unset($params['view']);
        if ((int) $params['page'] === 1) unset($params['page']);

        // the default sort is left out too
        $sortColumns = getRegistrationSortColumns();
        if (isset($params['sort'], $params['dir']) && $params['dir'] === $sortColumns[$params['sort']]) unset($params['dir']);
        if (($params['sort'] ?? '') === 'date' && !isset($params['dir'])) unset($params['sort']);

        $params = array_filter($params, fn($v) => $v !== '' && $v !== null);

        return 'registrations.php' . ($params ? '?' . http_build_query($params) : '');
    }

    // a column heading that sorts the table when clicked
    function sortableHeader(array $filters, $column, $label)
    {
        $isCurrent = $filters['sort'] === $column;
        $nextDir = $isCurrent
            ? ($filters['dir'] === 'asc' ? 'desc' : 'asc')
            : getRegistrationSortColumns()[$column];

        $ariaSort = $isCurrent ? ($filters['dir'] === 'asc' ? 'ascending' : 'descending') : 'none';
        $stateClass = $isCurrent ? ' sort-' . $filters['dir'] : '';
        $url = registrationsUrl($filters, ['sort' => $column, 'dir' => $nextDir, 'page' => 1]);
        $hint = 'Sort by ' . strtolower($label) . ($nextDir === 'asc' ? ', ascending' : ', descending');

        return '<th scope="col" aria-sort="' . $ariaSort . '">'
            . '<a class="sort-link js-results-link' . $stateClass . '" data-focus-key="sort-' . $column . '" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($hint) . '">'
            . htmlspecialchars($label)
            . '<span class="sort-icon" aria-hidden="true"></span>'
            . '</a></th>';
    }

    // add spaces to a phone number, same format as the public form
    function formatPhone($phone)
    {
        if (!$phone) return '';
        return preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1 $2 $3', $phone);
    }

    // format a date for the table
    function formatDate($dateTime)
    {
        return $dateTime ? date('j M Y', strtotime($dateTime)) : '';
    }

    // archive or restore, handled before any html then back to the page so a refresh never repeats it
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $registrationID = (int) ($_POST['registrationID'] ?? 0);
        // the filters and sort come from the address so the page comes back the same
        $returnFilters = readRegistrationFilters($_GET, $programmes);

        if (!verifyCsrf()) {
            $_SESSION['registrationNotice'] = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];
        } elseif ($action === 'restore' && !$isAdmin) {
            $_SESSION['registrationNotice'] = ['type' => 'error', 'text' => 'Only Administrators can restore registrations.'];
        } elseif (in_array($action, ['archive', 'restore'], true) && $registrationID > 0) {
            $isArchive = $action === 'archive';

            if (setRegistrationArchived($conn, $registrationID, $isArchive, (int) $account['accountID'])) {
                $verb = $isArchive ? 'Archived' : 'Restored';
                $_SESSION['registrationNotice'] = ['type' => 'success', 'text' => "{$verb} registration #{$registrationID}."];
                logActivity($conn, 'Registration', strtolower($verb), "{$verb} registration #{$registrationID}", $registrationID);
            } else {
                $_SESSION['registrationNotice'] = [
                    'type' => 'error',
                    'text' => 'That registration could not be updated. It may already have been ' . ($isArchive ? 'archived' : 'restored') . '.'
                ];
            }
        }

        header('Location: ' . registrationsUrl($returnFilters));
        exit;
    }

    // fetch the filters, numbers and this page of registrations
    $filters = readRegistrationFilters($_GET, $programmes);
    $stats = getRegistrationStats($conn);
    $result = getRegistrations($conn, $filters);
    $filters['page'] = $result['page'];

    $isArchivedView = $filters['view'] === 'archived';
    $hasActiveFilters = $filters['q'] !== '' || $filters['programme'] !== ''
        || $filters['age'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';

    // details for the view popup
    $modalData = [];
    foreach ($result['rows'] as $row) {
        $modalData[$row['registrationID']] = [
            'id' => $row['registrationID'],
            'name' => $row['firstName'] . ' ' . $row['lastName'],
            'programme' => $row['programme'],
            'age' => $row['age'],
            'isMinor' => $row['isMinor'],
            'registeredAt' => date('j M Y, H:i', strtotime($row['registeredAt'])),
            'email' => $row['email'],
            'phone' => formatPhone($row['phoneNumber']),
            'consentGivenAt' => date('j M Y, H:i', strtotime($row['consentGivenAt'])),
            'mediaConsent' => $row['mediaConsent'],
            'guardianName' => trim(($row['guardianName'] ?? '') . ' ' . ($row['guardianLastName'] ?? '')),
            'guardianRelationship' => $row['guardianRelationship'],
            'guardianPhone' => formatPhone($row['guardianPhoneNumber']),
            'guardianEmail' => $row['guardianEmail'],
            'guardianConsentGivenAt' => $row['guardianConsentGivenAt']
                ? date('j M Y, H:i', strtotime($row['guardianConsentGivenAt'])) : null,
            'isArchived' => $row['isArchived'],
            'archivedAt' => formatDate($row['archivedAt']),
            'archivedBy' => $row['archivedBy']
        ];
    }

    // clear the filters but keep the view and sort
    $clearFiltersUrl = registrationsUrl($filters, ['q' => '', 'programme' => '', 'age' => '', 'from' => '', 'to' => '', 'page' => 1]);

    // which rows are showing
    $firstShown = $result['totalRows'] ? ($result['page'] - 1) * 10 + 1 : 0;
    $lastShown = min($result['page'] * 10, $result['totalRows']);

    // read out to screen readers after each update
    $resultsAnnouncement = $result['totalRows']
        ? "Showing {$firstShown} to {$lastShown} of {$result['totalRows']} " . ($isArchivedView ? 'archived ' : '')
            . ($result['totalRows'] === 1 ? 'registration' : 'registrations')
        : 'No registrations to show';

    // results area, built once so it can also be sent on its own when the filters change
    ob_start();
?>
<div id="registrations-results" class="registrations-results" data-url="<?= htmlspecialchars(registrationsUrl($filters)) ?>">

    <section class="dashboard-panel registrations-panel">

        <div class="panel-header">
            <h2><?= $isArchivedView ? 'Archived Registrations' : 'Registrations' ?></h2>

            <!-- active and archived tabs, the filters reset on switch -->
            <nav class="view-toggle js-results-links" aria-label="Registration view">
                <a href="registrations.php" data-focus-key="view-active"
                   class="<?= !$isArchivedView ? 'active' : '' ?>"
                   <?= !$isArchivedView ? 'aria-current="page"' : '' ?>>
                    Active <span class="view-count"><?= $result['counts']['active'] ?></span>
                </a>
                <a href="registrations.php?view=archived" data-focus-key="view-archived"
                   class="<?= $isArchivedView ? 'active' : '' ?>"
                   <?= $isArchivedView ? 'aria-current="page"' : '' ?>>
                    Archived <span class="view-count"><?= $result['counts']['archived'] ?></span>
                </a>
            </nav>
        </div>

        <div class="panel-body">

            <!-- filters, kept in the address so they can be bookmarked -->
            <form class="registration-filters js-results-form" method="get" action="registrations.php">
                <?php if ($isArchivedView) : ?>
                    <input type="hidden" name="view" value="archived">
                <?php endif; ?>

                <!-- keep the current sort when filters are applied -->
                <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($filters['dir']) ?>">

                <div class="filter-field filter-search">
                    <label for="filter-q">Search</label>
                    <input type="search" id="filter-q" name="q" maxlength="100" data-focus-key="filter-q"
                           placeholder="Name, email or phone"
                           value="<?= htmlspecialchars($filters['q']) ?>">
                </div>

                <div class="filter-field">
                    <label for="filter-programme">Programme</label>
                    <select id="filter-programme" name="programme">
                        <option value="">All programmes</option>
                        <?php foreach ($programmes as $programme) : ?>
                            <option value="<?= htmlspecialchars($programme) ?>"
                                <?= $filters['programme'] === $programme ? 'selected' : '' ?>>
                                <?= htmlspecialchars($programme) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="filter-age">Age group</label>
                    <select id="filter-age" name="age">
                        <option value="">All ages</option>
                        <option value="minor" <?= $filters['age'] === 'minor' ? 'selected' : '' ?>>Under 18</option>
                        <option value="adult" <?= $filters['age'] === 'adult' ? 'selected' : '' ?>>18 and over</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label for="filter-from">Registered from</label>
                    <input type="date" id="filter-from" name="from" value="<?= htmlspecialchars($filters['from']) ?>">
                </div>

                <div class="filter-field">
                    <label for="filter-to">Registered to</label>
                    <input type="date" id="filter-to" name="to" value="<?= htmlspecialchars($filters['to']) ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="panel-button button-primary" data-focus-key="filter-apply">Apply filters</button>
                    <?php if ($hasActiveFilters) : ?>
                        <a class="panel-button js-results-link" data-focus-key="filter-q" href="<?= htmlspecialchars($clearFiltersUrl) ?>">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- shown when nothing matches -->
            <?php if (!$result['rows']) : ?>

                <div class="empty-state">
                    <?php if ($hasActiveFilters) : ?>
                        <p>No registrations match these filters.</p>
                        <a class="panel-button js-results-link" data-focus-key="filter-q" href="<?= htmlspecialchars($clearFiltersUrl) ?>">Clear filters</a>
                    <?php elseif ($isArchivedView) : ?>
                        <p>No archived registrations. Archived records will appear here.</p>
                    <?php else : ?>
                        <p>No registrations yet. New sign-ups from the Cyber Young Minds page will appear here.</p>
                    <?php endif; ?>
                </div>

            <?php else : ?>

                <!-- registrations table -->
                <div class="dashboard-table-wrap">
                    <table class="dashboard-table registrations-table">
                        <!-- column widths keep the table evenly spaced across pages and sorts -->
                        <colgroup>
                            <col class="col-name">
                            <col class="col-programme">
                            <col class="col-age">
                            <col class="col-date">
                            <col class="col-media">
                            <col class="col-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <?= sortableHeader($filters, 'name', 'Name') ?>
                                <?= sortableHeader($filters, 'programme', 'Programme') ?>
                                <?= sortableHeader($filters, 'age', 'Age') ?>
                                <?= sortableHeader($filters, 'date', $isArchivedView ? 'Archived' : 'Registered') ?>
                                <?= sortableHeader($filters, 'media', 'Media consent') ?>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($result['rows'] as $row) :
                                $fullName = $row['firstName'] . ' ' . $row['lastName'];
                            ?>
                                <tr>
                                    <td class="registrant-name"><?= htmlspecialchars($fullName) ?></td>

                                    <td><?= htmlspecialchars($row['programme']) ?></td>

                                    <td class="registrant-age">
                                        <?= htmlspecialchars($row['age']) ?>
                                        <?php if ($row['isMinor']) : ?>
                                            <span class="minor-badge">Minor</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="registrant-date">
                                        <?= htmlspecialchars(formatDate($isArchivedView ? $row['archivedAt'] : $row['registeredAt'])) ?>
                                    </td>

                                    <!-- privacy consent is required on cym.php so only media consent is listed -->
                                    <td class="registrant-media">
                                        <span class="media-flag <?= $row['mediaConsent'] ? 'media-yes' : 'media-no' ?>">
                                            <?= $row['mediaConsent'] ? 'Yes' : 'No' ?>
                                        </span>
                                    </td>

                                    <!-- view, archive and restore buttons -->
                                    <td class="table-actions">
                                        <button type="button" class="table-action js-view-registration"
                                                data-id="<?= (int) $row['registrationID'] ?>">
                                            View<span class="visually-hidden"> <?= htmlspecialchars($fullName) ?></span>
                                        </button>

                                        <?php if (!$isArchivedView) : ?>
                                            <button type="button" class="table-action table-action-danger js-confirm-action"
                                                    data-action="archive"
                                                    data-id="<?= (int) $row['registrationID'] ?>"
                                                    data-name="<?= htmlspecialchars($fullName) ?>">
                                                Archive<span class="visually-hidden"> <?= htmlspecialchars($fullName) ?></span>
                                            </button>
                                        <?php elseif ($isAdmin) : ?>
                                            <button type="button" class="table-action js-confirm-action"
                                                    data-action="restore"
                                                    data-id="<?= (int) $row['registrationID'] ?>"
                                                    data-name="<?= htmlspecialchars($fullName) ?>">
                                                Restore<span class="visually-hidden"> <?= htmlspecialchars($fullName) ?></span>
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
                    <nav class="registration-pagination js-results-links" aria-label="Registration pages">
                        <?php if ($result['page'] > 1) : ?>
                            <a class="panel-button" data-focus-key="page-prev" href="<?= htmlspecialchars(registrationsUrl($filters, ['page' => $result['page'] - 1])) ?>">Previous</a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $result['pages']; $p++) : ?>
                            <a class="panel-button <?= $p === $result['page'] ? 'active' : '' ?>" data-focus-key="page-<?= $p ?>"
                               href="<?= htmlspecialchars(registrationsUrl($filters, ['page' => $p])) ?>"
                               <?= $p === $result['page'] ? 'aria-current="page"' : '' ?>><?= $p ?></a>
                        <?php endfor; ?>

                        <?php if ($result['page'] < $result['pages']) : ?>
                            <a class="panel-button" data-focus-key="page-next" href="<?= htmlspecialchars(registrationsUrl($filters, ['page' => $result['page'] + 1])) ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </div>

    </section>

    <p class="visually-hidden" id="results-announcement"><?= htmlspecialchars($resultsAnnouncement) ?></p>

    <!-- details for the view popup, escaped so it cannot break out of the script tag -->
    <script type="application/json" id="registration-data">
    <?= json_encode($modalData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    </script>

</div>
<?php
$resultsHtml = ob_get_clean();

// when the page script asks for new results send the results area only
if (($_SERVER['HTTP_X_REGISTRATIONS_PARTIAL'] ?? '') === '1') {
    header('Cache-Control: no-store');
    echo $resultsHtml;
    exit;
}

// message from the last archive or restore
$notice = $_SESSION['registrationNotice'] ?? null;
unset($_SESSION['registrationNotice']);

$pageTitle = 'Registrations';
$activePage = 'registrations';

// open the shared dashboard layout
include __DIR__ . '/components/dashboard.php';
?>

<!-- registrations content, shared by both roles so the css is scoped to the page not a role -->
<div class="registrations-page">

    <!-- success or error message -->
    <?php if ($notice) : ?>
        <div class="page-notice page-notice-<?= htmlspecialchars($notice['type']) ?>" role="status">
            <?= htmlspecialchars($notice['text']) ?>
        </div>
    <?php endif; ?>

    <!-- summary cards -->
    <section class="dashboard-cards">

        <?php
        // total registrations that are not archived
        $cardTitle = 'Total Registrations';
        $cardValue = $stats['total'];
        $cardMeta = 'Active, excluding archived';
        $cardClass = 'registrations-total-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // registrations since the 1st of this month
        $cardTitle = 'Registered This Month';
        $cardValue = $stats['thisMonth'];
        $cardMeta = 'Since 1 ' . date('F');
        $cardClass = 'registrations-month-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

        <?php
        // under 18s, who have guardian details and consent
        $cardTitle = 'Minors';
        $cardValue = $stats['minors'];
        $cardMeta = 'Under 18, guardian consent on file';
        $cardClass = 'registrations-minors-card';
        include __DIR__ . '/components/dashboardCard.php';
        ?>

    </section>

    <!-- results area -->
    <?= $resultsHtml ?>

    <!-- screen readers hear the new result count after each update -->
    <div class="visually-hidden" id="results-live" aria-live="polite"></div>

    <!-- view details popup -->
    <div class="modal fade registration-modal registration-detail-modal" id="registration-detail"
         tabindex="-1" aria-labelledby="detail-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <h2 class="modal-title" id="detail-title">Registration details</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <section class="detail-section">
                        <h3>Participant</h3>
                        <dl class="detail-list">
                            <dt>Name</dt>             <dd data-field="name"></dd>
                            <dt>Age</dt>              <dd data-field="ageText"></dd>
                            <dt>Programme</dt>        <dd data-field="programme"></dd>
                            <dt>Registered</dt>       <dd data-field="registeredAt"></dd>
                            <dt>Registration ID</dt>  <dd data-field="id"></dd>
                        </dl>
                    </section>

                    <section class="detail-section">
                        <h3>Contact</h3>
                        <dl class="detail-list">
                            <dt>Email</dt>  <dd data-field="email"></dd>
                            <dt>Phone</dt>  <dd data-field="phone"></dd>
                        </dl>
                    </section>

                    <section class="detail-section">
                        <h3>Consent</h3>
                        <dl class="detail-list">
                            <dt>Privacy (POPIA)</dt>  <dd data-field="privacyText"></dd>
                            <dt>Media</dt>            <dd data-field="mediaText"></dd>
                        </dl>
                    </section>

                    <section class="detail-section detail-guardian" data-section="guardian">
                        <h3>Guardian</h3>
                        <dl class="detail-list">
                            <dt>Name</dt>          <dd data-field="guardianName"></dd>
                            <dt>Relationship</dt>  <dd data-field="guardianRelationship"></dd>
                            <dt>Phone</dt>         <dd data-field="guardianPhone"></dd>
                            <dt>Email</dt>         <dd data-field="guardianEmail"></dd>
                            <dt>Consent</dt>       <dd data-field="guardianConsentText"></dd>
                        </dl>
                    </section>

                    <section class="detail-section detail-archive" data-section="archive">
                        <h3>Archive</h3>
                        <dl class="detail-list">
                            <dt>Archived on</dt>  <dd data-field="archivedAt"></dd>
                            <dt>Archived by</dt>  <dd data-field="archivedBy"></dd>
                        </dl>
                    </section>
                </div>

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

    <!-- archive and restore confirmation popup -->
    <div class="modal fade registration-modal registration-confirm-modal" id="registration-confirm"
         tabindex="-1" aria-labelledby="confirm-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="<?= htmlspecialchars(registrationsUrl($filters)) ?>">

                <div class="modal-header">
                    <h2 class="modal-title" id="confirm-title"></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="confirm-message"></p>
                </div>

                <?= csrfField() ?>
                <input type="hidden" name="action" value="">
                <input type="hidden" name="registrationID" value="">

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button confirm-submit"></button>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
    // registrations page script
    document.addEventListener('DOMContentLoaded', function () {

        // 1 update the results without reloading the page

        const liveRegion = document.getElementById('results-live');
        const confirmForm = document.querySelector('#registration-confirm form');
        let activeRequest = null;

        // the results area on the page
        function resultsArea() {
            return document.getElementById('registrations-results');
        }

        // fetch new results and swap them in
        async function loadResults(url, options) {
            const settings = Object.assign({ addToHistory: true, focusKey: null }, options);
            const area = resultsArea();

            // a newer click cancels a request still in progress
            if (activeRequest) activeRequest.abort();
            activeRequest = new AbortController();

            area.classList.add('is-loading');
            area.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(url, {
                    headers: { 'X-Registrations-Partial': '1' },
                    credentials: 'same-origin',
                    signal: activeRequest.signal
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                const template = document.createElement('template');
                template.innerHTML = (await response.text()).trim();
                const freshArea = template.content.querySelector('#registrations-results');

                if (!freshArea) throw new Error('Unexpected response');

                area.replaceWith(freshArea);

                // the server sends back the tidy address for what is now shown
                const shownUrl = freshArea.dataset.url;
                if (settings.addToHistory) {
                    history.pushState({ registrations: true }, '', shownUrl);
                }

                // archive and restore should come back to the view now on screen
                confirmForm.action = shownUrl;

                // put keyboard focus back on the control that was used
                const focusTarget = settings.focusKey
                    && freshArea.querySelector('[data-focus-key="' + settings.focusKey + '"]');
                if (focusTarget) focusTarget.focus({ preventScroll: true });

                // after using the page links at the bottom bring the top of the table into view
                if (settings.focusKey && settings.focusKey.indexOf('page-') === 0
                    && freshArea.getBoundingClientRect().top < 0) {
                    freshArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                liveRegion.textContent = freshArea.querySelector('#results-announcement').textContent;
            } catch (error) {
                if (error.name === 'AbortError') return;
                window.location.href = url;   // fall back to a normal page load
            }
        }

        // sort headings, active and archived tabs, page links and clear
        document.addEventListener('click', function (event) {
            const link = event.target.closest(
                '#registrations-results a.js-results-link, #registrations-results .js-results-links a'
            );
            if (!link) return;

            // ctrl, cmd, shift and middle click still open a new tab
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;

            event.preventDefault();
            loadResults(link.href, { focusKey: link.dataset.focusKey });
        });

        // filter form, empty fields are left out to keep the address short
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('.js-results-form');
            if (!form) return;

            event.preventDefault();

            const params = new URLSearchParams();
            new FormData(form).forEach(function (value, key) {
                if (value !== '') params.append(key, value);
            });

            loadResults(form.action.split('?')[0] + '?' + params.toString(), { focusKey: 'filter-apply' });
        });

        // back and forward buttons show the results for that address
        window.addEventListener('popstate', function () {
            loadResults(window.location.href, { addToHistory: false });
        });

        // 2 popups

        if (!window.bootstrap) {
            console.error('Bootstrap JS did not load: registration modals are unavailable.');
            return;
        }

        const detailElement = document.getElementById('registration-detail');
        const confirmElement = document.getElementById('registration-confirm');
        const detailModal = bootstrap.Modal.getOrCreateInstance(detailElement);
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmElement);

        // shown when an optional value is missing
        const notProvided = 'Not provided';

        // read fresh each time since the details are replaced with the results
        function currentRegistrations() {
            return JSON.parse(document.getElementById('registration-data').textContent);
        }

        // fill in one field in the view popup
        function setField(name, value) {
            const field = detailElement.querySelector('[data-field="' + name + '"]');
            field.textContent = value || notProvided;
            field.classList.toggle('is-empty', !value);
        }

        // fill in the view popup and open it
        function openDetails(id) {
            const r = currentRegistrations()[id];
            if (!r) return;

            setField('name', r.name);
            setField('ageText', r.age + ' at registration');
            setField('programme', r.programme);
            setField('registeredAt', r.registeredAt);
            setField('id', '#' + r.id);
            setField('email', r.email);
            setField('phone', r.phone);
            setField('privacyText', 'Given ' + r.consentGivenAt);
            setField('mediaText', r.mediaConsent ? 'Given' : 'Not given');

            // guardian details only apply to under 18s
            detailElement.querySelector('[data-section="guardian"]').hidden = !r.isMinor;
            if (r.isMinor) {
                setField('guardianName', r.guardianName);
                setField('guardianRelationship', r.guardianRelationship);
                setField('guardianPhone', r.guardianPhone);
                setField('guardianEmail', r.guardianEmail);
                setField('guardianConsentText', r.guardianConsentGivenAt ? 'Given ' + r.guardianConsentGivenAt : '');
            }

            // archive details only for archived registrations
            detailElement.querySelector('[data-section="archive"]').hidden = !r.isArchived;
            if (r.isArchived) {
                setField('archivedAt', r.archivedAt);
                setField('archivedBy', r.archivedBy);
            }

            detailModal.show();
        }

        // fill in the archive or restore confirmation and open it
        function openConfirm(action, id, name) {
            const isArchive = action === 'archive';

            confirmElement.querySelector('#confirm-title').textContent =
                isArchive ? 'Archive this registration?' : 'Restore this registration?';

            confirmElement.querySelector('.confirm-message').textContent = isArchive
                ? name + ' will move to Archived and no longer count towards the totals. An Administrator can restore it later.'
                : name + ' will move back to Active registrations.';

            const submit = confirmElement.querySelector('.confirm-submit');
            submit.textContent = isArchive ? 'Archive' : 'Restore';
            submit.classList.toggle('button-danger', isArchive);
            submit.classList.toggle('button-primary', !isArchive);

            confirmElement.querySelector('input[name="action"]').value = action;
            confirmElement.querySelector('input[name="registrationID"]').value = id;

            confirmModal.show();
        }

        // view, archive and restore buttons
        document.addEventListener('click', function (event) {
            const viewButton = event.target.closest('.js-view-registration');
            if (viewButton) {
                openDetails(viewButton.dataset.id);
                return;
            }

            const actionButton = event.target.closest('.js-confirm-action');
            if (actionButton) {
                openConfirm(actionButton.dataset.action, actionButton.dataset.id, actionButton.dataset.name);
            }
        });
    });
</script>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>