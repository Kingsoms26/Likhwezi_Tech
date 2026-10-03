<!-- header.php is the shared head for the public pages
 contains the page title, fonts, bootstrap and the site stylesheets
-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php global $pageTitle, $baseHref; ?>
    <?php if (!empty($baseHref)): ?>
        <!-- only set by pages served from other addresses like 404.php so links still point at the site root -->
        <base href="<?= htmlspecialchars($baseHref) ?>">
    <?php endif; ?>

    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Likhwezi Technologies' : 'Likhwezi Technologies' ?></title>

    <!-- fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- bootstrap and bootstrap icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- site stylesheets, the version number changes with every edit so browsers fetch the new css -->
    <link href="assets/css/custom.css?v=<?= filemtime(__DIR__ . '/../../assets/css/custom.css') ?>" rel="stylesheet">
    <link href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>" rel="stylesheet">
</head>
