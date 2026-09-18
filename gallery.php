<?php
$pageTitle = "Gallery & Events";

$dbAvailable = false;
$events = null;

/*
use database when contents are available
 */
$dbConnectionFile = __DIR__ . '/tools/dbConnection.php';

if (file_exists($dbConnectionFile)) {
    require_once $dbConnectionFile;

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
        $dbAvailable = true;

        $eventStmt = $conn->prepare(
            "SELECT eventID, name, description, eventDate
             FROM Event
             WHERE isArchived = FALSE
             ORDER BY eventDate ASC, eventID ASC"
        );

        if ($eventStmt) {
            $eventStmt->execute();
            $events = $eventStmt->get_result();
        }
    }
}

$currentEvents = [];
$upcomingEvents = [];

if ($events) {
    while ($event = $events->fetch_assoc()) {
        if (!empty($event['eventDate']) && strtotime($event['eventDate']) <= strtotime('today')) {
            if (count($currentEvents) < 3) { $currentEvents[] = $event; }
        } else {
            if (count($upcomingEvents) < 3) { $upcomingEvents[] = $event; }
        }
        if (count($currentEvents) >= 3 && count($upcomingEvents) >= 3) { break; }
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
                    <div>
                        <h2>Our Events</h2>
                        <p>Select an event to view more information</p>
                    </div>
                    <a href="previous-events.php" class="btn btn-primary">View Previous Events Collection</a>
                </div>

<?php
function renderEventCards($events, $dbAvailable, $conn) {
    foreach ($events as $event) {
        $eventImage = 'images/placeholder.webp';
        if ($dbAvailable && !empty($event['eventID'])) {
            $galleryStmt = $conn->prepare("SELECT image FROM GalleryItem WHERE eventID = ? ORDER BY galleryItemID ASC LIMIT 1");
            if ($galleryStmt) {
                $galleryStmt->bind_param("i", $event['eventID']);
                $galleryStmt->execute();
                $galleryItem = $galleryStmt->get_result()->fetch_assoc();
                if ($galleryItem && !empty($galleryItem['image'])) { $eventImage = $galleryItem['image']; }
                $galleryStmt->close();
            }
        }
        $eventTitle = htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8');
        $eventDescription = htmlspecialchars($event['description'] ?? 'No description available.', ENT_QUOTES, 'UTF-8');
        $eventDate = !empty($event['eventDate']) ? date('d F Y', strtotime($event['eventDate'])) : 'Date to be announced';
        $eventDateAttribute = htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8');
        $eventImageAttribute = htmlspecialchars($eventImage, ENT_QUOTES, 'UTF-8');
?>
                    <article class="event-card" data-title="<?= $eventTitle ?>" data-date="<?= $eventDateAttribute ?>" data-image="<?= $eventImageAttribute ?>" data-description="<?= $eventDescription ?>">
                        <button type="button" class="event-card-button" aria-label="View <?= $eventTitle ?>">
                            <div class="event-image"><img src="<?= $eventImageAttribute ?>" alt="<?= $eventTitle ?>"></div>
                            <div class="event-content">
                                <h3><?= $eventTitle ?></h3>
                                <p ><?= $eventDateAttribute ?></p>
                                <p ><?= $eventDescription ?></p>
                                <span class="event-card-link">View event details</span>
                            </div>
                        </button>
                    </article>
<?php
    }
}

$placeholderCurrent = [
    ['name'=>'Business Strategy Workshop','description'=>'Placeholder event','eventDate'=>'2026-09-15'],
    ['name'=>'Business Strategy Workshop','description'=>'Placeholder event','eventDate'=>'2026-09-15'],
    ['name'=>'Business Strategy Workshop','description'=>'Placeholder event','eventDate'=>'2026-09-15']

];
$placeholderUpcoming = [
    ['name'=>'Business Strategy Workshop','description'=>'Placeholder event','eventDate'=>'2026-09-15'],
    ['name'=>'Business Strategy Workshop','description'=>'Placeholder event','eventDate'=>'2026-09-15'],
    ['name'=>'Business Strategy Workshop','description'=>'Placeholder event','eventDate'=>'2026-09-15']
];
?>

                <div class="gallery-event-section">
                    <div class="gallery-event-section-heading">
                        <span class="gallery-section-icon" aria-hidden="true">▣</span>
                        <div>
                            <h3 class="gallery-event-section-title">Current Events</h3>
                        </div>
                        <span class="gallery-section-line" aria-hidden="true"></span>
                    </div>
                    <div class="gallery-grid">
<?php renderEventCards(count($currentEvents) ? $currentEvents : $placeholderCurrent, $dbAvailable, $conn ?? null); ?>
                    </div>
                </div>

                <div class="gallery-event-section">
                    <div class="gallery-event-section-heading">
                        <span class="gallery-section-icon" aria-hidden="true">▣</span>
                        <div>
                            <h3 class="gallery-event-section-title">Upcoming Events</h3>
                        </div>
                        <span class="gallery-section-line" aria-hidden="true"></span>
                    </div>
                    <div class="gallery-grid">
<?php renderEventCards(count($upcomingEvents) ? $upcomingEvents : $placeholderUpcoming, $dbAvailable, $conn ?? null); ?>
                    </div>

                    <div class="gallery-archive">
                        <h3>Explore More Events Current/Upcoming Events</h3>
                        <a href="all-events.php" class="btn btn-primary">View More Events</a>
                    </div>
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
