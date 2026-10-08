<?php
    // services.php is the services page
    // contains the hero section with the services wheel, the service cards and the consulting package
    require_once __DIR__ . '/includes/security.php';
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <!-- hero section -->
        <div class="hero hero-wheel">
            <div class="hero-content d-grid gap-3 row-gap-3">
                <div>
                    <h1>Our Services</h1>
                    <p>Turning your data into decisions that move the business.</p>
                    <p>We design bespoke solutions, from architecture and data governance to testing and delivery, built around the way your organisation works.</p>
                </div>

                <div class="btn-group">
                    <div class="p-1">
                        <a href="contact.php" class="btn btn-primary btn-sm">Book a Consultation</a>
                    </div>
                    <div class="p-1">
                        <a href="#consulting-package" class="btn btn-ghost btn-sm">View consulting package</a>
                    </div>
                </div>
            </div>

            <!-- rotating services wheel -->
            <div class="services-wheel-wrap" aria-label="Services we offer">
                <div class="services-wheel">
                    <?php
                        // place each service evenly around the wheel
                        $wheelServices = [
                            ['icon' => 'bi-diagram-3', 'name' => 'Enterprise Architecture'],
                            ['icon' => 'bi-compass', 'name' => 'Strategic Advisory'],
                            ['icon' => 'bi-database', 'name' => 'Data Management'],
                            ['icon' => 'bi-clipboard-check', 'name' => 'Data Testing'],
                            ['icon' => 'bi-rocket-takeoff', 'name' => 'Solution Delivery'],
                        ];
                        $wheelCount = count($wheelServices);
                        foreach ($wheelServices as $i => $service) {
                            $angle = (360 / $wheelCount) * $i;
                            echo "<div class=\"wheel-item\" style=\"--angle: {$angle}deg\">";
                            echo "<div class=\"wheel-item-inner\">";
                            echo "<i class=\"bi {$service['icon']}\"></i>";
                            echo "<span>" . htmlspecialchars($service['name']) . "</span>";
                            echo "</div></div>";
                        }
                    ?>
                </div>
                <div class="wheel-hub">
                    <img src="assets/images/logo/logo-branding.webp" alt="Likhwezi Technologies" class="wheel-hub-logo">
                </div>
            </div>
        </div>

        <hr>

        <!-- services offered -->
        <!-- horizontal scrolling -->
        <!-- the cards stay pinned while the visitor scrolls and slide across one at a time -->
        <section class="service-stack section-centered" id="serviceStack">
            <div class="service-stack-sticky">
                <div class="section-title service-stack-heading">What We Offer</div>
                <p class="service-stack-intro">Likhwezi Technologies is a 100% black-owned consultancy focused on enterprise data systems. We build bespoke solutions tailored to the unique needs of each client.</p>
                <div class="service-stack-deck">
                    <?php
                        // fetch the service cards, admins edit them on staff/manageContent.php
                        require_once __DIR__ . '/includes/helpers/siteContent.php';
                        $stackServices = siteServices();
                        $stackCount = count($stackServices);
                        foreach ($stackServices as $i => $service) {
                            $number = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            $total = str_pad($stackCount, 2, '0', STR_PAD_LEFT);
                            $slug = strtolower(str_replace(' ', '-', $service['name']));
                            echo "<article class=\"stack-card\" data-index=\"{$i}\" data-slug=\"" . htmlspecialchars($slug) . "\">";
                            echo "<div class=\"stack-card-top\"><i class=\"bi " . htmlspecialchars($service['icon']) . "\"></i><span class=\"stack-card-count\">{$number} / {$total}</span></div>";
                            echo "<span class=\"stack-card-tagline\">" . htmlspecialchars($service['tagline']) . "</span>";
                            echo "<h2 class=\"stack-card-title\">" . htmlspecialchars($service['name']) . "</h2>";
                            echo "<p class=\"stack-card-text\">" . htmlspecialchars($service['description']) . "</p>";
                            echo "<ul class=\"stack-card-tags\" aria-label=\"Includes\">";
                            foreach ($service['includes'] as $item) {
                                echo "<li>" . htmlspecialchars($item) . "</li>";
                            }
                            echo "</ul>";
                            echo "</article>";
                        }
                    ?>
                </div>

                <!-- a dot for each card -->
                <div class="service-stack-dots" aria-hidden="true">
                    <?php for ($i = 0; $i < $stackCount; $i++) { echo "<span></span>"; } ?>
                </div>

                <div class="service-stack-cta-wrap">
                    <a href="contact.php" class="btn stack-card-cta">Book a consultation</a>
                </div>
            </div>
        </section>

        <script src="assets/js/services.js?v=<?= filemtime(__DIR__ . '/assets/js/services.js') ?>"></script>

        <!-- consulting package -->
        <section class="package" id="consulting-package">
            <div class="package-inner">
                <div class="package-intro">
                    <span class="package-eyebrow">Consulting Package</span>
                    <h2 class="package-title">Our Consulting Package</h2>
                    <p class="package-text">One engagement that takes you from understanding your data to running a working solution. Our consultants research, recommend, build and test alongside your team, then stay until your people can run it themselves.</p>
                    <a href="contact.php?enquiry=consulting-package#contactForm" class="btn package-cta">Request this package</a>
                </div>

                <div class="package-includes">
                    <h3 class="package-includes-title">Includes</h3>
                    <ul class="package-list">
                        <?php
                            // what the package includes
                            $packageItems = [
                                'Research and analytics',
                                'Strategic recommendations',
                                'Solution design and implementation',
                                'Testing and evaluation',
                                'Hands-on guidance',
                                'Industry best practices',
                                'Dedicated data and management consultants',
                            ];
                            foreach ($packageItems as $item) {
                                echo "<li><i class=\"bi bi-check-circle-fill\" aria-hidden=\"true\"></i><span>" . htmlspecialchars($item) . "</span></li>";
                            }
                        ?>
                    </ul>
                </div>
            </div>
        </section>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>