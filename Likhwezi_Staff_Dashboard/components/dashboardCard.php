<?php
$cardTitle = $cardTitle ?? '';
$cardValue = $cardValue ?? '';
$cardMeta = $cardMeta ?? '';
$cardClass = $cardClass ?? '';
?>

<article class="dashboard-card <?= htmlspecialchars($cardClass) ?>">
    <h2><?= htmlspecialchars($cardTitle) ?></h2>
    <div class="dashboard-card-value"><?= htmlspecialchars($cardValue) ?></div>

    <?php if ($cardMeta !== '') : ?>
        <p class="dashboard-card-meta"><?= htmlspecialchars($cardMeta) ?></p>
    <?php endif; ?>
</article>
