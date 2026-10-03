<?php
    // gallery.php is the gallery and events page
    // contains the photo carousel, the latest past events and the next upcoming events
    $pageTitle = "Gallery & Events";
    $eventsPerSection = 3;

    require_once __DIR__ . '/includes/helpers/cache.php';
    require_once __DIR__ . '/includes/helpers/events.php';

    // fetch the past and upcoming events, cached for 5 minutes so most visits skip the database
    // past and upcoming are defined in includes/helpers/events.php
    $galleryEvents = cached("gallery_events_$eventsPerSection", 300, function () use ($eventsPerSection) {
        $conn = db();
        if (!$conn) {
            return null;
        }
        if (eventsTableIsEmpty($conn)) {
            return ['past' => null, 'upcoming' => null];
        }
        return [
            'past' => fetchEvents($conn, 'past', $eventsPerSection),
            'upcoming' => fetchEvents($conn, 'upcoming', $eventsPerSection),
        ];
    });

    // show placeholders when there are no events yet or no database
    $pastEvents = $galleryEvents['past'] ?? array_slice(placeholderEvents('past'), 0, $eventsPerSection);
    $upcomingEvents = $galleryEvents['upcoming'] ?? array_slice(placeholderEvents('upcoming'), 0, $eventsPerSection);
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'includes/components/header.php'; ?>

    <body>
        <!-- navigation bar -->
        <?php include 'includes/components/navBar.php'; ?>

        <main>

            <!-- hero section -->
            <section class="hero">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <h1>Gallery & Events</h1>
                        <p>Explore moments from our events, workshops and activities. Discover how Likhwezi Technologies connects people, ideas and practical solutions through meaningful engagements with our communities and partners.</p>
                    </div>
                </div>

                <!-- photo carousel, the same one as on the cym page -->
                <div class="hero-image">
                    <?php
                        $carouselID = 'galleryPhotoCarousel';
                        $carouselPhotos = [
                            ['src' => 'assets/images/about-img/school-footage.webp', 'alt' => 'Likhwezi Technologies team at an event'],
                            ['src' => 'assets/images/about-img/school-footage2.webp', 'alt' => 'Likhwezi Technologies team at an event'],
                            // portrait photo so keep the crop on the people rather than the sky
                            ['src' => 'assets/images/about-img/group-photo.webp', 'alt' => 'Likhwezi Technologies team at an event', 'position' => 'center 72%'],
                        ];
                        include 'includes/components/photoCarousel.php';
                    ?>
                </div>
            </section>

            <hr>

            <!-- past events -->
            <section class="gallery-section">
                <div class="gallery-heading">
                    <div>
                        <h2>Past Events</h2>
                        <p>Look back at our recent workshops, talks and community days. Select an event to see more.</p>
                    </div>
                    <a href="previous-events.php" class="btn btn-sm">View All Past Events</a>
                </div>

                <?php if (empty($pastEvents)): ?>
                    <p class="gallery-empty">No past events to show yet.</p>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php renderEventCards($pastEvents); ?>
                    </div>
                <?php endif; ?>
            </section>

            <hr>

            <!-- upcoming events -->
            <section class="gallery-section">
                <div class="gallery-heading">
                    <div>
                        <h2>Upcoming Events</h2>
                        <p>What's next on our calendar. Select an event to see the details.</p>
                    </div>
                    <a href="all-events.php" class="btn btn-sm">View All Upcoming Events</a>
                </div>

                <?php if (empty($upcomingEvents)): ?>
                    <p class="gallery-empty">No upcoming events right now. Check back soon, or <a href="contact.php">get in touch</a> to hear about new ones first.</p>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php renderEventCards($upcomingEvents); ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- event details popup -->
        <?php include 'includes/components/eventModal.php'; ?>

        <!-- footer -->
        <?php include 'includes/components/footer.php'; ?>
    </body>
</html>
