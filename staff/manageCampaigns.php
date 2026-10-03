<?php
    // manageCampaigns.php is the staff campaigns page
    // the list view shows the active, draft, closed and archived campaigns
    // the campaign view shows one campaign's progress, photos and donations
    // only active campaigns show on campaign.php and take donations, the first photo is the campaign's image
    // add and edit share one dialog and close, archive and restore ask first in a confirmation popup
    // the database work lives in helpers/campaignData.php and helpers/galleryData.php

    session_start();

    require __DIR__ . '/../config/dbConnection.php';
    require __DIR__ . '/helpers/auth.php';
    require __DIR__ . '/helpers/campaignData.php';
    require __DIR__ . '/helpers/galleryData.php';
    require __DIR__ . '/helpers/marketingData.php';

    // admin and marketing can both add, edit and archive campaigns
    $account = requireStaff($conn);
    requireRole($account, ['Admin', 'Marketing']);

    // which view to show and where forms go back to
    $showArchived = isset($_GET['showArchived']);
    $viewCampaignID = isset($_GET['campaign']) && ctype_digit($_GET['campaign']) ? (int) $_GET['campaign'] : null;
    $returnURL = 'manageCampaigns.php' . ($viewCampaignID ? '?campaign=' . $viewCampaignID : ($showArchived ? '?showArchived=1' : ''));

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
        $campaignID = ctype_digit($_POST['campaignID'] ?? '') ? (int) $_POST['campaignID'] : null;

        // archive or restore
        if ($action === 'toggleArchive' && $campaignID) {
            $result = toggleCampaignArchive($conn, $campaignID, (int) $account['accountID']);

            if ($result) {
                setFlash('success', 'Campaign ' . $result . '.');
                logActivity($conn, 'Campaign', $result,
                    ucfirst($result) . ' campaign "' . (getCampaign($conn, $campaignID)['name'] ?? "#$campaignID") . '"', $campaignID);
            } else {
                setFlash('error', 'That campaign could not be updated.');
            }

            header('Location: ' . $returnURL);
            exit;
        }

        // close a campaign
        if ($action === 'close' && $campaignID) {
            if (closeCampaign($conn, $campaignID)) {
                setFlash('success', 'Campaign closed. It is no longer on the website or taking donations.');
                logActivity($conn, 'Campaign', 'closed',
                    'Closed campaign "' . (getCampaign($conn, $campaignID)['name'] ?? "#$campaignID") . '"', $campaignID);
            } else {
                setFlash('error', 'That campaign could not be closed. It may already be closed or archived.');
            }

            header('Location: ' . $returnURL);
            exit;
        }

        // upload photos
        if ($action === 'upload') {
            $campaign = $campaignID ? getCampaign($conn, $campaignID) : null;
            $files = normaliseUploadedFiles($_FILES['photos'] ?? []);

            if (!$campaign) {
                setFlash('error', 'That campaign no longer exists.');
            } elseif (!$files) {
                setFlash('error', 'Please choose at least one photo to upload.');
            } else {
                $result = addGalleryPhotos($conn, null, $campaignID, $files);
                $added = $result['added'] === 1 ? '1 photo' : $result['added'] . ' photos';

                if ($result['added']) {
                    logActivity($conn, 'Campaign', 'photos', "Uploaded $added to campaign \"{$campaign['name']}\"", $campaignID);
                }

                if (!$result['errors']) {
                    setFlash('success', $added . ' added.');
                } else {
                    $message = $result['added'] ? $added . ' added. ' : 'No photos were added. ';
                    setFlash('error', $message . implode(' ', $result['errors']));
                }
            }

            header('Location: ' . $returnURL);
            exit;
        }

        // delete a photo
        if ($action === 'deletePhoto') {
            $galleryItemID = ctype_digit($_POST['galleryItemID'] ?? '') ? (int) $_POST['galleryItemID'] : 0;
            $item = $galleryItemID ? getGalleryItem($conn, $galleryItemID) : null;

            // only campaign photos can be deleted from this page
            if ($item && $item['campaignID'] !== null && deleteGalleryPhoto($conn, $galleryItemID)) {
                setFlash('success', 'Photo deleted.');
                logActivity($conn, 'Campaign', 'photo deleted',
                    "Deleted photo #$galleryItemID from campaign \"" . (getCampaign($conn, (int) $item['campaignID'])['name'] ?? '#' . $item['campaignID']) . '"', $galleryItemID);
            } else {
                setFlash('error', 'That photo could not be deleted.');
            }

            header('Location: ' . $returnURL);
            exit;
        }

        // add or edit
        if ($action === 'save') {
            $formInput = [
                'name'        => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'goal'        => $_POST['goal'] ?? '',
                'status'      => $_POST['status'] ?? '',
            ];

            $existing = $campaignID ? getCampaign($conn, $campaignID) : null;
            if ($campaignID && !$existing) {
                $errors[] = 'That campaign no longer exists.';
            }

            $errors = array_merge($errors, validateCampaignInput($formInput));
            $photos = normaliseUploadedFiles($_FILES['photos'] ?? []);

            if (!$errors) {
                $newID = $existing ? null : addCampaign($conn, $formInput, (int) $account['accountID']);
                $saved = $existing ? updateCampaign($conn, $campaignID, $formInput) : $newID !== null;

                if ($saved) {
                    logActivity($conn, 'Campaign', $existing ? 'updated' : 'created',
                        ($existing ? 'Updated' : 'Created') . " campaign \"{$formInput['name']}\" (status {$formInput['status']}, goal R{$formInput['goal']})",
                        $newID ?? $campaignID);

                    // add the new photos once the campaign is saved
                    $photoResult = $photos ? addGalleryPhotos($conn, null, $newID ?? $campaignID, $photos) : null;
                    setFlash(...savedWithPhotosMessage(
                        '"' . $formInput['name'] . '" ' . ($existing ? 'updated' : 'added'),
                        $photoResult,
                        $existing ? '.' : '. You can upload its photos below.'
                    ));
                    // a new campaign opens straight away so its photos show
                    header('Location: ' . ($newID ? 'manageCampaigns.php?campaign=' . $newID : $returnURL));
                    exit;
                }

                $errors[] = 'Something went wrong saving the campaign. Please try again.';
            }

            // browsers cannot keep chosen files after the page reloads
            if ($errors && $photos) {
                $errors[] = 'Please choose your photos again.';
            }
        }
    }

    // page data

    $flash = takeFlash();

    // fetch the campaigns and counts
    $campaigns = getAllCampaigns($conn);
    $counts = getCampaignCounts($campaigns);

    // find one campaign by id
    $findCampaign = function (?int $id) use ($campaigns): ?array {
        foreach ($campaigns as $campaign) {
            if ($campaign['campaignID'] === $id) {
                return $campaign;
            }
        }
        return null;
    };

    // the campaign being edited
    $editingCampaign = isset($_GET['edit']) && ctype_digit($_GET['edit']) ? $findCampaign((int) $_GET['edit']) : null;

    // after a failed save keep what was typed and stay in edit mode
    if ($formInput !== null && !empty($_POST['campaignID'])) {
        $editingCampaign = $editingCampaign ?? $findCampaign((int) $_POST['campaignID']);
    }

    // goal as a plain number for the form
    $goalForForm = fn (float $goal) => rtrim(rtrim(number_format($goal, 2, '.', ''), '0'), '.');

    $form = $formInput ?? ($editingCampaign
        ? ['name' => $editingCampaign['name'], 'description' => $editingCampaign['description'],
           'goal' => $goalForForm($editingCampaign['goal']), 'status' => $editingCampaign['status']]
        : ['name' => '', 'description' => '', 'goal' => '', 'status' => 'draft']);

    // split the campaigns by status
    $current = array_filter($campaigns, fn ($c) => !$c['isArchived']);
    $activeCampaigns = array_values(array_filter($current, fn ($c) => $c['status'] === 'active'));
    $draftCampaigns = array_values(array_filter($current, fn ($c) => $c['status'] === 'draft'));
    $closedCampaigns = array_values(array_filter($current, fn ($c) => $c['status'] === 'closed'));
    $archivedCampaigns = array_values(array_filter($campaigns, fn ($c) => $c['isArchived']));

    $viewCampaign = $viewCampaignID ? $findCampaign($viewCampaignID) : null;

    $openDialog = $errors || $editingCampaign;

    $pageTitle = 'Campaigns';
    $activePage = 'campaigns';

    // open the shared dashboard layout
    include __DIR__ . '/components/dashboard.php';

    // what each status means for the website, shown under the campaign name
    $statusNote = fn (array $campaign) => $campaign['isArchived']
        ? 'Archived · hidden from the website'
        : match ($campaign['status']) {
            'active' => 'Active · on the website, taking donations',
            'draft'  => 'Draft · hidden from the website',
            default  => 'Closed · hidden from the website',
        };

    // everything the edit dialog needs to fill itself in
    $campaignJson = fn (array $campaign) => json_encode([
        'id'          => $campaign['campaignID'],
        'name'        => $campaign['name'],
        'description' => $campaign['description'],
        'goal'        => $goalForForm($campaign['goal']),
        'status'      => $campaign['status'],
    ], JSON_INVALID_UTF8_SUBSTITUTE);

    // raised so far against the goal
    $renderProgress = function (array $campaign) {
        ?>
        <div class="campaign-progress">
            <div class="campaign-progress-heading">
                <strong><?= htmlspecialchars(formatRand($campaign['raised'])) ?></strong>
                <span><?= $campaign['percentage'] ?>% of <?= htmlspecialchars(formatRand($campaign['goal'])) ?></span>
            </div>
            <div class="progress-track" role="progressbar" aria-label="<?= htmlspecialchars($campaign['name']) ?> progress"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $campaign['percentage'] ?>">
                <div class="progress-fill" style="width: <?= $campaign['percentage'] ?>%;"></div>
            </div>
        </div>
        <?php
    };

    // close, archive and restore buttons, each asks first in the confirmation popup
    $renderStatusButtons = function (array $campaign) {
        $data = 'data-id="' . $campaign['campaignID'] . '" data-name="' . htmlspecialchars($campaign['name']) . '" data-status="' . $campaign['status'] . '"';
        ?>
        <?php if ($campaign['status'] === 'active' && !$campaign['isArchived']) : ?>
            <button type="button" class="manage-action-button is-danger js-campaign-confirm" data-action="close" <?= $data ?>>Close</button>
        <?php endif; ?>
        <button type="button" class="manage-action-button js-campaign-confirm <?= $campaign['isArchived'] ? '' : 'is-danger' ?>"
                data-action="toggleArchive" data-archived="<?= $campaign['isArchived'] ? '1' : '0' ?>" <?= $data ?>>
            <?= $campaign['isArchived'] ? 'Restore' : 'Archive' ?>
        </button>
        <?php
    };

    // one campaign card
    $renderCampaignCard = function (array $campaign) use ($statusNote, $campaignJson, $renderProgress, $renderStatusButtons) {
        $detailsURL = 'manageCampaigns.php?campaign=' . $campaign['campaignID'];
        ?>
        <article class="manage-card campaign-manage-card campaign-status-<?= $campaign['status'] ?> <?= $campaign['isArchived'] ? 'is-archived' : '' ?>">

            <!-- cover photo -->
            <a class="manage-card-cover" href="<?= $detailsURL ?>" aria-label="Photos and donations for <?= htmlspecialchars($campaign['name']) ?>">
                <?php if ($campaign['coverImage']) : ?>
                    <img src="<?= htmlspecialchars(galleryImageSrc($campaign['coverImage'])) ?>" alt="" loading="lazy">
                <?php else : ?>
                    <span>No photo yet</span>
                <?php endif; ?>
            </a>

            <!-- name, status and description -->
            <div class="manage-card-title">
                <strong><?= htmlspecialchars($campaign['name']) ?></strong>
                <span><?= htmlspecialchars($statusNote($campaign)) ?></span>
            </div>

            <p class="manage-card-text" title="<?= htmlspecialchars($campaign['description']) ?>">
                <?= htmlspecialchars(mb_strimwidth($campaign['description'], 0, 140, '...')) ?>
            </p>

            <?php $renderProgress($campaign); ?>

            <!-- donations, photos and who added it -->
            <dl class="manage-card-details">
                <div>
                    <dt>Donations</dt>
                    <dd><?= $campaign['donations'] ?></dd>
                </div>
                <div>
                    <dt>Photos</dt>
                    <dd><?= $campaign['photoCount'] ?></dd>
                </div>
                <div>
                    <dt>Added by</dt>
                    <dd><?= htmlspecialchars($campaign['createdByName'] ?? 'Unknown') ?></dd>
                </div>
            </dl>

            <div class="manage-card-actions">
                <!-- edit, details, close and archive, edit works as a normal link without javascript -->
                <a class="manage-action-button campaign-edit" href="manageCampaigns.php?edit=<?= $campaign['campaignID'] ?>"
                   data-campaign="<?= htmlspecialchars($campaignJson($campaign)) ?>" aria-haspopup="dialog">Edit</a>
                <a class="manage-action-button" href="<?= $detailsURL ?>">Details</a>
                <?php $renderStatusButtons($campaign); ?>
            </div>

        </article>
        <?php
    };
