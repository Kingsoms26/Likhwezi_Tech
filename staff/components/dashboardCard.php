<?php
    // dashboardCard.php is a stat card on the dashboards showing a title, value and optional note
    $cardTitle = $cardTitle ?? '';
    $cardValue = $cardValue ?? '';
    $cardMeta = $cardMeta ?? '';
    $cardClass = $cardClass ?? '';
    // optional link that makes the whole card clickable
    $cardLink = $cardLink ?? '';
    $cardTag = $cardLink !== '' ? 'a' : 'article';
?>

<<?= $cardTag ?> class="dashboard-card <?= $cardLink !== '' ? 'dashboard-card-link' : '' ?> <?= htmlspecialchars($cardClass) ?>"<?= $cardLink !== '' ? ' href="' . htmlspecialchars($cardLink) . '"' : '' ?>>
    <h2><?= htmlspecialchars($cardTitle) ?></h2>
    <div class="dashboard-card-value"><?= htmlspecialchars($cardValue) ?></div>

    <?php if ($cardMeta !== '') : ?>
        <p class="dashboard-card-meta"><?= htmlspecialchars($cardMeta) ?></p>
    <?php endif; ?>
</<?= $cardTag ?>>

<!-- clear the link so it does not carry over to the next card -->
<?php unset($cardLink); ?>
