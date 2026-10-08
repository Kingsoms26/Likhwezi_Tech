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
        <section class="hero hero-centered hero-home">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div class="p-1">
                    <h1>Delivering IT solutions that drive business value.</h1>
                    <p>Likhwezi Technologies is a consultancy firm. We design enterprise architecture, put data governance and quality in place, test data before it reaches your reports, and stay on to deliver the change.</p>
                </div>

                <div class="btn-group">
                    <div class="p-1">
                        <a href="contact.php" class="btn btn-primary btn-sm">Book a Consultation</a>
                    </div>
                    <div class="p-1">
                        <a href="about.php" class="btn btn-ghost btn-sm">View Our Story</a>
                    </div>
                </div>
            </div>


            
        </section>
        <!-- scrolling partner logos -->
        <section class="partner-banner" aria-label="Partner logos">
            <div class="partner-track">
                <?php for ($group = 0; $group < 2; $group++): ?>
                    <div class="partner-group"<?= $group === 1 ? ' aria-hidden="true"' : '' ?>>
                        <?php for ($i = 0; $i < 3; $i++): ?>
                            <?php
                                // display the partner logos, only the first 4
                                // repeats get an empty alt so screen readers only read each logo once
                                $alt = ($group === 0 && $i === 0) ? 'Partner logo' : '';
                                foreach ($partnerLogos as $logo) {
                                    $logo = htmlspecialchars(imageSrc($logo, 'assets/images/partners/'));
                                    echo "<img src=\"{$logo}\" alt=\"{$alt}\" class=\"partner-logo\" loading=\"lazy\">";
                                }
                                for ($p = count($partnerLogos); $p < 4; $p++) {
                                    echo '<div class="partner-placeholder"></div>';
                                }
                            ?>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </section>
        <hr>

        <!-- services section -->
        <section class="section-container section-centered d-flex flex-column py-3 mx-10">
            <div class="section-title pt-3 pb-5">What can we help you with today?</div>

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
        <section class="section-container section-centered d-flex flex-column pt-3 pb-5 mx-10">
            <div class="section-title py-2">About Us</div>
            <div class="about-container pt-4">
                <!-- mission on top with the company text under it -->
                <div class="about-content d-flex flex-column gap-4">
                    <!-- mission, same wording as about.php -->
                    <div class="about-mission">
                        <i class="bi bi-quote" aria-hidden="true"></i>
                        <span class="about-mission-label">Our Mission</span>
                        <p>To help private and public sector organisations realise and deliver business value through data insights.</p>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <p>Likhwezi Technologies is a 100% black-owned professional services consultancy based in Fourways, Gauteng. We work with organisations whose data has outgrown the way it is currently managed.</p>
                        <p>Our consultants come from delivery backgrounds, not slide decks. That means we stay through implementation, hand the system over to your team, and leave documentation they can actually use.</p>
                    </div>
                </div>

                <!-- team photo on the right -->
                <img src="assets/images/about-img/about-us.webp" class="about-team-image" width="938" height="602" loading="lazy" alt="Likhwezi Technologies team">
            </div>

            <!-- quick points about the company, a card each under the text and mission -->
            <ul class="about-points">
                <li class="about-point">
                    <i class="bi bi-buildings" aria-hidden="true"></i>
                    <h3>Trusted Partner</h3>
                    <p>A trusted advisor and partner for businesses in the private and public sectors.</p>
                </li>
                <li class="about-point">
                    <i class="bi bi-lightbulb" aria-hidden="true"></i>
                    <h3>Bespoke Solutions</h3>
                    <p>Bespoke solutions tailored to the unique needs of each client.</p>
                </li>
                <li class="about-point">
                    <i class="bi bi-mortarboard" aria-hidden="true"></i>
                    <h3>Cyber Young Minds</h3>
                    <p>Our work funds <a href="cym.php">Cyber Young Minds</a>, free coding, robotics and AI programmes for the youth.</p>
                </li>
            </ul>

            <!-- link to the full about page -->
            <div class="about-actions">
                <a href="about.php" class="btn btn-sm">Read our Full Profile</a>
            </div>
        </section>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>
