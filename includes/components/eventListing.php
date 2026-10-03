<?php
    // eventListing.php is the paged list of past or upcoming events
    // the page sets $eventType, $pageTitle and $listingIntro first, see previous-events.php and all-events.php
    require_once __DIR__ . '/../helpers/cache.php';
    require_once __DIR__ . '/../helpers/events.php';

    // work out which page we are on
    $eventsPerPage = 6;
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

    // count the events, cached for 5 minutes so most visits skip the database
    // no count means there are no events yet or no database so placeholders are shown
    $eventCount = cached("events_count_$eventType", 300, function () use ($eventType) {
        $conn = db();
        if (!$conn) {
            return null;
        }
        return ['total' => eventsTableIsEmpty($conn) ? null : countEvents($conn, $eventType)];
    });

    // work out the pages
    $usingPlaceholders = !isset($eventCount['total']);
    $totalEvents = $usingPlaceholders ? count(placeholderEvents($eventType)) : $eventCount['total'];
    $totalPages = max(1, (int) ceil($totalEvents / $eventsPerPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $eventsPerPage;

    // fetch the events for this page
    $events = $usingPlaceholders
        ? array_slice(placeholderEvents($eventType), $offset, $eventsPerPage)
        : cached("events_{$eventType}_page_$page", 300, function () use ($eventType, $eventsPerPage, $offset) {
            $conn = db();
            return $conn ? fetchEvents($conn, $eventType, $eventsPerPage, $offset) : null;
        }) ?? [];

    $eventLabel = $eventType === 'past' ? 'past events' : 'upcoming events';
?>

<!DOCTYPE html>
<html lang="en">

    <?php include __DIR__ . '/header.php'; ?>

    <body>

        <!-- navigation bar -->
        <?php include __DIR__ . '/navBar.php'; ?>

        <main>

            <!-- hero section -->
            <section class="hero">
                <div class="hero-content d-grid gap-3 row-gap-3">
                    <div class="p-1">
                        <h1><?= htmlspecialchars($pageTitle) ?></h1>
                        <p><?= htmlspecialchars($listingIntro) ?></p>
                    </div>
                    <div class="p-1">
                        <a href="gallery.php" class="btn btn-sm">Back to Gallery &amp; Events</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/about-img/about-us.webp" alt="Likhwezi Technologies team at an event" width="938" height="602" fetchpriority="high">
                </div>
            </section>

            <hr>

            <!-- event cards -->
            <section class="gallery-section">
                <div class="gallery-heading">
                    <div>
                        <h2><?= htmlspecialchars($pageTitle) ?></h2>
                        <p>
                            <?php if ($totalEvents > 0): ?>
                                Showing <?= $offset + 1 ?>&ndash;<?= min($offset + $eventsPerPage, $totalEvents) ?>
                                of <?= $totalEvents ?> <?= $eventLabel ?>. Select an event to see more.
                            <?php else: ?>
                                Select an event to see more.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if (empty($events)): ?>
                    <p class="gallery-empty">
                        <?= $eventType === 'past'
                            ? 'No past events to show yet.'
                            : 'No upcoming events right now. Check back soon, or <a href="contact.php">get in touch</a> to hear about new ones first.' ?>
                    </p>
                <?php else: ?>
                    <div class="gallery-grid">
                        <?php renderEventCards($events); ?>
                    </div>
                <?php endif; ?>

                <!-- page links -->
                <?php if ($totalPages > 1): ?>
                    <nav class="gallery-pagination" aria-label="<?= ucfirst($eventLabel) ?> pages">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="btn btn-outline-dark" rel="prev">Previous</a>
                        <?php endif; ?>

                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <a href="?page=<?= $pageNumber ?>" class="btn <?= $pageNumber === $page ? 'btn-primary' : 'btn-outline-dark' ?>" <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="btn btn-outline-dark" rel="next">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>

        </main>

        <!-- event details popup -->
        <?php include __DIR__ . '/eventModal.php'; ?>

        <!-- footer -->
        <?php include __DIR__ . '/footer.php'; ?>

    </body>
</html>
