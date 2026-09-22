<?php
    session_start();
    include __DIR__ . '/tools/dbConnection.php';

    $dbAvailable = false;
    $donationError = '';
    $donationSuccess = '';

    // Show a preview of the newest campaigns; ?view=all lists every active campaign.
    $campaignPreviewLimit = 6;
    $showAllCampaigns = isset($_GET['view']) && $_GET['view'] === 'all';
    $campaigns = [];

    // Search, filter and sort options (only shown and applied on the "view all" page).
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

    $searchTerm = $showAllCampaigns ? trim((string) ($_GET['search'] ?? '')) : '';
    $sortKey = $showAllCampaigns && isset($sortOptions[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'newest';
    $progressKey = $showAllCampaigns && isset($progressOptions[$_GET['progress'] ?? '']) ? $_GET['progress'] : 'all';
    $activeFilterCount = ($progressKey !== 'all' ? 1 : 0) + ($sortKey !== 'newest' ? 1 : 0);
    $filtersActive = $searchTerm !== '' || $activeFilterCount > 0;

    if ($conn && !$conn->connect_error) {
        $dbAvailable = true;

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
                ), 'images/placeholder.webp') AS campaignImage,
                COALESCE((
                    SELECT SUM(d.amount)
                    FROM Donation d
                    WHERE d.campaignID = c.campaignID
                ), 0) AS raised
            FROM Campaign c
            WHERE c.isArchived = FALSE
            ORDER BY c.campaignID DESC
        ";

        $result = $conn->query($sql);
        if ($result) {
            $campaigns = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    // TEMP: hard coded example campaigns for demonstration when the database has none.
    if (empty($campaigns)) {
        $campaigns = [
            ['campaignID' => 8, 'name' => 'Laptops for Learners', 'description' => 'Providing refurbished laptops to high school learners in rural areas.', 'goal' => 50000, 'raised' => 18250, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 7, 'name' => 'Coding Club Kits', 'description' => 'Starter electronics and robotics kits for after-school coding clubs.', 'goal' => 12000, 'raised' => 12000, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 6, 'name' => 'Community Wi-Fi Hotspot', 'description' => 'Setting up free internet access at the local community centre.', 'goal' => 30000, 'raised' => 4500, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 5, 'name' => 'Girls in Tech Bootcamp', 'description' => 'A week-long bootcamp introducing young women to careers in technology.', 'goal' => 20000, 'raised' => 15800, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 4, 'name' => 'Cyber Safety Workshops', 'description' => 'Teaching learners and parents how to stay safe online.', 'goal' => 8000, 'raised' => 9200, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 3, 'name' => 'School Computer Lab Upgrade', 'description' => 'Replacing outdated computers in a township school lab.', 'goal' => 75000, 'raised' => 31000, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 2, 'name' => 'Donate a Scissor', 'description' => 'Stationery and craft supplies for early childhood development centres.', 'goal' => 1000, 'raised' => 250, 'campaignImage' => 'images/placeholder.webp'],
            ['campaignID' => 1, 'name' => 'Teacher Digital Training', 'description' => 'Upskilling teachers to use digital tools in the classroom.', 'goal' => 15000, 'raised' => 2100, 'campaignImage' => 'images/placeholder.webp'],
        ];
    }

    $totalCampaigns = count($campaigns);

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

            // Newest first as the default and tie-breaker.
            return $cmp !== 0 ? $cmp : $b['campaignID'] <=> $a['campaignID'];
        });
    } else {
        $campaigns = array_slice($campaigns, 0, $campaignPreviewLimit);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donation_submit'])) {
        if (!$conn || $conn->connect_error) {
            $donationError = 'The donation service is currently unavailable. Please try again later.';
        } else {
            $campaignId = filter_input(INPUT_POST, 'campaignID', FILTER_VALIDATE_INT);
            $firstName = trim((string) ($_POST['firstName'] ?? ''));
            $lastName = trim((string) ($_POST['lastName'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $phoneNumber = trim((string) ($_POST['phoneNumber'] ?? ''));
            $selectedPreset = isset($_POST['presetAmount']) ? trim((string) $_POST['presetAmount']) : '';
            $customAmount = trim((string) ($_POST['customAmount'] ?? ''));

            $amount = 0.0;
            if ($selectedPreset !== '' && $selectedPreset !== 'custom') {
                $amount = (float) $selectedPreset;
            } elseif ($customAmount !== '') {
                $amount = (float) $customAmount;
            }

            if (!$campaignId || $campaignId < 1) {
                $donationError = 'Please choose a valid campaign before donating.';
            } elseif ($firstName === '' || $lastName === '' || $email === '') {
                $donationError = 'Please complete your name and email address.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $donationError = 'Please provide a valid email address.';
            } elseif ($amount <= 0) {
                $donationError = 'Please select or enter a donation amount greater than zero.';
            } else {
                $paymentReference = 'DON-' . strtoupper(substr(hash('sha256', uniqid((string) microtime(true), true)), 0, 16));
                $insertStmt = $conn->prepare(
                    'INSERT INTO Donation (campaignID, firstName, lastName, phoneNumber, email, amount, paymentReference) VALUES (?, ?, ?, ?, ?, ?, ?)'
                );

                if (!$insertStmt) {
                    $donationError = 'Could not prepare the donation record.';
                } else {
                    $insertStmt->bind_param(
                        'issssds',
                        $campaignId,
                        $firstName,
                        $lastName,
                        $phoneNumber,
                        $email,
                        $amount,
                        $paymentReference
                    );

                    if ($insertStmt->execute()) {
                        $donationSuccess = 'Thank you, your donation has been recorded successfully.';
                    } else {
                        $donationError = 'Could not save your donation. Please try again.';
                    }

                    $insertStmt->close();
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <?php $pageTitle = "Campaigns"; ?>
    <?php include 'components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'components/navBar.php'; ?>

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

            <p class="campaign-filter-count">Showing <?= count($campaigns) ?> of <?= $totalCampaigns ?> campaigns</p>
        <?php endif; ?>

        <!-- campaign section -->
        <h2 class="section-title pt-4 mb-0"><?= $showAllCampaigns ? 'All Campaigns' : 'Current Campaigns' ?></h2>
        <section class="campaign-section">

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

                    <?php
                    $campaignImage = !empty($campaign['campaignImage']) ? $campaign['campaignImage'] : 'images/placeholder.webp';
                    $raised = isset($campaign['raised']) ? (float) $campaign['raised'] : 0;
                    $goal = (float) $campaign['goal'];
                    $percent = $goal > 0 ? min(100, ($raised / $goal) * 100) : 0;
                    ?>

                    <div class="campaign-card"
                        data-bs-toggle="modal"
                        data-bs-target="#campaignModal"
                        data-campaign-id="<?= (int) $campaign['campaignID'] ?>"
                        data-name="<?= htmlspecialchars($campaign['name'], ENT_QUOTES) ?>"
                        data-image="<?= htmlspecialchars($campaignImage, ENT_QUOTES) ?>"
                        data-description="<?= htmlspecialchars($campaign['description'], ENT_QUOTES) ?>"
                        data-stats="R<?= number_format($raised, 2) ?> raised of R<?= number_format($goal, 2) ?>">
                        <div class="campaign-image">
                            <img src="<?= htmlspecialchars($campaignImage) ?>" alt="<?= htmlspecialchars($campaign['name']) ?>">
                        </div>
                        <div class="campaign-content">
                            <h3><?= htmlspecialchars($campaign['name']) ?></h3>
                            <p><?= htmlspecialchars($campaign['description']) ?></p>
                            <p class="campaign-stats">R<?= number_format($raised, 2) ?> raised of R<?= number_format($goal, 2) ?></p>
                            <div class="progress" role="progressbar" aria-label="Campaign progress" aria-valuenow="<?= round($percent) ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </section>

        <?php if ($showAllCampaigns || $totalCampaigns > $campaignPreviewLimit): ?>
            <div class="campaign-view-all">
                <?php if ($showAllCampaigns): ?>
                    <a href="campaign.php" class="btn btn-outline-dark">Show Fewer Campaigns</a>
                <?php else: ?>
                    <a href="campaign.php?view=all" class="btn btn-primary">View All Campaigns (<?= $totalCampaigns ?>)</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>

        <?php if ($donationError !== ''): ?>
            <div class="alert alert-danger mx-auto mt-3" style="max-width: 900px;">
                <?= htmlspecialchars($donationError) ?>
            </div>
        <?php elseif ($donationSuccess !== ''): ?>
            <div class="alert alert-success mx-auto mt-3" style="max-width: 900px;">
                <?= htmlspecialchars($donationSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($showAllCampaigns): ?>
            <!-- Sort & Filter Modal -->
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

        <!-- Campaign Modal -->
        <div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="campaignModalTitle">Campaign Details</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="campaign-modal-image-wrap mb-3">
                            <img id="campaignModalImage" src="images/placeholder.webp" alt="Campaign image" class="campaign-modal-image">
                        </div>
                        <div class="progress" role="progressbar" aria-label="Campaign progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 25%"></div>
                        </div>
                        <p id="campaignModalStats" class="campaign-stats mb-2"></p>
                        <p id="campaignModalDescription">More information about the selected campaign will be displayed here.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="openDonationModal" class="btn btn-primary">Make your Donation</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donation Modal -->
        <div class="modal fade" id="donationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="donationModalTitle">Donate</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="campaign.php<?= $showAllCampaigns ? '?' . htmlspecialchars(http_build_query(['view' => 'all', 'search' => $searchTerm, 'progress' => $progressKey, 'sort' => $sortKey])) : '' ?>">
                        <div class="modal-body">
                            <input type="hidden" name="campaignID" id="campaignID">

                            <div class="mb-3">
                                <label for="firstName" class="form-label">First name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>

                            <div class="mb-3">
                                <label for="lastName" class="form-label">Last name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="phoneNumber" class="form-label">Phone number</label>
                                <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Choose an amount</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presetAmount" id="amount100" value="100">
                                        <label class="form-check-label" for="amount100">R100</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presetAmount" id="amount250" value="250">
                                        <label class="form-check-label" for="amount250">R250</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presetAmount" id="amount500" value="500">
                                        <label class="form-check-label" for="amount500">R500</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="presetAmount" id="amountCustom" value="custom">
                                        <label class="form-check-label" for="amountCustom">Custom</label>
                                    </div>
                                </div>
                                <input type="number" class="form-control d-none" id="customAmount" name="customAmount" min="1" step="0.01" placeholder="Enter your own amount" disabled>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark px-10" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="donation_submit" value="1" class="btn btn-primary">Donate now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            const campaignModalElement = document.getElementById('campaignModal');
            const donationModalElement = document.getElementById('donationModal');
            const campaignModalTitle = document.getElementById('campaignModalTitle');
            const donationModalTitle = document.getElementById('donationModalTitle');
            const campaignModalImage = document.getElementById('campaignModalImage');
            const campaignModalStats = document.getElementById('campaignModalStats');
            const campaignModalDescription = document.getElementById('campaignModalDescription');
            const campaignIdInput = document.getElementById('campaignID');
            const customAmountInput = document.getElementById('customAmount');
            const campaignModal = new bootstrap.Modal(campaignModalElement);
            const donationModal = new bootstrap.Modal(donationModalElement);

            document.querySelectorAll('.campaign-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const campaignName = card.dataset.name || 'Campaign Details';
                    campaignModalTitle.textContent = campaignName;
                    donationModalTitle.textContent = campaignName;
                    campaignModalImage.src = card.dataset.image || 'images/placeholder.webp';
                    campaignModalImage.alt = campaignName;
                    campaignModalStats.textContent = card.dataset.stats || '';
                    campaignModalDescription.textContent = card.dataset.description || 'More information about the selected campaign will be displayed here.';
                    campaignIdInput.value = card.dataset.campaignId || '';
                    campaignModal.show();
                });
            });

            document.getElementById('openDonationModal').addEventListener('click', function() {
                if (!campaignIdInput.value) {
                    alert('Please choose a campaign first.');
                    return;
                }

                campaignModal.hide();
                donationModal.show();
            });

            document.querySelectorAll('input[name="presetAmount"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    const isCustom = this.value === 'custom';
                    customAmountInput.disabled = !isCustom;
                    customAmountInput.classList.toggle('d-none', !isCustom);
                    if (!isCustom) {
                        customAmountInput.value = '';
                    }
                });
            });
        </script>
    </body>
</html>
