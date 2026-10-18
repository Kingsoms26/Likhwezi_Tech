<nav class="enquiry-tabs-nav" aria-label="Enquiries">
    <ul class="enquiry-tabs">
        <?php foreach ($enquiryTabs as $tabKey => $tab) : ?>
            <li>
                <a href="<?= htmlspecialchars($tab['href']) ?>" <?= $tabKey === $activeTab ? 'aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($tab['label']) ?>
                    <span class="enquiry-tab-count"><?= $tab['count'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
