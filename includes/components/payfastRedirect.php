<?php
    // payfastRedirect.php sends the donor to PayFast with a signed form
    // the form submits itself, the button is there if javascript is off or the redirect is slow
    // needs $payfastCheckout from payfastCheckoutFields()
?>
<!DOCTYPE html>
<html lang="en">
    <?php $pageTitle = "Campaigns"; ?>
    <?php include __DIR__ . '/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include __DIR__ . '/navBar.php'; ?>

        <!-- redirect message and the payfast form -->
        <main class="payfast-redirect">
            <div class="payfast-redirect-card">
                <div class="spinner-border" role="status" aria-hidden="true"></div>
                <h1>Taking you to PayFast&hellip;</h1>
                <p>Your donation of <strong>R<?= htmlspecialchars(number_format((float) $payfastCheckout['amount'], 2)) ?></strong> is ready. You'll pay securely on PayFast, then come back here.</p>

                <form id="payfastForm" method="POST" action="<?= htmlspecialchars(payfastProcessURL()) ?>">
                    <?php foreach ($payfastCheckout as $name => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Continue to PayFast</button>
                </form>
            </div>
        </main>

        <script src="assets/js/payfastRedirect.js?v=<?= filemtime(__DIR__ . '/../../assets/js/payfastRedirect.js') ?>"></script>
    </body>
</html>
