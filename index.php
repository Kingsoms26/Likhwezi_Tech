<?php
    // index.php is the landing page for the Likhwezi Technologies public facing website
    // contains the hero section, services section, about us section, and partner section

    require_once __DIR__ . '/includes/security.php';
    require_once __DIR__ . '/includes/helpers/cache.php';
    require_once __DIR__ . '/includes/helpers/imageStorage.php';

    // fetch the partner logos from the database
    $partnerLogos = cached('home_partner_logos_ordered', 300, function () {
        $conn = db();
        $result = $conn ? $conn->query("SELECT logo FROM Partner WHERE isArchived = FALSE ORDER BY sortOrder ASC, dateAdd ASC LIMIT 4") : false;
        return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'logo') : null;
    }) ?? [];
?>

<!DOCTYPE html>
<html lang="en">
    <?php $pageTitle = "Home"; ?>
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <!-- hero section -->
        <section class="hero">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Realised Imagination through Insights</h1>
                    <p>Our mission is to help private and public sector organisations realise and deliver business value through data insights.</p>
                </div>

                <div class="p-1">
                    <a href="contact.php" class="btn btn-primary btn-sm">Book a Consultation</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="assets/images/home-img-2.webp" alt="Hero Image" width="1400" height="1016" fetchpriority="high">
            </div>

            
        </section>
        <div class="partner-container d-flex justify-content-start gap-4">
                <?php
                // display the partner logos, only the first 4
                    foreach ($partnerLogos as $logo) {
                        $logo = htmlspecialchars(imageSrc($logo, 'assets/images/partners/'));
                        echo "<img src=\"{$logo}\" alt=\"Partner logo\" class=\"partner-logo\" loading=\"lazy\">";
                    }
                    for ($i = count($partnerLogos); $i < 4; $i++) {
                        echo '<div class="partner-placeholder"></div>';
                    }
                ?>
            </div>
        <hr>

        <!-- services section -->
        <section class="section-container d-flex flex-column align-items-left py-3 mx-10">
            <div class="section-title pt-3 pb-5">Client Solutions</div>

            <!-- services bento grid display all the services offered by Likhwezi Technologies -->
            <div class="service-bento">
                <?php
                    // define the services offered by Likhwezi Technologies, each with an icon, eyebrow text, name, description, and optional tags.
                    $services = [
                        ['icon' => 'bi-diagram-3', 'eyebrow' => "Tomorrow's Direction", 'name' => 'Enterprise Architecture', 'description' => 'Analyse, design, plan and implement enterprise analysis to successfully execute on business strategies.', 'tags' => ['Business', 'Application', 'Data', 'Technology']],
                        ['icon' => 'bi-compass', 'eyebrow' => 'The Game Plan', 'name' => 'Strategic Advisory', 'description' => 'Unbiased advice on high-level decisions.'],
                        ['icon' => 'bi-database', 'eyebrow' => 'Drive Efficiency', 'name' => 'Data Management', 'description' => 'Deliver, protect and enhance the value of data.'],
                        ['icon' => 'bi-clipboard-check', 'eyebrow' => '20/20 Sight', 'name' => 'Data Testing', 'description' => 'Accurate, quality, fit-for-purpose data.'],
                        ['icon' => 'bi-rocket-takeoff', 'eyebrow' => 'Span the Enterprise', 'name' => 'Solution Delivery', 'description' => 'Transition to new states and realise the benefits.'],
                    ];

                    // loop through the services and display each as a tile in the bento grid
                    foreach ($services as $i => $service) {
                        $class = $i === 0 ? 'service-tile is-featured' : 'service-tile';
                        // each tile opens services.php on that service's card
                        $slug = strtolower(str_replace(' ', '-', $service['name']));
                        echo "<a href=\"services.php#{$slug}\" class=\"{$class}\">";
                        echo "<i class=\"bi {$service['icon']} service-tile-icon\" aria-hidden=\"true\"></i>";
                        echo "<div class=\"service-tile-body\">";
                        echo "<span class=\"service-tile-eyebrow\">" . htmlspecialchars($service['eyebrow']) . "</span>";
                        echo "<h3 class=\"service-tile-title\">" . htmlspecialchars($service['name']) . "</h3>";
                        echo "<p class=\"service-tile-description\">" . htmlspecialchars($service['description']) . "</p>";
                        if (!empty($service['tags'])) {
                            echo "<ul class=\"service-tile-tags\">";
                            foreach ($service['tags'] as $tag) {
                                echo "<li>" . htmlspecialchars($tag) . "</li>";
                            }
                            echo "</ul>";
                        }
                        echo "<span class=\"service-tile-link\">Learn more</span>";
                        echo "</div></a>";
                    }
                ?>
            </div>
        </section>

        <hr>

        <!-- About Us Section -->
        <!-- About Us section with a brief description of Likhwezi Technologies and a link to the full profile -->
        <section class="section-container d-flex flex-column align-items-left py-3 mx-10">
            <div class="section-title py-2">About Us</div>
            <div class="about-container d-flex align-items-stretch gap-4 pt-4">
                <div class="about-content d-flex flex-column gap-2">
                    <p>Likhwezi Technologies is a 100% black-owned professional services consultancy based in Fourways, Gauteng. We work with organisations whose data has outgrown the way it is currently managed.</p>
                    <p>Our consultants come from delivery backgrounds, not slide decks. That means we stay through implementation, hand the system over to your team, and leave documentation they can actually use.</p>
                    <a href="about.php" class="btn btn-sm align-self-start mt-2">Read our Full Profile</a>
                </div>
                <div class="about-image">
                    <img src="assets/images/about-img/about-us.webp" alt="About Us Image" width="938" height="602" loading="lazy">
                </div>
            </div>
        </section>

        <hr>

        <!-- partner section 
            <section class="section-container d-flex flex-column align-items-left py-3 mx-10">
                
                <div class="partner-actions align-self-stretch">
                    <a href="partners.php" class="btn btn-sm mt-3">Explore our Partners</a>
                </div>
    
                <!-- Empty div to ensure the section has some space between the partners section and the footer -->
                <div><p></p></div>
            </section>
        -->
        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>
