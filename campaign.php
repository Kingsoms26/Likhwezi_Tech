<?php
    session_start();
    include __DIR__ . '/tools/dbConnection.php';

    $dbAvailable = false;
    $donationError = '';
    $donationSuccess = '';

    if ($conn && !$conn->connect_error) {
        $dbAvailable = true;

        $sql = "SELECT * FROM Campaign WHERE isArchived = FALSE";
        $result = $conn->query($sql);
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
    <?php include 'components/header.php'; ?>
    <?php $pageTitle = "Campaign"; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Campaigns</h1>
                    <p>Explore our various campaigns and initiatives.</p>
                </div>

                <div class="p-1">
                    <a href="cym.php" class="btn btn-primary btn-sm">Cyber Young Minds</a>
                </div>
            </div>
        </section>

        <hr>

        <!-- campaign section -->
        <section class="campaign-section">

            <?php if ($dbAvailable && $result && $result->num_rows > 0): ?>

                <?php while ($campaign = $result->fetch_assoc()): ?>

                    <?php
                    $campaignImage = 'images/placeholder.webp';
                    $imageStmt = $conn->prepare(
                        "SELECT image FROM GalleryItem WHERE campaignID = ? ORDER BY galleryItemID ASC LIMIT 1"
                    );
                    if ($imageStmt) {
                        $imageStmt->bind_param("i", $campaign['campaignID']);
                        $imageStmt->execute();
                        $imageItem = $imageStmt->get_result()->fetch_assoc();
                        if ($imageItem && !empty($imageItem['image'])) {
                            $campaignImage = $imageItem['image'];
                        }
                        $imageStmt->close();
                    }

                    $raised = 0;
                    $donationStmt = $conn->prepare(
                        "SELECT COALESCE(SUM(amount), 0) AS raised FROM Donation WHERE campaignID = ?"
                    );
                    if ($donationStmt) {
                        $donationStmt->bind_param("i", $campaign['campaignID']);
                        $donationStmt->execute();
                        $raised = (float) $donationStmt->get_result()->fetch_assoc()['raised'];
                        $donationStmt->close();
                    }

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
                <?php endwhile; ?>

            <?php else: ?>

                <!--<p>No campaigns available at the moment.</p>-->
                <!-- hard coded example campaigns for demonstration purposes -->
                <div class="campaign-card" data-bs-toggle="modal" data-bs-target="#campaignModal" data-campaign-id="1" data-name="Donate a scissor" data-image="images/placeholder.webp" data-description="A bunch of random stuff" data-stats="R250.00 raised of R1000.00">
                    <div class="campaign-image">
                        <img src="images/placeholder.webp" alt="Donate a scissor">
                    </div>
                    <div class="campaign-content">
                        <h3>Donate a scissor</h3>
                        <p>A bunch of random stuff</p>
                        <p class="campaign-stats">R250.00 raised of R1000.00</p>
                        <div class="progress" role="progressbar" aria-label="Campaign progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 25%"></div>
                        </div>
                    </div>
                </div>

                <div class="campaign-card" data-bs-toggle="modal" data-bs-target="#campaignModal" data-campaign-id="2" data-name="Donate a scissor" data-image="images/placeholder.webp" data-description="A bunch of random stuff" data-stats="R250.00 raised of R1000.00">
                    <div class="campaign-image">
                        <img src="images/placeholder.webp" alt="Donate a scissor">
                    </div>
                    <div class="campaign-content">
                        <h3>Donate a scissor</h3>
                        <p>A bunch of random stuff</p>
                        <p class="campaign-stats">R250.00 raised of R1000.00</p>
                        <div class="progress" role="progressbar" aria-label="Campaign progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 25%"></div>
                        </div>
                    </div>
                </div>

                <div class="campaign-card" data-bs-toggle="modal" data-bs-target="#campaignModal" data-campaign-id="3" data-name="Donate a scissor" data-image="images/placeholder.webp" data-description="A bunch of random stuff" data-stats="R250.00 raised of R1000.00">
                    <div class="campaign-image">
                        <img src="images/placeholder.webp" alt="Donate a scissor">
                    </div>
                    <div class="campaign-content">
                        <h3>Donate a scissor</h3>
                        <p>A bunch of random stuff</p>
                        <p class="campaign-stats">R250.00 raised of R1000.00</p>
                        <div class="progress" role="progressbar" aria-label="Campaign progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 25%"></div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </section>

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

        <div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="campaignModalTitle">Campaign Details</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <img id="campaignModalImage" src="images/placeholder.webp" alt="Campaign image" class="img-fluid mb-3 rounded">
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

        <div class="modal fade" id="donationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="donationModalTitle">Donate</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="campaign.php">
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
                                        <input class="form-check-input" type="radio" name="presetAmount" id="amount50" value="50">
                                        <label class="form-check-label" for="amount50">R50</label>
                                    </div>
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
                                <input type="number" class="form-control" id="customAmount" name="customAmount" min="1" step="0.01" placeholder="Enter your own amount" disabled>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
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
                    donationModalTitle.textContent = 'Donate to ' + campaignName;
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
                    if (!isCustom) {
                        customAmountInput.value = '';
                    }
                });
            });
        </script>
    </body>
</html>
