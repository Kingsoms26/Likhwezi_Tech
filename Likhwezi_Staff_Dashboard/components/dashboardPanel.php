<?php
$panelTitle = $panelTitle ?? '';
$panelAction = $panelAction ?? '';
$panelClass = $panelClass ?? '';
?>

<section class="dashboard-panel <?= htmlspecialchars($panelClass) ?>">
    <div class="panel-header">
        <h2><?= htmlspecialchars($panelTitle) ?></h2>

        <?php if ($panelAction !== '') : ?>
            <a class="panel-button" href="#"><?= htmlspecialchars($panelAction) ?></a>
        <?php endif; ?>
    </div>

    <div class="panel-body">
        <?= $panelContent ?? '' ?>
    </div>
</section>
