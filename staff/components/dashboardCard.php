<?php
    // Reusable statistic card shared by dashboard and management pages.
    $cardTitle = $cardTitle ?? '';
    $cardValue = $cardValue ?? '';
    $cardMeta = $cardMeta ?? '';
    $cardClass = $cardClass ?? '';
    $cardLink = $cardLink ?? '';

    $isDashboardHome = ($activePage ?? '') === 'dashboard';
    $cardTag = $cardLink !== '' ? 'a' : 'article';

    $cardIcons = [
        'enquiries-awaiting-card' => 'envelope-exclamation',
        'enquiries-open-card' => 'inbox',
        'registrations-recent-card' => 'person-plus',
        'campaigns-active-card' => 'megaphone',
        'donations-card' => 'cash-stack',
        'events-upcoming-card' => 'calendar-event',
        'enquiries-card' => 'envelope',
        'campaigns-card' => 'bullseye',
    ];

    $cardIcon = $cardIcons[$cardClass] ?? 'bar-chart-line';
?>

<?php if ($isDashboardHome) : ?>
    <div class="col">
<?php endif; ?>

<<?= $cardTag ?>
    class="dashboard-card <?= $cardLink !== '' ? 'dashboard-card-link' : '' ?> <?= htmlspecialchars($cardClass) ?><?= $isDashboardHome ? ' card h-100' : '' ?>"
    <?= $cardLink !== '' ? 'href="' . htmlspecialchars($cardLink) . '"' : '' ?>
>
    <div class="dashboard-card-heading d-flex align-items-center justify-content-between gap-2">
        <h2><?= htmlspecialchars($cardTitle) ?></h2>
        <span class="dashboard-card-icon" aria-hidden="true">
            <i class="bi bi-<?= htmlspecialchars($cardIcon) ?>"></i>
        </span>
    </div>

    <div class="dashboard-card-value"><?= htmlspecialchars($cardValue) ?></div>

    <?php if ($cardMeta !== '') : ?>
        <p class="dashboard-card-meta"><?= htmlspecialchars($cardMeta) ?></p>
    <?php endif; ?>
</<?= $cardTag ?>>

<?php if ($isDashboardHome) : ?>
    </div>
<?php endif; ?>

<?php
    // Clear the optional link before the next include uses this component.
    unset($cardLink);
?>
