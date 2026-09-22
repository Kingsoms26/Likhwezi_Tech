<?php
    session_start();
    include 'tools/dbConnection.php';
    $pageTitle = "Partners";
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero hero-text-only">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Our Partners</h1>
                    <p>Likhwezi Technologies works alongside a network of organisations who share our commitment to delivering real business value through data.</p>
                </div>
            </div>
        </section>

        <hr>

        <!-- partners section -->
        <section class="page-section">
            <h2 class="section-title">Who We Work With</h2>
            <div class="partners-list">
                <?php
                    $result = $conn->query(
                        "SELECT name, description, logo, websiteURL FROM Partner WHERE isArchived = FALSE ORDER BY dateAdd ASC"
                    );

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $name = htmlspecialchars($row['name']);
                            $description = htmlspecialchars($row['description']);
                            $logo = htmlspecialchars($row['logo']);
                            $websiteURL = htmlspecialchars($row['websiteURL'] ?? '');
                ?>
                            <div class="partner-row">
                                <div class="partner-logo-box">
                                    <img src="images/partners/<?= $logo ?>" alt="<?= $name ?> logo">
                                </div>
                                <div class="partner-info">
                                    <div class="partner-name"><?= $name ?></div>
                                    <div class="partner-description"><?= $description ?></div>
                                </div>
                                <?php if ($websiteURL): ?>
                                    <a href="<?= $websiteURL ?>" class="partner-link" target="_blank" rel="noopener">
                                        Visit website &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                <?php
                        }
                    } else {
                        echo '<p class="text-center py-4">Partner information coming soon.</p>';
                    }
                ?>
            </div>
        </section>

        <!-- footer -->
        <?php include 'components/footer.php'; ?>
    </body>
</html>