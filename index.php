<!DOCTYPE html>
<html lang="en">
    <?php include 'components/header.php'; ?>
    <?php $pageTitle = "Home"; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero ">
            <div class="hero-content ps-5 d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Realized Imagination through Insights</h1>
                    <p>Our mission is to help private and public sector organizations realize and deliver business value through data insights.</p>
                </div>

                <div class="p-1">
                    <a href="services.php" class="btn btn-primary btn-sm">Book a Consultation</a>
                </div>
            </div>
            <div class="hero-image"> 
                
                <!-- Still to be decided -->
                <img src="images/hero-image.jpg" alt="Hero Image">

            </div>
        </section>

        <!-- footer -->
        <?php include 'components/footer.php'; ?>
    </body>
</html>