<?php
$pageTitle = "Gallery & Events";
$eventsPerSection = 3;

require_once __DIR__ . '/tools/dbConnection.php';
require_once __DIR__ . '/tools/events.php';

// Past and upcoming are defined once in tools/events.php.
if (eventsTableIsEmpty($conn ?? null)) {
    $pastEvents = array_slice(placeholderEvents('past'), 0, $eventsPerSection);
    $upcomingEvents = array_slice(placeholderEvents('upcoming'), 0, $eventsPerSection);
} else {
    $pastEvents = fetchEvents($conn, 'past', $eventsPerSection);
    $upcomingEvents = fetchEvents($conn, 'upcoming', $eventsPerSection);
}
?>
<!DOCTYPE html>
<html lang="en">

    <?php include 'components/header.php'; ?>

    <body>

        <!-- navigation bar -->
        <?php include 'components/navBar.php'; ?>


        <!-- gallery and events hero -->
        <main>

            <section class="hero">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <h1>Gallery & Events</h1>
                        <p>Explore moments from our events, workshops and activities. Discover how Likhwezi Technologies connects people, ideas and practical solutions through meaningful engagements with our communities and partners.</p>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="images/about-us.png" alt="Likhwezi Technologies team at an event">
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


        <?php include 'components/eventModal.php'; ?>


        <?php
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        ?>

        <!-- footer -->
        <?php include 'components/footer.php'; ?>

    </body>
</html>
