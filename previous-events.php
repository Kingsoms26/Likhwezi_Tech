<?php
$pageTitle = "Previous Events";

$dbAvailable = false;
$events = null;

$eventsPerPage = 6;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$totalEvents = 0;
$totalPages = 1;

$dummyEvents = [
    [
        'name' => 'Business Strategy Workshop',
        'date' => '15 August 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'A workshop focused on strategic planning and improving business performance.'
    ],
    [
        'name' => 'Industry Conference',
        'date' => '22 August 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'Professionals gathered to exchange knowledge and discuss emerging trends.'
    ],
    [
        'name' => 'Digital Skills Workshop',
        'date' => '18 April 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'A practical workshop focused on developing digital skills and knowledge.'
    ],
    [
        'name' => 'Technology Awareness Day',
        'date' => '05 March 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'An event focused on technology awareness and digital opportunities.'
    ],
    [
        'name' => 'Community Outreach Event',
        'date' => '20 February 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'A community engagement event focused on meaningful local connections.'
    ],
    [
        'name' => 'Digital Transformation Seminar',
        'date' => '12 June 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'A seminar exploring practical approaches to digital transformation.'
    ],
    [
        'name' => 'Community Technology Day',
        'date' => '28 May 2026',
        'image' => 'images/placeholder.webp',
        'description' => 'A previous community technology event showcasing practical digital solutions.'
    ]
];

/* Keep fallback archive events in the same newest-first order as the database query. */
usort($dummyEvents, function ($a, $b) {
    return strtotime($b['date']) <=> strtotime($a['date']);
});

$dbConnectionFile = __DIR__ . '/tools/dbConnection.php';

if (file_exists($dbConnectionFile)) {
    require_once $dbConnectionFile;

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
        $dbAvailable = true;

        $countStmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM Event
             WHERE isArchived = TRUE"
        );

        if ($countStmt) {
            $countStmt->execute();
            $countResult = $countStmt->get_result();

            if ($countResult) {
                $countRow = $countResult->fetch_assoc();
                $totalEvents = (int) ($countRow['total'] ?? 0);
            }

            $countStmt->close();
        }

        if ($totalEvents > 0) {
            $totalPages = (int) ceil($totalEvents / $eventsPerPage);

            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $offset = ($page - 1) * $eventsPerPage;

            $eventStmt = $conn->prepare(
                "SELECT eventID, name, description, eventDate
                 FROM Event
                 WHERE isArchived = TRUE
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
}

/*
 * If TiDB has no archived events, use the dummy events above.
 * Nothing is inserted into or changed in the database.
 */
if (!$dbAvailable || $totalEvents === 0) {
    $totalEvents = count($dummyEvents);
    $totalPages = (int) ceil($totalEvents / $eventsPerPage);

    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $eventsPerPage;
    $pageEvents = array_slice($dummyEvents, $offset, $eventsPerPage);
} else {
    $pageEvents = null;
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
<h1>Previous Events</h1>

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
                    <div>
                        <h2>Our Events</h2>
                        <p>Select an event to view more information</p>
                    </div>

                </div>

                <?php if ($totalEvents > 0): ?>
                    <p class="archive-result-count">
                        Showing
                        <?= (($page - 1) * $eventsPerPage) + 1 ?>
                        -
                        <?= min($page * $eventsPerPage, $totalEvents) ?>
                        of
                        <?= $totalEvents ?>
                        previous events
                    </p>
                <?php endif; ?>

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

        $eventTitle = htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8');
        $eventDescription = htmlspecialchars(
            $event['description'] ?? 'No description available.',
            ENT_QUOTES,
            'UTF-8'
        );

        $eventDate = !empty($event['eventDate'])
            ? date('d F Y', strtotime($event['eventDate']))
            : 'Date to be announced';

        $eventDateAttribute = htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8');
        $eventImageAttribute = htmlspecialchars($eventImage, ENT_QUOTES, 'UTF-8');
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
                    <img src="<?= $eventImageAttribute ?>" alt="<?= $eventTitle ?>">
                </div>

                <div class="event-content">
                    <h3><?= $eventTitle ?></h3>
                    <p ><?= $eventDateAttribute ?></p>
                    <p ><?= $eventDescription ?></p>
                    <span class="event-card-link">View event details</span>
                </div>
            </button>
        </article>

    <?php endwhile; ?>

<?php else: ?>

    <?php foreach ($pageEvents as $event): ?>
        <?php
        $eventTitle = htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8');
        $eventDate = htmlspecialchars($event['date'], ENT_QUOTES, 'UTF-8');
        $eventImage = htmlspecialchars($event['image'], ENT_QUOTES, 'UTF-8');
        $eventDescription = htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8');
        ?>

        <article
            class="event-card"
            data-title="<?= $eventTitle ?>"
            data-date="<?= $eventDate ?>"
            data-image="<?= $eventImage ?>"
            data-description="<?= $eventDescription ?>"
        >
            <button
                type="button"
                class="event-card-button"
                aria-label="View <?= $eventTitle ?>"
            >
                <div class="event-image">
                    <img src="<?= $eventImage ?>" alt="<?= $eventTitle ?>">
                </div>

                <div class="event-content">
                    <h3><?= $eventTitle ?></h3>
                    <p ><?= $eventDate ?></p>
                    <p ><?= $eventDescription ?></p>
                    <span class="event-card-link">View event details</span>
                </div>
            </button>
        </article>

    <?php endforeach; ?>

<?php endif; ?>

                </div>

                <?php if ($totalPages > 1): ?>
    <nav class="gallery-pagination" aria-label="Previous events pages">

        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="btn btn-outline-dark" rel="prev">Previous</a>
        <?php endif; ?>

        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
            <a
                href="?page=<?= $pageNumber ?>"
                class="btn <?= $pageNumber === $page ? 'btn-primary' : 'btn-outline-dark' ?>"
                <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>
            >
                <?= $pageNumber ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="btn btn-outline-dark" rel="next">Next</a>
        <?php endif; ?>

    </nav>
<?php endif; ?>
                <div class="gallery-archive">
                    <h3>Return To Previous Page</h3>
                    <a href="gallery.php" class="btn btn-primary">View Main Gallery & Events</a>
                </div>
            </section>

        </main>


        <!-- Expanded event modal -->
        <div class="gallery-modal" id="eventModal" aria-hidden="true">

            <div class="gallery-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

                <button type="button" class="btn-close gallery-modal-close" id="closeEventModal" aria-label="Close event details"></button>

                <img id="modalImage" class="gallery-modal-image" src="images/placeholder.webp" alt="">

                <div class="gallery-modal-body">
<h2 id="modalTitle"></h2>
                    <p id="modalMeta" ></p>
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
