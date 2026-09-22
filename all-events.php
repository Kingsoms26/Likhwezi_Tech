<?php
// Every upcoming event (active, dated today or later), soonest first. Rules live in tools/events.php.
$eventType = 'upcoming';
$pageTitle = 'Upcoming Events';
$listingIntro = "See what's next on our calendar, from workshops and seminars to community and Cyber Young Minds events.";

include __DIR__ . '/components/eventListing.php';
