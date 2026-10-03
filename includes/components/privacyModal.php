<?php
    // privacyModal.php is the shared privacy notice and consent popup
    // include once per page then add data-privacy-modal-open to any link or button that should open it
    // set $privacyModalButtonClass first to match the page's button style
    $privacyModalButtonClass = $privacyModalButtonClass ?? 'btn btn-primary';
?>
<!-- privacy notice and consent popup -->
<div class="contact-modal-overlay" id="privacyModal" role="dialog" aria-modal="true" aria-labelledby="privacyModalTitle" aria-hidden="true">
    <div class="contact-modal-box privacy-modal-box">
        <!-- close button -->
        <button type="button" class="contact-modal-close" data-privacy-modal-close aria-label="Close privacy notice">&times;</button>

        <!-- heading -->
        <h2 id="privacyModalTitle">Consent Clause</h2>

        <!-- privacy notice wording -->
        <div class="privacy-modal-content">
            <?php include __DIR__ . '/privacyNotice.php'; ?>
        </div>

        <!-- confirms the visitor read the notice and ticks the consent box -->
        <div class="contact-modal-actions">
            <button type="button" class="<?= htmlspecialchars($privacyModalButtonClass) ?>" data-privacy-modal-accept>
                I understand
            </button>
        </div>
    </div>
</div>

<script src="assets/js/privacyModal.js?v=<?= filemtime(__DIR__ . '/../../assets/js/privacyModal.js') ?>"></script>
