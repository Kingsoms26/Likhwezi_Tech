<?php
    // 404.php is shown for any address that does not exist, see the 404 rule in .htaccess
    require_once __DIR__ . '/includes/security.php';
    http_response_code(404);
    $pageTitle = "Page Not Found";

    // the missing address can be in a subfolder so relative links would break
    // the base tag points them back at the site root
    $baseHref = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <!-- not found message -->
        <main>
            <section class="hero hero-centered hero-text-only not-found">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <p class="not-found-code">404</p>
                        <h1>Page not found</h1>
                        <p>The page you are looking for may have been moved, renamed or no longer exists. Check the address, or use the links below to find your way.</p>
                    </div>

                    <div class="btn-group">
                        <div class="p-1">
                            <a href="index.php" class="btn btn-primary btn-sm">Back to Home</a>
                        </div>
                        <div class="p-1">
                            <a href="contact.php" class="btn btn-ghost btn-sm">Contact Us</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>
