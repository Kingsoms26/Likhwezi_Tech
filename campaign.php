<?php
    // campaign.php is the campaigns page
    // contains the campaign cards, the view all page with search and filters, and the donation form that sends donors to PayFast
    session_start();
    require_once __DIR__ . '/includes/helpers/cache.php';
    require_once __DIR__ . '/includes/helpers/payfast.php';
    require_once __DIR__ . '/includes/helpers/donations.php';

    $dbAvailable = false;
    $donationError = '';
    $donationErrors = [];
    $donationInput = [];
    $donationCampaignID = 0;

    // token that protects the donation form
    if (empty($_SESSION['donationToken'])) {
        $_SESSION['donationToken'] = bin2hex(random_bytes(32));
    }

    // show the newest campaigns, view all lists every active campaign
    $campaignPreviewLimit = 6;
    $showAllCampaigns = isset($_GET['view']) && $_GET['view'] === 'all';
    $campaigns = [];

    // search, filter and sort options, only used on the view all page
    $sortOptions = [
        'newest'    => 'Newest first',
        'oldest'    => 'Oldest first',
        'name_asc'  => 'Name (A-Z)',
        'name_desc' => 'Name (Z-A)',
        'raised'    => 'Most raised',
        'goal_high' => 'Highest goal',
        'goal_low'  => 'Lowest goal',
        'progress'  => 'Closest to goal',
    ];
    $progressOptions = [
        'all'         => 'All campaigns',
        'in_progress' => 'Still raising',
        'funded'      => 'Goal reached',
    ];

    // read the chosen search, filter and sort
    $searchTerm = $showAllCampaigns ? trim((string) ($_GET['search'] ?? '')) : '';
    $sortKey = $showAllCampaigns && isset($sortOptions[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'newest';
    $progressKey = $showAllCampaigns && isset($progressOptions[$_GET['progress'] ?? '']) ? $_GET['progress'] : 'all';
    $activeFilterCount = ($progressKey !== 'all' ? 1 : 0) + ($sortKey !== 'newest' ? 1 : 0);
    $filtersActive = $searchTerm !== '' || $activeFilterCount > 0;

    // fetch the active campaigns, cached for 5 minutes so most visits skip the database
    $campaignRows = cached('campaigns_active', 300, function () {
        $conn = db();
        if (!$conn) {
            return null;
        }

        $sql = "SELECT
                c.campaignID,
                c.name,
                c.description,
                c.goal,
                COALESCE((
                    SELECT gi.image
                    FROM GalleryItem gi
                    WHERE gi.campaignID = c.campaignID
                    ORDER BY gi.galleryItemID ASC
                    LIMIT 1
                ), 'assets/images/placeholder.webp') AS campaignImage,
                COALESCE((
                    SELECT SUM(d.amount)
                    FROM Donation d
                    WHERE d.campaignID = c.campaignID AND d.paymentStatus = 'complete'
                ), 0) AS raised
            FROM Campaign c
            WHERE c.isArchived = FALSE
              AND c.status = 'active'
            ORDER BY c.campaignID DESC
        ";

        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : null;
    });

    if ($campaignRows !== null) {
        $dbAvailable = true;
        $campaigns = $campaignRows;
    }

    // temporary example campaigns shown when the database has none
    if (empty($campaigns)) {
        $campaigns = [
            ['campaignID' => 8, 'name' => 'Laptops for Learners', 'description' => 'Providing refurbished laptops to high school learners in rural areas.', 'goal' => 50000, 'raised' => 18250, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 7, 'name' => 'Coding Club Kits', 'description' => 'Starter electronics and robotics kits for after-school coding clubs.', 'goal' => 12000, 'raised' => 12000, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 6, 'name' => 'Community Wi-Fi Hotspot', 'description' => 'Setting up free internet access at the local community centre.', 'goal' => 30000, 'raised' => 4500, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 5, 'name' => 'Girls in Tech Bootcamp', 'description' => 'A week-long bootcamp introducing young women to careers in technology.', 'goal' => 20000, 'raised' => 15800, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 4, 'name' => 'Cyber Safety Workshops', 'description' => 'Teaching learners and parents how to stay safe online.', 'goal' => 8000, 'raised' => 9200, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 3, 'name' => 'School Computer Lab Upgrade', 'description' => 'Replacing outdated computers in a township school lab.', 'goal' => 75000, 'raised' => 31000, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 2, 'name' => 'Donate a Scissor', 'description' => 'Stationery and craft supplies for early childhood development centres.', 'goal' => 1000, 'raised' => 250, 'campaignImage' => 'assets/images/placeholder.webp'],
            ['campaignID' => 1, 'name' => 'Teacher Digital Training', 'description' => 'Upskilling teachers to use digital tools in the classroom.', 'goal' => 15000, 'raised' => 2100, 'campaignImage' => 'assets/images/placeholder.webp'],
        ];
    }

    // count the campaigns and look them up by id
    $totalCampaigns = count($campaigns);
    $campaignsByID = array_column($campaigns, null, 'campaignID');

    // view all applies the search and filter, otherwise only the first few show
    if ($showAllCampaigns) {
        $campaigns = array_values(array_filter($campaigns, function ($campaign) use ($searchTerm, $progressKey) {
            if ($searchTerm !== ''
                && stripos($campaign['name'], $searchTerm) === false
                && stripos($campaign['description'], $searchTerm) === false) {
                return false;
            }

            $isFunded = (float) $campaign['raised'] >= (float) $campaign['goal'];
            if ($progressKey === 'funded') {
                return $isFunded;
            }
            if ($progressKey === 'in_progress') {
                return !$isFunded;
            }
            return true;
        }));

        // sort the campaigns
        usort($campaigns, function ($a, $b) use ($sortKey) {
            $progressA = (float) $a['goal'] > 0 ? (float) $a['raised'] / (float) $a['goal'] : 0;
            $progressB = (float) $b['goal'] > 0 ? (float) $b['raised'] / (float) $b['goal'] : 0;

            switch ($sortKey) {
                case 'oldest':    $cmp = $a['campaignID'] <=> $b['campaignID']; break;
                case 'name_asc':  $cmp = strcasecmp($a['name'], $b['name']); break;
                case 'name_desc': $cmp = strcasecmp($b['name'], $a['name']); break;
                case 'raised':    $cmp = (float) $b['raised'] <=> (float) $a['raised']; break;
                case 'goal_high': $cmp = (float) $b['goal'] <=> (float) $a['goal']; break;
                case 'goal_low':  $cmp = (float) $a['goal'] <=> (float) $b['goal']; break;
                case 'progress':  $cmp = $progressB <=> $progressA; break;
                default:          $cmp = 0;
            }

            // newest first when nothing else decides
            return $cmp !== 0 ? $cmp : $b['campaignID'] <=> $a['campaignID'];
        });
    } else {
        $campaigns = array_slice($campaigns, 0, $campaignPreviewLimit);
    }

    // donate, save the donation as pending then send the donor to PayFast to pay
    // it only counts once PayFast confirms the payment through payfast-notify.php
    $payfastCheckout = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donation_submit'])) {
        $donationInput = $_POST;
        $donationCampaignID = (int) filter_input(INPUT_POST, 'campaignID', FILTER_VALIDATE_INT);
        $conn = db();

        if (!hash_equals($_SESSION['donationToken'], (string) ($_POST['donationToken'] ?? ''))) {
            $donationError = 'Your session expired. Please try again.';
        } elseif (!$conn || !payfastConfigured()) {
            $donationError = 'The donation service is currently unavailable. Please try again later.';
        } elseif ($donationCampaignID < 1) {
            $donationError = 'Please choose a valid campaign before donating.';
        } elseif (!($donationErrors = validateDonationInput($donationInput))) {
            $donation = createPendingDonation($conn, $donationCampaignID, $donationInput);

            if ($donation) {
                // lets only this visitor see their donation's result when PayFast sends them back
                $_SESSION['donationRefs'][] = $donation['reference'];
                $payfastCheckout = payfastCheckoutFields($donation);
            } else {
                $donationError = 'This campaign is no longer accepting donations.';
            }
        }
    }

    // show the page that sends the donor to PayFast
    if ($payfastCheckout) {
        include __DIR__ . '/includes/components/payfastRedirect.php';
        exit;
    }

    // back from PayFast, either paid, still being confirmed or cancelled
    $donationResult = null;
    $resultRef = (string) ($_GET['ref'] ?? '');

    if (isset($_GET['donation']) && in_array($resultRef, $_SESSION['donationRefs'] ?? [], true) && ($conn = db())) {
        if ($_GET['donation'] === 'cancelled') {
            setDonationPaymentStatus($conn, $resultRef, 'cancelled');
        }
        $donationResult = getDonationByReference($conn, $resultRef);
    }

    // reopen the donation form with what was typed when it had a problem
    $reopenDonation = $donationError !== '' || $donationErrors;
    $donationCampaign = $campaignsByID[$donationCampaignID] ?? null;

    // error style and message for a donation field that failed
    function donation_field_class(array $errors, string $field): string {
        return isset($errors[$field]) ? ' is-invalid' : '';
    }

    function donation_field_error(array $errors, string $field): string {
        return isset($errors[$field])
            ? '<div class="invalid-feedback d-block" id="' . $field . 'Error">' . htmlspecialchars($errors[$field]) . '</div>'
            : '';
    }

    // link a field to its error message for screen readers
    function donation_field_describedby(array $errors, string $field): string {
        return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $field . 'Error"' : '';
    }

    // what was typed in a field
    function donation_value(array $input, string $field): string {
        return htmlspecialchars((string) ($input[$field] ?? ''));
    }
?>

<!DOCTYPE html>
<html lang="en">
    <?php $pageTitle = "Campaigns"; ?>
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero hero-text-only">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Campaigns</h1>
                    <p>Explore our various campaigns and initiatives.</p>
                </div>
            </div>
        </section>

        <hr>

        <?php if ($showAllCampaigns): ?>
            <!-- filter and sort (view all only) -->
            <form method="GET" action="campaign.php" id="campaignFilterForm" class="campaign-filters">
                <input type="hidden" name="view" value="all">

                <div class="campaign-filter-search">
                    <label for="campaignSearch" class="visually-hidden">Search campaigns</label>
                    <input type="search" class="form-control" id="campaignSearch" name="search" placeholder="Search campaigns..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>

                <div class="campaign-filter-actions">
                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#campaignFilterModal">
                        Sort &amp; Filter
                        <?php if ($activeFilterCount > 0): ?>
                            <span class="badge rounded-pill bg-primary ms-1"><?= $activeFilterCount ?></span>
                        <?php endif; ?>
                    </button>
                    <?php if ($filtersActive): ?>
                        <a href="campaign.php?view=all" class="btn btn-link">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- how many campaigns are showing -->
            <p class="campaign-filter-count">Showing <?= count($campaigns) ?> of <?= $totalCampaigns ?> campaigns</p>
        <?php endif; ?>

        <!-- campaign section -->
        <h2 class="section-title pt-4 mb-0"><?= $showAllCampaigns ? 'All Campaigns' : 'Current Campaigns' ?></h2>
        <section class="campaign-section">

            <!-- shown when there are no campaigns -->
            <?php if (empty($campaigns)): ?>

                <p class="campaign-empty">
                    <?php if ($showAllCampaigns && $totalCampaigns > 0): ?>
                        No campaigns match your search. <a href="campaign.php?view=all">Clear filters</a>
                    <?php else: ?>
                        No campaigns available at the moment.
                    <?php endif; ?>
                </p>

            <?php else: ?>

                <?php foreach ($campaigns as $campaign): ?>

                    <!-- work out the image and progress for each card -->
                    <?php
                    $campaignImage = !empty($campaign['campaignImage']) ? $campaign['campaignImage'] : 'assets/images/placeholder.webp';
                    $raised = isset($campaign['raised']) ? (float) $campaign['raised'] : 0;
                    $goal = (float) $campaign['goal'];
                    $percent = $goal > 0 ? min(100, ($raised / $goal) * 100) : 0;
                    ?>

                    <!-- campaign card, the title button covers the whole card so it opens with a click, the keyboard or a screen reader -->
                    <article class="campaign-card<?= $percent >= 100 ? ' is-funded' : '' ?>"
                        data-campaign-id="<?= (int) $campaign['campaignID'] ?>"
                        data-name="<?= htmlspecialchars($campaign['name'], ENT_QUOTES) ?>"
                        data-image="<?= htmlspecialchars($campaignImage, ENT_QUOTES) ?>"
                        data-description="<?= htmlspecialchars($campaign['description'], ENT_QUOTES) ?>"
                        data-raised="R<?= number_format($raised, 2) ?>"
                        data-goal="R<?= number_format($goal, 2) ?>"
                        data-percent="<?= round($percent) ?>">
                        <div class="campaign-image">
                            <img src="<?= htmlspecialchars($campaignImage) ?>" alt="<?= htmlspecialchars($campaign['name']) ?>" loading="lazy">
                        </div>
                        <div class="campaign-content">
                            <h3>
                                <button type="button" class="campaign-card-button" aria-haspopup="dialog">
                                    <?= htmlspecialchars($campaign['name']) ?>
                                </button>
                            </h3>
                            <p><?= htmlspecialchars($campaign['description']) ?></p>
                            <div class="campaign-progress">
                                <p class="campaign-stats"><strong>R<?= number_format($raised, 2) ?></strong> raised of R<?= number_format($goal, 2) ?></p>
                                <div class="progress" role="progressbar" aria-label="Campaign progress" aria-valuenow="<?= round($percent) ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

            <?php endif; ?>

        </section>

        <!-- view all or show fewer button -->
        <?php if ($showAllCampaigns || $totalCampaigns > $campaignPreviewLimit): ?>
            <div class="campaign-view-all">
                <?php if ($showAllCampaigns): ?>
                    <a href="campaign.php" class="btn btn-outline-dark">Show Fewer Campaigns</a>
                <?php else: ?>
                    <a href="campaign.php?view=all" class="btn btn-primary">View All Campaigns (<?= $totalCampaigns ?>)</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>

        <?php if ($showAllCampaigns): ?>
            <!-- sort and filter popup -->
            <div class="modal fade" id="campaignFilterModal" tabindex="-1" aria-labelledby="campaignFilterModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title" id="campaignFilterModalTitle">Sort &amp; Filter</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="campaignProgress" class="form-label">Status</label>
                                <select class="form-select" id="campaignProgress" name="progress" form="campaignFilterForm">
                                    <?php foreach ($progressOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $value === $progressKey ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="campaignSort" class="form-label">Sort by</label>
                                <select class="form-select" id="campaignSort" name="sort" form="campaignFilterForm">
                                    <?php foreach ($sortOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $value === $sortKey ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="campaign.php?<?= htmlspecialchars(http_build_query(['view' => 'all', 'search' => $searchTerm])) ?>" class="btn btn-outline-dark">Clear</a>
                            <button type="submit" class="btn btn-primary" form="campaignFilterForm">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- campaign details popup -->
        <div class="modal fade campaign-modal" id="campaignModal" tabindex="-1" aria-labelledby="campaignModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="campaignModalTitle">Campaign Details</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="campaign-modal-layout">
                            <div class="campaign-modal-image-wrap">
                                <img id="campaignModalImage" src="assets/images/placeholder.webp" alt="Campaign image" class="campaign-modal-image">
                            </div>
                            <div class="campaign-modal-details">
                                <div class="campaign-progress">
                                    <p id="campaignModalStats" class="campaign-stats"></p>
                                    <div class="progress" id="campaignModalProgress" role="progressbar" aria-label="Campaign progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: 0%"></div>
                                    </div>
                                </div>
                                <p id="campaignModalDescription" class="campaign-modal-description">More information about the selected campaign will be displayed here.</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="openDonationModal" class="btn btn-primary">Make your Donation</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- donation popup -->
        <?php
        $selectedPreset = (string) ($donationInput['presetAmount'] ?? '');
        $summaryPercent = $donationCampaign && (float) $donationCampaign['goal'] > 0
            ? min(100, (float) $donationCampaign['raised'] / (float) $donationCampaign['goal'] * 100)
            : 0;
        ?>
        <div class="modal fade donation-modal" id="donationModal" tabindex="-1" aria-labelledby="donationModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="donationModalTitle"><?= $donationCampaign ? htmlspecialchars($donationCampaign['name']) : 'Donate' ?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="campaign.php<?= $showAllCampaigns ? '?' . htmlspecialchars(http_build_query(['view' => 'all', 'search' => $searchTerm, 'progress' => $progressKey, 'sort' => $sortKey])) : '' ?>" novalidate>
                        <div class="modal-body">
                            <input type="hidden" name="campaignID" id="campaignID" value="<?= $donationCampaignID ?: '' ?>">
                            <input type="hidden" name="donationToken" value="<?= htmlspecialchars($_SESSION['donationToken']) ?>">

                            <!-- error message -->
                            <?php if ($donationError !== ''): ?>
                                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($donationError) ?></div>
                            <?php endif; ?>

                            <!-- the chosen campaign's image and progress, filled in when a campaign is opened -->
                            <div class="donation-summary">
                                <img id="donationSummaryImage" src="<?= htmlspecialchars($donationCampaign['campaignImage'] ?? 'assets/images/placeholder.webp') ?>" alt="">
                                <div class="campaign-progress">
                                    <p id="donationSummaryStats" class="campaign-stats"><?php if ($donationCampaign): ?><strong>R<?= number_format((float) $donationCampaign['raised'], 2) ?></strong> raised of R<?= number_format((float) $donationCampaign['goal'], 2) ?><?php endif; ?></p>
                                    <div class="progress" id="donationSummaryProgress" aria-hidden="true">
                                        <div class="progress-bar" style="width: <?= $summaryPercent ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- amount -->
                            <fieldset class="mb-4">
                                <legend class="form-label">Choose an amount</legend>
                                <div class="donation-amounts<?= isset($donationErrors['amount']) ? ' is-invalid' : '' ?>">
                                    <?php foreach (DONATION_PRESETS as $preset): ?>
                                        <input class="btn-check" type="radio" name="presetAmount" id="amount<?= $preset ?>" value="<?= $preset ?>" <?= $selectedPreset === (string) $preset ? 'checked' : '' ?>>
                                        <label class="donation-amount" for="amount<?= $preset ?>">R<?= $preset ?></label>
                                    <?php endforeach; ?>
                                    <input class="btn-check" type="radio" name="presetAmount" id="amountCustom" value="custom" <?= $selectedPreset === 'custom' ? 'checked' : '' ?>>
                                    <label class="donation-amount" for="amountCustom">Custom</label>
                                </div>
                                <div class="input-group mt-2<?= $selectedPreset === 'custom' ? '' : ' d-none' ?>" id="customAmountGroup">
                                    <span class="input-group-text">R</span>
                                    <input type="number" class="form-control<?= donation_field_class($donationErrors, 'amount') ?>" id="customAmount" name="customAmount" min="<?= DONATION_MIN ?>" max="<?= DONATION_MAX ?>" step="0.01" inputmode="decimal" placeholder="Enter your own amount" aria-label="Enter your own amount" value="<?= donation_value($donationInput, 'customAmount') ?>" <?= $selectedPreset === 'custom' ? '' : 'disabled' ?>>
                                </div>
                                <div class="invalid-feedback" id="amountError"><?= htmlspecialchars($donationErrors['amount'] ?? 'Please select or enter a donation amount greater than zero.') ?></div>
                            </fieldset>

                            <!-- donor details -->
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="firstName" class="form-label">First name</label>
                                    <input type="text" class="form-control<?= donation_field_class($donationErrors, 'firstName') ?>" id="firstName" name="firstName" maxlength="100" autocomplete="given-name" required value="<?= donation_value($donationInput, 'firstName') ?>"<?= donation_field_describedby($donationErrors, 'firstName') ?>>
                                    <?= donation_field_error($donationErrors, 'firstName') ?>
                                </div>

                                <div class="col-sm-6">
                                    <label for="lastName" class="form-label">Last name</label>
                                    <input type="text" class="form-control<?= donation_field_class($donationErrors, 'lastName') ?>" id="lastName" name="lastName" maxlength="100" autocomplete="family-name" required value="<?= donation_value($donationInput, 'lastName') ?>"<?= donation_field_describedby($donationErrors, 'lastName') ?>>
                                    <?= donation_field_error($donationErrors, 'lastName') ?>
                                </div>

                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control<?= donation_field_class($donationErrors, 'email') ?>" id="email" name="email" maxlength="100" autocomplete="email" required value="<?= donation_value($donationInput, 'email') ?>"<?= donation_field_describedby($donationErrors, 'email') ?>>
                                    <?= donation_field_error($donationErrors, 'email') ?>
                                </div>

                                <div class="col-12">
                                    <label for="phoneNumber" class="form-label">Phone number</label>
                                    <input type="tel" class="form-control<?= donation_field_class($donationErrors, 'phoneNumber') ?>" id="phoneNumber" name="phoneNumber" maxlength="20" autocomplete="tel" value="<?= donation_value($donationInput, 'phoneNumber') ?>"<?= donation_field_describedby($donationErrors, 'phoneNumber') ?>>
                                    <?= donation_field_error($donationErrors, 'phoneNumber') ?>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isAnonymous" name="isAnonymous" value="1" aria-describedby="isAnonymousHelp" <?= !empty($donationInput['isAnonymous']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="isAnonymous">Donate anonymously</label>
                                        <div class="form-text" id="isAnonymousHelp">We'll still email your receipt, but won't mention your name publicly.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <p class="donation-secure"><i class="bi bi-lock-fill" aria-hidden="true"></i> You'll pay securely on PayFast.</p>
                            <button type="button" class="btn btn-outline-dark px-10" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="donation_submit" value="1" class="btn btn-primary">Donate now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($donationResult): ?>
            <?php
            $resultStatus = $donationResult['paymentStatus'];
            $resultAmount = 'R' . number_format($donationResult['amount'], 2);
            $resultCampaign = htmlspecialchars($donationResult['campaignName']);
            // try again reopens the form from the campaign's card so the card has to be on this page
            $canRetry = in_array($resultStatus, ['cancelled', 'failed'], true)
                && in_array($donationResult['campaignID'], array_map('intval', array_column($campaigns, 'campaignID')), true);
            ?>
            <!-- donation result, shown when PayFast sends the donor back -->
            <div class="modal fade" id="donationResultModal" tabindex="-1" aria-labelledby="donationResultTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body donation-result donation-result-<?= htmlspecialchars($resultStatus) ?>">
                            <?php if ($resultStatus === 'complete'): ?>
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                <h2 id="donationResultTitle">Thank you</h2>
                                <p>Thank you, your donation has been recorded successfully.</p>
                                <p class="donation-result-detail"><?= $resultAmount ?> to <?= $resultCampaign ?> &middot; Ref <?= htmlspecialchars($donationResult['reference']) ?></p>
                            <?php elseif ($resultStatus === 'pending'): ?>
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                <h2 id="donationResultTitle">Thank you</h2>
                                <p>PayFast is confirming your payment of <?= $resultAmount ?> to <?= $resultCampaign ?>. It will show on the campaign once confirmed, usually within a minute.</p>
                                <p class="donation-result-detail">Ref <?= htmlspecialchars($donationResult['reference']) ?></p>
                            <?php else: ?>
                                <i class="bi bi-x-circle" aria-hidden="true"></i>
                                <h2 id="donationResultTitle">Donation not completed</h2>
                                <p>No payment was taken for your <?= $resultAmount ?> donation to <?= $resultCampaign ?>.</p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <?php if ($canRetry): ?>
                                <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="retryDonation" data-campaign-id="<?= (int) $donationResult['campaignID'] ?>">Try again</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <script>
            // the popups and donation form
            const campaignModalElement = document.getElementById('campaignModal');
            const donationModalElement = document.getElementById('donationModal');
            const donationForm = donationModalElement.querySelector('form');
            const amountChoices = donationModalElement.querySelector('.donation-amounts');
            const campaignModal = new bootstrap.Modal(campaignModalElement);
            const donationModal = new bootstrap.Modal(donationModalElement);
            const campaignIdInput = document.getElementById('campaignID');
            const customAmountGroup = document.getElementById('customAmountGroup');
            const customAmountInput = document.getElementById('customAmount');

            // every campaign card by id so the donation form can be filled for any of them
            const campaignCards = {};
            document.querySelectorAll('.campaign-card').forEach(function(card) {
                campaignCards[card.dataset.campaignId] = card;
            });

            // amount raised of the goal, with the raised amount in bold like the cards
            function fillStats(element, card) {
                const raised = document.createElement('strong');
                raised.textContent = card.dataset.raised;
                element.replaceChildren(raised, ' raised of ' + card.dataset.goal);
            }

            // fill a progress bar
            function fillProgress(progress, percent) {
                progress.querySelector('.progress-bar').style.width = percent + '%';
                progress.setAttribute('aria-valuenow', percent);
            }

            // fill the details and donation popups with one campaign
            function selectCampaign(card) {
                const campaignName = card.dataset.name || 'Campaign Details';
                const image = card.dataset.image || 'assets/images/placeholder.webp';
                const percent = card.dataset.percent || 0;
                const isFunded = card.classList.contains('is-funded');

                document.getElementById('campaignModalTitle').textContent = campaignName;
                document.getElementById('donationModalTitle').textContent = campaignName;
                document.getElementById('campaignModalImage').src = image;
                document.getElementById('campaignModalImage').alt = campaignName;
                document.getElementById('campaignModalDescription').textContent = card.dataset.description || 'More information about the selected campaign will be displayed here.';
                document.getElementById('donationSummaryImage').src = image;
                fillStats(document.getElementById('campaignModalStats'), card);
                fillStats(document.getElementById('donationSummaryStats'), card);
                fillProgress(document.getElementById('campaignModalProgress'), percent);
                fillProgress(document.getElementById('donationSummaryProgress'), percent);

                campaignModalElement.classList.toggle('is-funded', isFunded);
                donationModalElement.classList.toggle('is-funded', isFunded);
                campaignIdInput.value = card.dataset.campaignId || '';
            }

            // show one popup after another has finished closing so they never overlap
            function switchModal(fromElement, fromModal, toModal) {
                fromElement.addEventListener('hidden.bs.modal', function() {
                    toModal.show();
                }, { once: true });
                fromModal.hide();
            }

            // clicking a card opens its details
            Object.values(campaignCards).forEach(function(card) {
                card.addEventListener('click', function() {
                    selectCampaign(card);
                    campaignModal.show();
                });
            });

            // make your donation opens the donation form
            document.getElementById('openDonationModal').addEventListener('click', function() {
                if (!campaignIdInput.value) {
                    alert('Please choose a campaign first.');
                    return;
                }

                switchModal(campaignModalElement, campaignModal, donationModal);
            });

            // show the custom amount box only when custom is chosen
            document.querySelectorAll('input[name="presetAmount"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    const isCustom = this.value === 'custom';
                    customAmountInput.disabled = !isCustom;
                    customAmountGroup.classList.toggle('d-none', !isCustom);
                    amountChoices.classList.remove('is-invalid');
                    if (isCustom) {
                        customAmountInput.focus();
                    } else {
                        customAmountInput.value = '';
                    }
                });
            });

            // check the amount and required fields before going to PayFast
            donationForm.addEventListener('submit', function(event) {
                const chosen = donationForm.querySelector('input[name="presetAmount"]:checked');
                const amountMissing = !chosen || (chosen.value === 'custom' && !customAmountInput.checkValidity());

                amountChoices.classList.toggle('is-invalid', amountMissing);
                if (amountMissing || !donationForm.checkValidity()) {
                    event.preventDefault();
                    donationForm.classList.add('was-validated');
                    (amountMissing ? amountChoices.querySelector('.donation-amount') : donationForm.querySelector(':invalid')).focus();
                    return;
                }

                // stop a double click from creating two donations
                const submitButton = donationForm.querySelector('[name="donation_submit"]');
                submitButton.disabled = true;
                donationForm.insertAdjacentHTML('beforeend', '<input type="hidden" name="donation_submit" value="1">');
            });

            // reopen the donation form when it had a problem
            <?php if ($reopenDonation): ?>
                if (campaignCards['<?= (int) $donationCampaignID ?>']) {
                    selectCampaign(campaignCards['<?= (int) $donationCampaignID ?>']);
                }
                donationModal.show();
            <?php endif; ?>

            // show the donation result
            <?php if ($donationResult): ?>
                const resultModalElement = document.getElementById('donationResultModal');
                const resultModal = new bootstrap.Modal(resultModalElement);
                resultModal.show();

                // clean up the address so refreshing does not show the message again
                history.replaceState(null, '', location.pathname);

                // try again reopens the donation form
                const retryButton = document.getElementById('retryDonation');
                if (retryButton) {
                    retryButton.addEventListener('click', function() {
                        selectCampaign(campaignCards[retryButton.dataset.campaignId]);
                        switchModal(resultModalElement, resultModal, donationModal);
                    });
                }
            <?php endif; ?>
        </script>
    </body>
</html>
