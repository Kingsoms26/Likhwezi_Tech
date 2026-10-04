<!-- footer.php is the footer for the public pages
 contains the logo, contact details, page links and the cookie notice
-->
<?php
    // fetch the contact details, admins edit them on staff/manageContent.php
    require_once __DIR__ . '/../helpers/siteContent.php';
    $footerContact = siteSettings();
?>
<footer>
    <!-- top section with the company info and link columns -->
    <div class="footer-top py-3">
        <div class="footer-columns">
            <!-- company logo and description -->
            <div>
                <!-- the full logo already shows the company name so no separate name is needed -->
                <img src="assets/images/logo/extended-logo.webp" alt="Likhwezi Technologies" class="footer-logo" width="560" height="224" loading="lazy">
            </div>

            <!-- contact info -->
            <div>
                <p class="footer-section-heading">Contact us</p>
                <ul>
                    <li><?= htmlspecialchars($footerContact['contactAddress']) ?></li>
                    <li><a class="footer-link" href="<?= htmlspecialchars(phoneHref($footerContact['contactPhone'])) ?>"><?= htmlspecialchars($footerContact['contactPhone']) ?></a></li>
                    <li><a class="footer-link" href="mailto:<?= htmlspecialchars($footerContact['contactEmail']) ?>"><?= htmlspecialchars($footerContact['contactEmail']) ?></a></li>
                </ul>
            </div>

            <!-- company links -->
            <div>
                <p class="footer-section-heading">Company</p>
                <ul>
                    <li><a class="footer-link" href="about.php">About Us</a></li>
                    <li><a class="footer-link" href="services.php">Services</a></li>
                    <li><a class="footer-link" href="partners.php">Partners</a></li>
                </ul>
            </div>

            <!-- community links -->
            <div>
                <p class="footer-section-heading">Community</p>
                <ul>
                    <li><a class="footer-link" href="cym.php">Cyber Young Minds</a></li>
                    <li><a class="footer-link" href="gallery.php">Gallery & Events</a></li>
                    <li><a class="footer-link" href="campaign.php">Campaigns</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- bottom section with the copyright and privacy link -->
    <div class="footer-bottom font-small pt-2">
        <p>&copy; 2026 Likhwezi Technologies (Pty) Ltd. All rights reserved. | <a class="footer-link" href="privacy.php">Privacy Policy</a></p>
    </div>
</footer>

<!-- cookie notice -->
<?php include __DIR__ . '/cookieNotice.php'; ?>

<!-- email links offer gmail or outlook when no email app opens -->
<script src="assets/js/emailChooser.js?v=<?= filemtime(__DIR__ . '/../../assets/js/emailChooser.js') ?>"></script>

<!-- bootstrap script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>