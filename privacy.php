<!-- privacy.php is the privacy policy page
 contains the privacy notice and how to contact us about personal information
-->
<?php
    $pageTitle = "Privacy Policy";
    require_once __DIR__ . '/includes/helpers/siteContent.php';
    // fetch the contact email for the page
    $siteContact = siteSettings();
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero hero-text-only">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Privacy Policy</h1>
                    <p>How Likhwezi Technologies collects, uses and protects your personal information under POPIA.</p>
                </div>
            </div>
        </section>

        <hr>

        <!-- privacy notice -->
        <section class="page-section privacy-page">
            <h2 class="section-title">Privacy Notice and Consent</h2>
            <div class="privacy-page-content">
                <?php include 'includes/components/privacyNotice.php'; ?>
                <p>For questions about your personal information, or to request access, correction or deletion, email <a href="mailto:<?= htmlspecialchars($siteContact['contactEmail']) ?>"><?= htmlspecialchars($siteContact['contactEmail']) ?></a> or <a href="contact.php">contact us</a>.</p>
            </div>
        </section>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>
