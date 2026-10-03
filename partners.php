<?php
    // partners.php is the partners page
    // contains the hero logo cluster and a card for each partner
    require_once __DIR__ . '/includes/security.php';
    require_once __DIR__ . '/includes/helpers/cache.php';
    require_once __DIR__ . '/includes/helpers/imageStorage.php';
    $pageTitle = "Partners";
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <?php
            // fetch the partners from the database, the hero logos and the cards both use this list
            // cached for 5 minutes so most visits skip the database
            $partnerRows = cached('partners_list', 300, function () {
                $conn = db();
                $result = $conn ? $conn->query(
                    "SELECT name, description, logo, websiteURL FROM Partner WHERE isArchived = FALSE ORDER BY sortOrder ASC, dateAdd ASC"
                ) : false;
                return $result ? $result->fetch_all(MYSQLI_ASSOC) : null;
            }) ?? [];

            $partners = [];
            if ($partnerRows) {
                foreach ($partnerRows as $row) {
                    // add https to bare addresses and drop anything that is not a web link
                    $rawURL = trim($row['websiteURL'] ?? '');
                    if ($rawURL !== '' && !preg_match('#^https?://#i', $rawURL)) {
                        $rawURL = 'https://' . $rawURL;
                    }
                    $partners[] = [
                        'name' => htmlspecialchars($row['name']),
                        'description' => htmlspecialchars($row['description']),
                        'logo' => htmlspecialchars(imageSrc($row['logo'], 'assets/images/partners/')),
                        'websiteURL' => filter_var($rawURL, FILTER_VALIDATE_URL) ? htmlspecialchars($rawURL) : '',
                    ];
                }
            }
        ?>

        <!-- hero section -->
        <section class="hero partners-hero <?= $partners ? '' : 'hero-text-only' ?>">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Our Partners</h1>
                    <p>Likhwezi Technologies works alongside a network of organisations who share our commitment to delivering real business value through data.</p>
                </div>
            </div>

            <?php if ($partners): ?>
                <!-- logo cluster, the same partners are listed in full below -->
                <?php $heroLogos = array_slice($partners, 0, 6); ?>
                <!-- two columns for up to four logos so there is never a lone tile on the last row -->
                <div class="hero-image partners-hero-logos <?= count($heroLogos) <= 4 ? 'is-two-col' : '' ?>" aria-hidden="true">
                    <?php foreach ($heroLogos as $i => $partner): ?>
                        <div class="partners-hero-tile" style="--i: <?= $i ?>">
                            <img src="<?= $partner['logo'] ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <hr>

        <!-- partners section -->
        <section class="page-section">
            <h2 class="section-title">Who We Work With</h2>
            <?php if ($partners): ?>
                <!-- a card for each partner -->
                <div class="partners-cards">
                    <?php foreach ($partners as $partner): ?>
                        <article class="partner-card">
                            <div class="partner-card-media">
                                <img src="<?= $partner['logo'] ?>" alt="<?= $partner['name'] ?> logo" loading="lazy">
                            </div>
                            <div class="partner-card-body">
                                <h3 class="partner-name"><?= $partner['name'] ?></h3>
                                <p class="partner-description"><?= $partner['description'] ?></p>
                                <?php if ($partner['websiteURL']): ?>
                                    <!-- stretched over the whole card so anywhere on it opens the site -->
                                    <a href="<?= $partner['websiteURL'] ?>" class="partner-link" target="_blank" rel="noopener">
                                        Visit website
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- shown when there are no partners yet -->
                <p class="text-center py-4">Partner information coming soon.</p>
            <?php endif; ?>
        </section>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>