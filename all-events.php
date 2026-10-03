<?php
// all-events.php lists every upcoming event, soonest first
// the rules for what counts as upcoming live in includes/helpers/events.php
$eventType = 'upcoming';
$pageTitle = 'Upcoming Events';
$listingIntro = "See what's next on our calendar, from workshops and seminars to community and Cyber Young Minds events.";

include __DIR__ . '/includes/components/eventListing.php';
