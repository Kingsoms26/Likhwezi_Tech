<?php
$pageTitle = "Gallery & Events";

$dbAvailable = false;
$events = null;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$eventsPerPage = 6;
$totalEvents = 0;
$totalPages = 1;

/*
 * Use the database when the team's connection file exists.
 * Otherwise, keep the page working with placeholder events.
 */
$dbConnectionFile = __DIR__ . '/tools/dbConnection.php';

if (file_exists($dbConnectionFile)) {
    require_once $dbConnectionFile;

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
        $dbAvailable = true;

        $countResult = $conn->query(
            "SELECT COUNT(*) AS total
             FROM Event
             WHERE isArchived = FALSE"
        );

        if ($countResult) {
            $totalEvents = (int) $countResult->fetch_assoc()['total'];
        }

        $totalPages = max(1, (int) ceil($totalEvents / $eventsPerPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $eventsPerPage;

        $eventStmt = $conn->prepare(
            "SELECT eventID, name, description, eventDate
             FROM Event
             WHERE isArchived = FALSE
             ORDER BY eventDate DESC, eventID DESC
             LIMIT ? OFFSET ?"
        );

        if ($eventStmt) {
            $eventStmt->bind_param("ii", $eventsPerPage, $offset);
            $eventStmt->execute();
            $events = $eventStmt->get_result();
        }
    }
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

            <section class="gallery-hero">

                <div class="gallery-hero-content">

                    <p class="gallery-hero-eyebrow">Our Events & Activities</p>

                    <h1>Gallery & Events</h1>

                    <p>
                        Explore moments from our events, workshops and activities.
                        Discover how Likhwezi Technologies connects people,
                        ideas and practical solutions through meaningful
                        engagements with our communities and partners.
                    </p>

                </div>

                <div class="gallery-hero-image">
                    <img src="images/about-us.png" alt="Likhwezi Technologies team at an event">
                </div>

            </section>


            <!-- event cards -->
            <section class="gallery-section">

                <div class="gallery-heading">
                    <h2>Our Events</h2>
                    <p>
                        Select an event to view more information and a larger
                        image. Six events are displayed on each page.
                    </p>
                </div>

                <div class="gallery-grid">

<?php if ($dbAvailable && $events && $events->num_rows > 0): ?>

                    <?php while ($event = $events->fetch_assoc()): ?>

                        <?php
                        $eventImage = 'images/placeholder.webp';

                        $galleryStmt = $conn->prepare(
                            "SELECT image
                             FROM GalleryItem
                             WHERE eventID = ?
                             ORDER BY galleryItemID ASC
                             LIMIT 1"
                        );

                        if ($galleryStmt) {
                            $galleryStmt->bind_param("i", $event['eventID']);
                            $galleryStmt->execute();

                            $galleryResult = $galleryStmt->get_result();
                            $galleryItem = $galleryResult->fetch_assoc();

                            if ($galleryItem && !empty($galleryItem['image'])) {
                                $eventImage = $galleryItem['image'];
                            }

                            $galleryStmt->close();
                        }

                        $eventTitle = htmlspecialchars(
                            $event['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        $eventDescription = htmlspecialchars(
                            $event['description'] ?? 'No description available.',
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        $eventDate = $event['eventDate']
                            ? date('d F Y', strtotime($event['eventDate']))
                            : 'Date to be announced';

                        $eventDateAttribute = htmlspecialchars(
                            $eventDate,
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        $eventImageAttribute = htmlspecialchars(
                            $eventImage,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                        <article
                            class="event-card"
                            data-title="<?= $eventTitle ?>"
                            data-date="<?= $eventDateAttribute ?>"
                            data-image="<?= $eventImageAttribute ?>"
                            data-description="<?= $eventDescription ?>"
                        >

                            <button
                                type="button"
                                class="event-card-button"
                                aria-label="View <?= $eventTitle ?>"
                            >

                                <div class="event-image">
                                    <img
                                        src="<?= $eventImageAttribute ?>"
                                        alt="<?= $eventTitle ?>"
                                    >
                                </div>

                                <div class="event-content">
                                    <h3><?= $eventTitle ?></h3>

                                    <p class="event-date">
                                        <?= $eventDateAttribute ?>
                                    </p>

                                    <p class="event-short-description">
                                        <?= $eventDescription ?>
                                    </p>

                                    <span class="event-card-link">
                                        View event details
                                    </span>
                                </div>

                            </button>

                        </article>

                    <?php endwhile; ?>

<?php else: ?>

                    <!-- Temporary placeholders shown until database events are available -->

                    <article
                        class="event-card"
                        data-title="Business Strategy Workshop"
                        data-date="15 August 2026"
                        data-image="images/placeholder.webp"
                        data-description="Placeholder event used while the database connection and event records are being set up."
                    >

                        <button
                            type="button"
                            class="event-card-button"
                            aria-label="View Business Strategy Workshop"
                        >

                            <div class="event-image">
                                <img
                                    src="images/placeholder.webp"
                                    alt="Business Strategy Workshop placeholder"
                                >
                            </div>

                            <div class="event-content">
                                <h3>Business Strategy Workshop</h3>

                                <p class="event-date">
                                    15 August 2026
                                </p>

                                <p class="event-short-description">
                                    Placeholder event used while the database
                                    content is being set up.
                                </p>

                                <span class="event-card-link">
                                    View event details
                                </span>
                            </div>

                        </button>

                    </article>


                    <article
                        class="event-card"
                        data-title="Industry Conference"
                        data-date="22 August 2026"
                        data-image="images/placeholder.webp"
                        data-description="Placeholder event used while the database connection and event records are being set up."
                    >

                        <button
                            type="button"
                            class="event-card-button"
                            aria-label="View Industry Conference"
                        >

                            <div class="event-image">
                                <img
                                    src="images/placeholder.webp"
                                    alt="Industry Conference placeholder"
                                >
                            </div>

                            <div class="event-content">
                                <h3>Industry Conference</h3>

                                <p class="event-date">
                                    22 August 2026
                                </p>

                                <p class="event-short-description">
                                    Placeholder event used while the database
                                    content is being set up.
                                </p>

                                <span class="event-card-link">
                                    View event details
                                </span>
                            </div>

                        </button>

                    </article>

<?php endif; ?>

                </div>


                <!-- pagination framework -->
<?php if ($dbAvailable && $totalEvents > $eventsPerPage): ?>

                <nav class="gallery-pagination" aria-label="Gallery pages">

                    <?php if ($page > 1): ?>
                        <a
                            href="?page=<?= $page - 1 ?>"
                            class="gallery-page-link"
                            aria-label="Previous page"
                        >
                            &laquo;
                        </a>
                    <?php endif; ?>

                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>

                        <a
                            href="?page=<?= $pageNumber ?>"
                            class="gallery-page-link <?= $pageNumber === $page ? 'active' : '' ?>"
                            <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>
                        >
                            <?= $pageNumber ?>
                        </a>

                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a
                            href="?page=<?= $page + 1 ?>"
                            class="gallery-page-link"
                            aria-label="Next page"
                        >
                            &raquo;
                        </a>
                    <?php endif; ?>

                </nav>

<?php endif; ?>


            </section>

        </main>


        <!-- Expanded event modal -->
        <div class="gallery-modal" id="eventModal" aria-hidden="true">

            <div class="gallery-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

                <button type="button" class="gallery-modal-close" id="closeEventModal" aria-label="Close event details">
                    &times;
                </button>

                <img id="modalImage" class="gallery-modal-image" src="images/placeholder.webp" alt="">

                <div class="gallery-modal-body">
                    <p class="gallery-modal-eyebrow">Event Details</p>
                    <h2 id="modalTitle"></h2>
                    <p id="modalMeta" class="gallery-modal-meta"></p>
                    <p id="modalDescription"></p>
                </div>

            </div>

        </div>


        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('eventModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalImage = document.getElementById('modalImage');
                const modalMeta = document.getElementById('modalMeta');
                const modalDescription = document.getElementById('modalDescription');
                const closeButton = document.getElementById('closeEventModal');
                const eventCards = document.querySelectorAll('.event-card');

                function openEvent(card) {
                    modalTitle.textContent = card.dataset.title;
                    modalImage.src = card.dataset.image;
                    modalImage.alt = card.dataset.title;
                    modalMeta.textContent = card.dataset.date;
                    modalDescription.textContent = card.dataset.description;

                    modal.style.display = 'flex';
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('gallery-modal-open');
                    closeButton.focus();
                }

                function closeEvent() {
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('gallery-modal-open');
                }

                eventCards.forEach(function (card) {
                    const button = card.querySelector('.event-card-button');
                    button.addEventListener('click', function () {
                        openEvent(card);
                    });
                });

                closeButton.addEventListener('click', closeEvent);

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeEvent();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                        closeEvent();
                    }
                });
            });
        </script>


        <?php
        if (isset($eventStmt) && $eventStmt) {
            $eventStmt->close();
        }

        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        ?>

        <!-- footer -->
        <?php include 'components/footer.php'; ?>

    </body>
</html>
