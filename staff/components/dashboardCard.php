<?php
    // dashboardCard.php is a stat card on the dashboards showing a title, value and optional note
    $cardTitle = $cardTitle ?? '';
    $cardValue = $cardValue ?? '';
    $cardMeta = $cardMeta ?? '';
    $cardClass = $cardClass ?? '';
    // optional link that makes the whole card clickable
    $cardLink = $cardLink ?? '';
    // optional bootstrap icon name without the bi- prefix, like envelope or megaphone
    $cardIcon = $cardIcon ?? '';
    $cardTag = $cardLink !== '' ? 'a' : 'article';
?>

<<?= $cardTag ?> class="dashboard-card <?= $cardLink !== '' ? 'dashboard-card-link' : '' ?> <?= htmlspecialchars($cardClass) ?>"<?= $cardLink !== '' ? ' href="' . htmlspecialchars($cardLink) . '"' : '' ?>>
    <?php if ($cardIcon !== '') : ?>
        <i class="bi bi-<?= htmlspecialchars($cardIcon) ?> dashboard-card-icon" aria-hidden="true"></i>
    <?php endif; ?>

    <h2><?= htmlspecialchars($cardTitle) ?></h2>
    <div class="dashboard-card-value"><?= htmlspecialchars($cardValue) ?></div>

    <?php if ($cardMeta !== '') : ?>
        <p class="dashboard-card-meta"><?= htmlspecialchars($cardMeta) ?></p>
    <?php endif; ?>
</<?= $cardTag ?>>

<!-- clear the link and icon so they do not carry over to the next card -->
<?php unset($cardLink, $cardIcon); ?>
