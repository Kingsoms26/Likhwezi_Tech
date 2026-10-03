<?php
    // cookieNotice.php shows the cookie notice at the bottom of the public pages until the visitor dismisses it
    // the site only sets essential cookies so the notice just informs
    // staff pages set $hideCookieNotice so it never shows there
    // rejected is still accepted so visitors who rejected the old notice do not see it again
    if (!empty($hideCookieNotice) || in_array($_COOKIE['cookieConsent'] ?? '', ['accepted', 'rejected'], true)) {
        return;
    }
?>
<!-- cookie notice -->
<div class="cookie-notice" id="cookieNotice" role="region" aria-label="Cookie notice">
    <p class="mb-0">We only use the cookies needed to keep this website and its forms working safely. <a href="privacy.php">Privacy Policy</a></p>
    <div class="cookie-notice-actions">
        <button type="button" class="btn btn-primary" data-cookie-choice="accepted">Got it</button>
    </div>
</div>
<script src="assets/js/cookieNotice.js?v=<?= filemtime(__DIR__ . '/../../assets/js/cookieNotice.js') ?>"></script>
