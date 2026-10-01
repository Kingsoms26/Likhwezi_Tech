<header class="dashboard-header">
    <div class="dashboard-brand">
        <img src="images/logo.jpg" alt="Likhwezi Technologies logo">
        <span>LIKHWEZI TECHNOLOGIES</span>
    </div>

    <div class="dashboard-user">
        <?= htmlspecialchars($_SESSION['username'] ?? 'Username') ?>
    </div>
</header>
