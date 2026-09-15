<?php
    session_start();
    include __DIR__ . '/tools/dbConnection.php';

    $dbAvailable = false;
    if ($conn && !$conn->connect_error) {
        $dbAvailable = true;

        $sql = "SELECT * FROM Campaign WHERE isArchived = FALSE";
        $result = $conn->query($sql);

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

                    <div class="campaign-card">
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
                
                <div class="campaign-card">
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

                <div class="campaign-card">
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

                <div class="campaign-card">
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
    </body>
</html>
