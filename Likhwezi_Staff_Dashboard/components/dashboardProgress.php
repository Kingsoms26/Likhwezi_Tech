<?php
$progressValue = max(0, min(100, (float)($progressValue ?? 0)));
$progressLabel = $progressLabel ?? '';
$progressMeta = $progressMeta ?? '';
?>

<div class="progress-item">
    <?php if ($progressLabel !== '' || $progressMeta !== '') : ?>
        <div class="progress-heading">
            <span><?= htmlspecialchars($progressLabel) ?></span>
            <span><?= htmlspecialchars($progressMeta) ?></span>
        </div>
    <?php endif; ?>

    <div class="progress-track">
        <div class="progress-fill" style="width: <?= $progressValue ?>%;"></div>
    </div>
</div>
