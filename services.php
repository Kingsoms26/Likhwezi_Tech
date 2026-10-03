<!-- services.php is the services page
 contains the hero section with the services wheel, the service cards and the consulting package
-->
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
                    <span>Likhwezi</span>
                    <small>Services</small>
                </div>
            </div>
        </div>

        <hr>

        <!-- services offered -->
        <!-- the cards stay pinned while the visitor scrolls and slide up one at a time -->
        <section class="service-stack" id="serviceStack">
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

        <script>
            // move the cards based on how far the visitor has scrolled through the section
            (function () {
                const stack = document.getElementById('serviceStack');
                if (!stack) return;

                const cards = Array.from(stack.querySelectorAll('.stack-card'));
                const dots = Array.from(stack.querySelectorAll('.service-stack-dots span'));
                const last = cards.length - 1;
                let ticking = false;

                // screens too short to pin the cards get the plain card list instead
                const tallEnough = window.matchMedia('(min-height: 520px)');

                stack.style.setProperty('--stack-count', cards.length);

                // ease the position so scrolling glides instead of jumping
                let current = null;
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                // how far through the section the visitor is
                function targetProgress() {
                    const rect = stack.getBoundingClientRect();
                    const scrollable = stack.offsetHeight - window.innerHeight;
                    const ratio = scrollable > 0 ? Math.min(Math.max(-rect.top / scrollable, 0), 1) : 0;
                    return ratio * last;
                }

                // place each card and highlight the active dot
                function render(progress) {
                    const active = Math.round(progress);

                    cards.forEach(function (card, i) {
                        // cards above have slid out and cards below are waiting
                        const offset = i - progress;
                        const distance = Math.min(Math.abs(offset), 1);
                        card.style.transform = 'translateY(' + (offset * 108) + '%) scale(' + (1 - distance * 0.06) + ')';
                        card.classList.toggle('is-active', i === active);
                    });

                    dots.forEach(function (dot, i) {
                        dot.classList.toggle('is-active', i === active);
                    });
                }

                // move towards the scroll position a little each frame
                function update() {
                    ticking = false;
                    if (!stack.classList.contains('is-stacked')) return;
                    const target = targetProgress();
                    current = current === null || reduceMotion ? target : current + (target - current) * 0.18;
                    if (Math.abs(target - current) < 0.001) current = target;
                    render(current);
                    if (current !== target) requestUpdate();
                }

                function requestUpdate() {
                    if (!ticking) {
                        ticking = true;
                        window.requestAnimationFrame(update);
                    }
                }

                // switch between pinned cards and the plain list
                function setMode() {
                    const stacked = tallEnough.matches;
                    stack.classList.toggle('is-stacked', stacked);
                    current = null;
                    if (stacked) {
                        requestUpdate();
                    } else {
                        cards.forEach(function (card) {
                            card.style.transform = '';
                        });
                    }
                }

                if (tallEnough.addEventListener) {
                    tallEnough.addEventListener('change', setMode);
                } else {
                    tallEnough.addListener(setMode);
                }
                window.addEventListener('scroll', requestUpdate, { passive: true });
                window.addEventListener('resize', requestUpdate);
                setMode();

                // links from the home page like services.php#data-testing open on that card
                function showHashedCard() {
                    const slug = window.location.hash.slice(1);
                    const index = cards.findIndex(function (card) { return card.dataset.slug === slug; });
                    if (index === -1) return;

                    if (stack.classList.contains('is-stacked')) {
                        const sectionTop = stack.getBoundingClientRect().top + window.scrollY;
                        const scrollable = stack.offsetHeight - window.innerHeight;
                        current = null;
                        window.scrollTo(0, sectionTop + scrollable * (index / last));
                    } else {
                        cards[index].scrollIntoView();
                    }
                }

                window.addEventListener('load', showHashedCard);
                window.addEventListener('hashchange', showHashedCard);
            })();
        </script>

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