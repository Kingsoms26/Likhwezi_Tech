<?php
/*
 * Shared event logic for gallery.php, previous-events.php and all-events.php.
 *
 * This is the single definition of what counts as a "past" or an "upcoming"
 * event, so every page that lists events pulls from the database the same way:
 *   past     - archived, or dated before today. Newest first.
 *   upcoming - not archived, dated today or later (undated events last). Soonest first.
 */

const EVENT_FILTERS = [
    'past' => [
        'where' => 'e.isArchived = TRUE OR e.eventDate < CURDATE()',
        'order' => 'e.eventDate DESC, e.eventID DESC',
    ],
    'upcoming' => [
        'where' => 'e.isArchived = FALSE AND (e.eventDate >= CURDATE() OR e.eventDate IS NULL)',
        'order' => 'e.eventDate IS NULL, e.eventDate ASC, e.eventID ASC',
    ],
];

function eventsDbAvailable($conn): bool {
    return isset($conn) && $conn instanceof mysqli && !$conn->connect_errno;
}

/* True when the Event table has no rows at all, so the pages show placeholders instead. */
function eventsTableIsEmpty($conn): bool {
    if (!eventsDbAvailable($conn)) {
        return true;
    }

    $result = $conn->query("SELECT 1 FROM Event LIMIT 1");
    return !$result || $result->num_rows === 0;
}

function countEvents($conn, string $type): int {
    if (!eventsDbAvailable($conn)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM Event e WHERE " . EVENT_FILTERS[$type]['where']);
    return $result ? (int) $result->fetch_assoc()['total'] : 0;
}

/* Returns events of the given type, each with its first gallery image (or the placeholder). */
function fetchEvents($conn, string $type, int $limit, int $offset = 0): array {
    if (!eventsDbAvailable($conn)) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT
            e.eventID,
            e.name,
            e.description,
            e.eventDate,
            COALESCE((
                SELECT gi.image
                FROM GalleryItem gi
                WHERE gi.eventID = e.eventID
                ORDER BY gi.galleryItemID ASC
                LIMIT 1
            ), 'images/placeholder.webp') AS image
         FROM Event e
         WHERE " . EVENT_FILTERS[$type]['where'] . "
         ORDER BY " . EVENT_FILTERS[$type]['order'] . "
         LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $events;
}

/* Example events, only shown while the database has no events at all. */
function placeholderEvents(string $type): array {
    $events = [
        'past' => [
            ['name' => 'Industry Conference', 'description' => 'Professionals gathered to exchange knowledge and discuss emerging trends.', 'eventDate' => '2026-08-22'],
            ['name' => 'Business Strategy Workshop', 'description' => 'A workshop focused on strategic planning and improving business performance.', 'eventDate' => '2026-08-15'],
            ['name' => 'Digital Transformation Seminar', 'description' => 'A seminar exploring practical approaches to digital transformation.', 'eventDate' => '2026-06-12'],
            ['name' => 'Community Technology Day', 'description' => 'A community technology event showcasing practical digital solutions.', 'eventDate' => '2026-05-28'],
            ['name' => 'Digital Skills Workshop', 'description' => 'A practical workshop focused on developing digital skills and knowledge.', 'eventDate' => '2026-04-18'],
            ['name' => 'Technology Awareness Day', 'description' => 'An event focused on technology awareness and digital opportunities.', 'eventDate' => '2026-03-05'],
            ['name' => 'Community Outreach Event', 'description' => 'A community engagement event focused on meaningful local connections.', 'eventDate' => '2026-02-20'],
        ],
        'upcoming' => [
            ['name' => 'Data Governance Masterclass', 'description' => 'A hands-on session on building data governance that teams actually follow.', 'eventDate' => '2026-10-08'],
            ['name' => 'Cyber Young Minds Hackathon', 'description' => 'Learners team up to build and pitch solutions to real community problems.', 'eventDate' => '2026-10-24'],
            ['name' => 'Year-End Partner Breakfast', 'description' => 'Reflecting on the year with our partners and planning what comes next.', 'eventDate' => '2026-11-20'],
        ],
    ];

    return array_map(function ($event) {
        return $event + ['image' => 'images/placeholder.webp'];
    }, $events[$type]);
}

/* Event cards used by every events page; clicking one opens components/eventModal.php. */
function renderEventCards(array $events): void {
    foreach ($events as $event) {
        $title = htmlspecialchars($event['name'], ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($event['description'] ?? 'No description available.', ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars(
            !empty($event['eventDate']) ? date('d F Y', strtotime($event['eventDate'])) : 'Date to be announced',
            ENT_QUOTES,
            'UTF-8'
        );
        $image = htmlspecialchars($event['image'] ?? 'images/placeholder.webp', ENT_QUOTES, 'UTF-8');
?>
                    <article class="event-card" data-title="<?= $title ?>" data-date="<?= $date ?>" data-image="<?= $image ?>" data-description="<?= $description ?>">
                        <button type="button" class="event-card-button" aria-label="View <?= $title ?>">
                            <div class="event-image"><img src="<?= $image ?>" alt="<?= $title ?>"></div>
                            <div class="event-content">
                                <p class="event-date"><?= $date ?></p>
                                <h3><?= $title ?></h3>
                                <p class="event-description"><?= $description ?></p>
                                <span class="event-card-link">View event details</span>
                            </div>
                        </button>
                    </article>
<?php
    }
}