?>

<div class="manage-page campaigns-page">

    <?php
    // success messages, save errors show inside the dialog instead
    $pageErrors = $errors;
    $errors = [];
    include __DIR__ . '/components/dashboardAlerts.php';
    $errors = $pageErrors;
    ?>

<?php if ($viewCampaignID) : ?>

    <!-- campaign view, one campaign with its photos and donations -->

    <div class="manage-toolbar">
        <a class="manage-back-link" href="manageCampaigns.php">All campaigns</a>
    </div>

    <!-- shown when the campaign is not found -->
    <?php if (!$viewCampaign) : ?>
        <p class="manage-empty">That campaign could not be found. <a href="manageCampaigns.php">Back to all campaigns</a>.</p>
    <?php else : ?>
        <!-- fetch its photos and donations -->
        <?php
        $photos = getCampaignPhotos($conn, $viewCampaign['campaignID']);
        $donations = getCampaignDonations($conn, $viewCampaign['campaignID']);
        ?>

        <!-- campaign summary -->
        <article class="manage-card campaign-manage-card event-summary campaign-status-<?= $viewCampaign['status'] ?> <?= $viewCampaign['isArchived'] ? 'is-archived' : '' ?>">
            <div class="manage-card-title">
                <strong><?= htmlspecialchars($viewCampaign['name']) ?></strong>
                <span><?= htmlspecialchars($statusNote($viewCampaign)) ?></span>
            </div>

            <p class="manage-card-text"><?= nl2br(htmlspecialchars($viewCampaign['description'])) ?></p>

            <?php $renderProgress($viewCampaign); ?>

            <dl class="manage-card-details">
                <div>
                    <dt>Donations</dt>
                    <dd><?= $viewCampaign['donations'] ?></dd>
                </div>
                <div>
                    <dt>Last 30 days</dt>
                    <dd><?= htmlspecialchars(formatRand($viewCampaign['raisedLast30Days'])) ?></dd>
                </div>
                <div>
                    <dt>Added by</dt>
                    <dd><?= htmlspecialchars($viewCampaign['createdByName'] ?? 'Unknown') ?></dd>
                </div>
            </dl>

            <div class="manage-card-actions">
                <a class="manage-action-button campaign-edit" href="manageCampaigns.php?campaign=<?= $viewCampaign['campaignID'] ?>&amp;edit=<?= $viewCampaign['campaignID'] ?>"
                   data-campaign="<?= htmlspecialchars($campaignJson($viewCampaign)) ?>" aria-haspopup="dialog">Edit details</a>
                <?php $renderStatusButtons($viewCampaign); ?>
            </div>
        </article>

        <!-- photos -->
        <section class="dashboard-panel manage-section" id="campaign-photos">
            <div class="panel-header">
                <div class="gallery-event-heading">
                    <h2>Photos</h2>
                    <span><?= count($photos) === 1 ? '1 photo' : count($photos) . ' photos' ?> &middot; the first photo is the campaign's image on the website</span>
                </div>
                <button type="button" class="form-button" id="gallery-upload-open" aria-haspopup="dialog" aria-controls="gallery-dialog">
                    Upload photos
                </button>
            </div>

            <div class="gallery-photo-grid">
                <?php if (!$photos) : ?>
                    <p class="manage-empty">No photos for this campaign yet. Until one is added, the website shows a placeholder image.</p>
                <?php endif; ?>

                <?php foreach ($photos as $index => $photo) : ?>
                    <figure class="gallery-photo">
                        <a href="<?= htmlspecialchars(galleryImageSrc($photo['image'])) ?>" target="_blank" rel="noopener"
                           aria-label="Open photo <?= $index + 1 ?> full size">
                            <img src="<?= htmlspecialchars(galleryImageSrc($photo['image'])) ?>" alt="" loading="lazy">
                        </a>
                        <?php if ($index === 0) : ?>
                            <span class="gallery-cover-badge" title="Used as the campaign's image on the website">Cover</span>
                        <?php endif; ?>
                        <form method="POST" action="<?= htmlspecialchars($returnURL) ?>"
                              onsubmit="return confirm('Delete this photo? This cannot be undone.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="deletePhoto">
                            <input type="hidden" name="galleryItemID" value="<?= (int) $photo['galleryItemID'] ?>">
                            <button type="submit" class="gallery-photo-delete" aria-label="Delete photo <?= $index + 1 ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
                                    <path fill="currentColor" d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-3 6h12l-1 12H7L6 9Zm4 2v8h1.5v-8H10Zm3.5 0v8H15v-8h-1.5Z"/>
                                </svg>
                            </button>
                        </form>
                    </figure>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- donations -->
        <section class="dashboard-panel manage-section" id="campaign-donations">
            <div class="panel-header">
                <div class="gallery-event-heading">
                    <h2>Donations</h2>
                    <span><?= count($donations) === 1 ? '1 donation' : count($donations) . ' donations' ?> &middot; newest first &middot; only paid donations count towards the total</span>
                </div>
            </div>

            <?php if (!$donations) : ?>
                <p class="manage-empty">No donations yet.<?= $viewCampaign['status'] === 'active' && !$viewCampaign['isArchived'] ? '' : ' Only active campaigns take donations.' ?></p>
            <?php else : ?>
                <div class="dashboard-table-wrap campaign-donations-table">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Donor</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Reference</th>
                                <th scope="col">Payment</th>
                                <th scope="col" class="is-amount">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donations as $donation) : ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('j M Y, H:i', strtotime($donation['donationDate']))) ?></td>
                                    <td>
                                        <?= htmlspecialchars(trim($donation['firstName'] . ' ' . $donation['lastName'])) ?>
                                        <?php if ($donation['isAnonymous']) : ?>
                                            <span class="snapshot-sub">Anonymous on the website</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($donation['email']) ?>"><?= htmlspecialchars($donation['email']) ?></a>
                                        <?php if (($donation['phoneNumber'] ?? '') !== '') : ?>
                                            <span class="snapshot-sub"><?= htmlspecialchars($donation['phoneNumber']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($donation['paymentReference']) ?></code></td>
                                    <td><span class="status-badge donation-status-<?= htmlspecialchars($donation['paymentStatus']) ?>"><?= htmlspecialchars(DONATION_STATUS_LABELS[$donation['paymentStatus']] ?? ucfirst($donation['paymentStatus'])) ?></span></td>
                                    <td class="is-amount"><?= htmlspecialchars(formatRand($donation['amount'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- upload dialog for this campaign's photos -->
        <dialog class="dashboard-dialog gallery-dialog" id="gallery-dialog" aria-labelledby="gallery-dialog-title">
            <form method="POST" action="<?= htmlspecialchars($returnURL) ?>" enctype="multipart/form-data" class="dashboard-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="campaignID" value="<?= $viewCampaign['campaignID'] ?>">

                <div class="dialog-header">
                    <h2 id="gallery-dialog-title">Upload photos</h2>
                    <button type="button" class="popover-icon-button" data-dialog-close aria-label="Close">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M18.3 5.7 16.9 4.3 12 9.2 7.1 4.3 5.7 5.7 10.6 12l-4.9 4.9 1.4 1.4 4.9-4.9 4.9 4.9 1.4-1.4-4.9-4.9z"/>
                        </svg>
                    </button>
                </div>

                <div class="dialog-body">
                    <p class="field-hint gallery-dialog-event"><?= htmlspecialchars($viewCampaign['name']) ?></p>

                    <?php
                    $uploaderID = 'gallery-photos';
                    $uploaderRequired = true;
                    include __DIR__ . '/components/galleryUploader.php';
                    ?>
                </div>

                <div class="dialog-footer">
                    <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                    <button type="submit" class="form-button" id="gallery-submit" disabled>Upload</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>

<?php else : ?>

    <!-- list view, every campaign -->

    <!-- summary cards -->
    <section class="dashboard-cards">
        <?php
        $cardTitle = 'Active';
        $cardValue = $counts['active'];
        $cardMeta = $counts['draft'] . ' draft, ' . $counts['closed'] . ' closed';
        $cardClass = 'events-upcoming-card';
        include __DIR__ . '/components/dashboardCard.php';

        $cardTitle = 'Raised';
        $cardValue = formatRand($counts['raised']);
        $cardMeta = formatRand($counts['raisedLast30Days']) . ' in the last 30 days';
        $cardClass = 'campaigns-raised-card';
        include __DIR__ . '/components/dashboardCard.php';

        $cardTitle = 'Donations';
        $cardValue = $counts['donations'];
        $cardMeta = $counts['funded'] === 1 ? '1 campaign has reached its goal' : $counts['funded'] . ' campaigns have reached their goal';
        $cardClass = 'campaigns-donations-card';
        include __DIR__ . '/components/dashboardCard.php';

        $cardTitle = 'Archived';
        $cardValue = $counts['archived'];
        $cardMeta = $showArchived ? 'Shown below' : 'Select to show';
        $cardClass = 'events-archived-card';
        $cardLink = $showArchived ? 'manageCampaigns.php' : 'manageCampaigns.php?showArchived=1#archived-campaigns';
        include __DIR__ . '/components/dashboardCard.php';
        ?>
    </section>

    <!-- active campaigns -->
    <section class="dashboard-panel manage-section" id="active-campaigns">
        <div class="panel-header">
            <div class="gallery-event-heading">
                <h2>Active Campaigns</h2>
                <span>On the website and taking donations</span>
            </div>
            <button type="button" class="form-button" id="campaign-add" aria-haspopup="dialog" aria-controls="campaign-dialog">
                Add campaign
            </button>
        </div>

        <div class="manage-grid">
            <?php if (!$activeCampaigns) : ?>
                <p class="manage-empty">No active campaigns. Use "Add campaign", or set a draft to Active when it is ready.</p>
            <?php endif; ?>
            <?php foreach ($activeCampaigns as $campaign) $renderCampaignCard($campaign); ?>
        </div>
    </section>

    <!-- drafts -->
    <section class="dashboard-panel manage-section" id="draft-campaigns">
        <div class="panel-header">
            <div class="gallery-event-heading">
                <h2>Drafts</h2>
                <span>Hidden from the website until set to Active</span>
            </div>
        </div>

        <div class="manage-grid">
            <?php if (!$draftCampaigns) : ?>
                <p class="manage-empty">No drafts.</p>
            <?php endif; ?>
            <?php foreach ($draftCampaigns as $campaign) $renderCampaignCard($campaign); ?>
        </div>
    </section>

    <!-- closed campaigns -->
    <section class="dashboard-panel manage-section" id="closed-campaigns">
        <div class="panel-header">
            <div class="gallery-event-heading">
                <h2>Closed Campaigns</h2>
                <span>Finished; hidden from the website, donations kept</span>
            </div>
        </div>

        <div class="manage-grid">
            <?php if (!$closedCampaigns) : ?>
                <p class="manage-empty">No closed campaigns yet.</p>
            <?php endif; ?>
            <?php foreach ($closedCampaigns as $campaign) $renderCampaignCard($campaign); ?>
        </div>
    </section>

    <!-- archived campaigns, only when asked -->
    <?php if ($showArchived) : ?>
        <section class="dashboard-panel manage-section" id="archived-campaigns">
            <div class="panel-header">
                <h2>Archived Campaigns</h2>
                <a class="panel-button" href="manageCampaigns.php">Hide archived</a>
            </div>

            <div class="manage-grid">
                <?php if (!$archivedCampaigns) : ?>
                    <p class="manage-empty">No archived campaigns.</p>
                <?php endif; ?>
                <?php foreach ($archivedCampaigns as $campaign) $renderCampaignCard($campaign); ?>
            </div>
        </section>
    <?php endif; ?>

<?php endif; ?>

    <!-- add and edit dialog, filled in by the server after a failed save or by the script when add or edit is clicked -->
    <dialog class="dashboard-dialog campaign-dialog" id="campaign-dialog" aria-labelledby="campaign-dialog-title"
            <?= $openDialog ? 'data-open-on-load="1"' : '' ?>>
        <form method="POST" action="<?= htmlspecialchars($returnURL) ?>" enctype="multipart/form-data" class="dashboard-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="campaignID" id="campaign-id" value="<?= $editingCampaign ? $editingCampaign['campaignID'] : '' ?>">

            <div class="dialog-header">
                <h2 id="campaign-dialog-title"><?= $editingCampaign ? 'Edit campaign' : 'Add a campaign' ?></h2>
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
                    <label for="campaign-name">Campaign name</label>
                    <input type="text" id="campaign-name" name="name" maxlength="255" required
                           value="<?= htmlspecialchars($form['name'] ?? '') ?>">
                </div>

                <div class="form-field">
                    <label for="campaign-goal">Fundraising goal <span class="field-hint">(in rand)</span></label>
                    <div class="campaign-goal-input">
                        <span aria-hidden="true">R</span>
                        <input type="text" id="campaign-goal" name="goal" inputmode="decimal" required
                               placeholder="25000" value="<?= htmlspecialchars($form['goal'] ?? '') ?>">
                    </div>
                </div>

                <!-- status -->
                <fieldset class="form-field event-undated campaign-status-options">
                    <legend>Status</legend>
                    <?php
                    $statusHints = [
                        'draft'  => 'Hidden from the website while you prepare it',
                        'active' => 'Shown on the website and taking donations',
                        'closed' => 'Finished: hidden from the website, donations kept',
                    ];
                    foreach ($statusHints as $value => $hint) :
                    ?>
                        <label class="event-undated-option">
                            <input type="radio" name="status" value="<?= $value ?>" id="campaign-status-<?= $value ?>"
                                   <?= ($form['status'] ?? 'draft') === $value ? 'checked' : '' ?>>
                            <span>
                                <strong><?= CAMPAIGN_STATUSES[$value] ?></strong>
                                <span class="field-hint"><?= $hint ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <div class="form-field">
                    <label for="campaign-description">Description <span class="field-hint">(shown on the website)</span></label>
                    <textarea id="campaign-description" name="description" rows="5" required
                              maxlength="<?= CAMPAIGN_DESCRIPTION_MAX ?>"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
                </div>

                <!-- photos -->
                <?php
                $uploaderID = 'campaign-photos';
                $uploaderHint = $editingCampaign
                    ? '(optional, added to the photos already there)'
                    : '(optional, the first photo is the cover on the website)';
                include __DIR__ . '/components/galleryUploader.php';
                ?>
            </div>

            <div class="dialog-footer">
                <button type="button" class="panel-button" data-dialog-close>Cancel</button>
                <button type="submit" class="form-button" id="campaign-submit"><?= $editingCampaign ? 'Save changes' : 'Add campaign' ?></button>
            </div>
        </form>
    </dialog>

    <!-- close, archive and restore confirmation, styled like the one on registrations.php -->
    <div class="modal fade registration-modal registration-confirm-modal" id="campaign-confirm"
         tabindex="-1" aria-labelledby="campaign-confirm-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="<?= htmlspecialchars($returnURL) ?>">

                <div class="modal-header">
                    <h2 class="modal-title" id="campaign-confirm-title"></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="confirm-message"></p>
                </div>

                <?= csrfField() ?>
                <input type="hidden" name="action" value="">
                <input type="hidden" name="campaignID" value="">

                <div class="modal-footer">
                    <button type="button" class="panel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="panel-button confirm-submit"></button>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
    // close buttons for both dialogs, clicking outside also closes them
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
        const dialog = document.getElementById('campaign-dialog');
        const field = (id) => document.getElementById(id);

        // fill in the dialog for a campaign or a new one and open it
        const open = (campaign) => {
            dialog.querySelector('.dialog-errors').hidden = true;
            dialog.querySelector('.gallery-uploader').galleryReset();

            field('campaign-id').value = campaign ? campaign.id : '';
            field('campaign-name').value = campaign ? campaign.name : '';
            field('campaign-goal').value = campaign ? campaign.goal : '';
            field('campaign-status-' + (campaign ? campaign.status : 'draft')).checked = true;
            field('campaign-description').value = campaign ? campaign.description : '';

            field('campaign-dialog-title').textContent = campaign ? 'Edit campaign' : 'Add a campaign';
            field('campaign-submit').textContent = campaign ? 'Save changes' : 'Add campaign';

            dialog.showModal();
            field('campaign-name').focus();
        };

        // add and edit buttons
        field('campaign-add')?.addEventListener('click', () => open(null));

        document.querySelectorAll('.campaign-edit').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                open(JSON.parse(link.dataset.campaign));
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

    // close, archive and restore confirmation, waits for bootstrap to load
    document.addEventListener('DOMContentLoaded', () => {
        const element = document.getElementById('campaign-confirm');
        const modal = bootstrap.Modal.getOrCreateInstance(element);

        // wording for each action
        const texts = {
            close: (name) => ['Close this campaign?',
                name + ' will be removed from the website and stop taking donations. Its donations are kept. You can reopen it later by editing its status.',
                'Close campaign', true],
            archive: (name) => ['Archive this campaign?',
                name + ' will be hidden from the website and stop taking donations. Its donations are kept, and it can be restored later.',
                'Archive', true],
            restore: (name, status) => ['Restore this campaign?',
                name + ' will move back to the ' + status + ' campaigns.'
                    + (status === 'active' ? ' It will show on the website and take donations again.' : ''),
                'Restore', false],
        };

        // fill in the confirmation and open it
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.js-campaign-confirm');
            if (!button) return;

            const kind = button.dataset.action === 'close' ? 'close' : (button.dataset.archived === '1' ? 'restore' : 'archive');
            const [title, message, label, danger] = texts[kind](button.dataset.name, button.dataset.status);

            element.querySelector('.modal-title').textContent = title;
            element.querySelector('.confirm-message').textContent = message;

            const submit = element.querySelector('.confirm-submit');
            submit.textContent = label;
            submit.classList.toggle('button-danger', danger);
            submit.classList.toggle('button-primary', !danger);

            element.querySelector('input[name="action"]').value = button.dataset.action;
            element.querySelector('input[name="campaignID"]').value = button.dataset.id;

            modal.show();
        });
    });

    // upload dialog on the campaign view, the photo picker is set up by components/galleryUploader.php
    document.getElementById('gallery-upload-open')?.addEventListener('click', () => {
        const dialog = document.getElementById('gallery-dialog');
        dialog.querySelector('.gallery-uploader').galleryReset();
        dialog.showModal();
    });
</script>

<!-- page end, closes the layout opened by components/dashboard.php -->
            </main>
        </div>
    </div>
</body>
</html>