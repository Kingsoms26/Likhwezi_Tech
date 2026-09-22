<?php
/*
 * Paginated list of one type of event ('past' or 'upcoming').
 * The including page sets $eventType, $pageTitle and $listingIntro first,
 * e.g. previous-events.php (past) and all-events.php (upcoming).
 */
require_once __DIR__ . '/../tools/dbConnection.php';
require_once __DIR__ . '/../tools/events.php';

$eventsPerPage = 6;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$usingPlaceholders = eventsTableIsEmpty($conn ?? null);
$totalEvents = $usingPlaceholders ? count(placeholderEvents($eventType)) : countEvents($conn, $eventType);
$totalPages = max(1, (int) ceil($totalEvents / $eventsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $eventsPerPage;

$events = $usingPlaceholders
    ? array_slice(placeholderEvents($eventType), $offset, $eventsPerPage)
    : fetchEvents($conn, $eventType, $eventsPerPage, $offset);

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
                    <img src="images/about-us.png" alt="Likhwezi Technologies team at an event">
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

                <?php if ($totalPages > 1): ?>
                    <nav class="gallery-pagination" aria-label="<?= ucfirst($eventLabel) ?> pages">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="btn btn-outline-dark" rel="prev">Previous</a>
                        <?php endif; ?>

                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <a
                                href="?page=<?= $pageNumber ?>"
                                class="btn <?= $pageNumber === $page ? 'btn-primary' : 'btn-outline-dark' ?>"
                                <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>
                            ><?= $pageNumber ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="btn btn-outline-dark" rel="next">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>

        </main>

        <?php include __DIR__ . '/eventModal.php'; ?>

        <?php
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        ?>

        <!-- footer -->
        <?php include __DIR__ . '/footer.php'; ?>

    </body>
</html>
