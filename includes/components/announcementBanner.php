<?php
    // announcementBanner.php shows the website announcements under the navbar on every public page
    // admins post them on staff/announcements.php
    // a closed announcement stays hidden in that browser until it is edited, see assets/js/announcementBanner.js
    require_once __DIR__ . '/../helpers/announcements.php';
    $siteAnnouncements = siteAnnouncements();
?>

<?php if ($siteAnnouncements) : ?>
    <div class="site-announcements">
        <?php foreach ($siteAnnouncements as $announcement) : ?>
            <div class="site-announcement" data-key="<?= htmlspecialchars(announcementKey($announcement)) ?>" role="status">
                <i class="bi bi-megaphone site-announcement-icon" aria-hidden="true"></i>
                <p class="site-announcement-text">
                    <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                    <span><?= nl2br(htmlspecialchars($announcement['message'])) ?></span>
                    <?php if ($announcement['linkURL']) : ?>
                        <a href="<?= htmlspecialchars($announcement['linkURL']) ?>" target="_blank" rel="noopener">Find out more</a>
                    <?php endif; ?>
                </p>
                <button type="button" class="site-announcement-close" aria-label="Close announcement">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- runs straight away so closed announcements are hidden before the page shows -->
    <script src="assets/js/announcementBanner.js?v=<?= filemtime(__DIR__ . '/../../assets/js/announcementBanner.js') ?>"></script>
<?php endif; ?>
