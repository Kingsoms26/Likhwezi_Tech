<?php
    // dashboardAlerts.php shows the success and error messages at the top of a page
    // $flash comes from takeFlash() and $errors is a list of validation messages, both optional
    $flash = $flash ?? null;
    $errors = $errors ?? [];
?>

<!-- success or error message -->
<?php if ($flash) : ?>
    <div class="dashboard-alert <?= $flash['type'] === 'success' ? 'alert-success' : 'alert-error' ?>" role="status">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- validation errors -->
<?php if ($errors) : ?>
    <div class="dashboard-alert alert-error" role="alert">
        <ul>
            <?php foreach ($errors as $error) : ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
