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

        <!-- partners section -->
        <section class="section-container d-flex flex-column align-items-center py-3 mx-10">
            <div class="section-title py-2">Our Partners</div>
            <p class="text-center px-3" style="max-width: 700px;">
                Likhwezi Technologies works alongside a network of organisations who share our
                commitment to delivering real business value through data.
            </p>

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