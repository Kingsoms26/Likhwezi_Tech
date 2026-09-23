<?php
    session_start();
    include __DIR__ . '/tools/dbConnection.php';
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'components/header.php'; ?>
    <?php $pageTitle = "Home"; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero">
            <div class="hero-content d-grid gap-3 row-gap-3">
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
                <img src="images/placeholder.webp" alt="Hero Image">
            </div>
        </section>

        <hr>

        <!-- services section -->
        <section class="section-container d-flex flex-column align-items-left py-3 mx-10">
            <div class="section-title pt-3 pb-5">Client Solutions</div>
            
            <!-- services cards -->
            <div class=" service-container d-flex flex-wrap justify-content-center gap-5 py-10">
                <div class="card">
                    <div class="card-body">
                        <div class="service-title">Enterprise Architecture</div>
                        <div class="service-description">Analyze, design, plan and implement enterprise analysis to successfully execute on business strategies.</div>
                        <a href="services.php" class="service-card-link py-2">Learn More</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="service-title">Strategic Advisory</div>
                        <div class="service-description">Analyze, design, plan and implement enterprise analysis to successfully execute on business strategies.</div>
                        <a href="services.php" class="service-card-link py-2">Learn More</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="service-title">Data Management</div>
                        <div class="service-description">Analyze, design, plan and implement enterprise analysis to successfully execute on business strategies.</div>
                        <a href="services.php" class="service-card-link py-2">Learn More</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="service-title">Data Testing</div>
                        <div class="service-description">Analyze, design, plan and implement enterprise analysis to successfully execute on business strategies.</div>
                        <a href="services.php" class="service-card-link py-2">Learn More</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="service-title">Solution Delivery</div>
                        <div class="service-description">Analyze, design, plan and implement enterprise analysis to successfully execute on business strategies.</div>
                        <a href="services.php" class="service-card-link py-2">Learn More</a>
                    </div>
                </div>
            </div>
        </section>

        <hr>

        <!-- About Us Section -->
        <section class="section-container d-flex flex-column align-items-left py-3 mx-10">
            <div class="section-title py-2">About Us</div>
            <div class="about-container d-flex align-items-stretch gap-4 pt-4">
                <div class="about-content d-flex flex-column gap-2">
                    <p>Likhwezi Technologies is a 100% Black-owned professional services consultancy based in Fourways, Gauteng. We work with organisations whose data has outgrown the way it is currently managed.</p>
                    <p>Our consultants come from delivery backgrounds, not slide decks. That means we stay through implementation, hand the system over to your team, and leave documentation they can actually use.</p>
                    <button class="btn btn-sm align-self-start mt-2">Read our Full Profile</button>
                </div>
                <div class="about-image">
                    <img src="images/about-us.png" alt="About Us Image">
                </div>
            </div>
        </section>

        <hr>

        <!-- partner section -->
        <section class="section-container d-flex flex-column align-items-left py-3 mx-10">
            <div class="section-title py-2">Our Partners</div>
            <div class="partner-container d-flex justify-content-start gap-4">
                <?php
                    $count = 0;
                    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
                        $result = $conn->query("SELECT logo FROM Partner WHERE isArchived = FALSE LIMIT 4");
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                $logo = htmlspecialchars($row['logo']);
                                echo "<img src=\"images/partners/{$logo}\" alt=\"Partner logo\" class=\"partner-logo\">";
                                $count++;
                            }
                        }
                    }
                    for ($i = $count; $i < 4; $i++) {
                        echo '<div class="partner-placeholder"></div>';
                    }
                ?>
            </div>
            <div class="partner-actions align-self-stretch">
                <a href="partners.php" class="btn btn-sm mt-3">Explore our Partners</a>
            </div>
            <div><p></p></div>
        </section>

        <!-- footer -->
        <?php include 'components/footer.php'; ?>
    </body>
</html>