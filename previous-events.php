<?php
// previous-events.php lists every past event, newest first
// the rules for what counts as past live in includes/helpers/events.php
$eventType = 'past';
$pageTitle = 'Past Events';
$listingIntro = 'Look back at the workshops, talks and community days Likhwezi Technologies has hosted and taken part in.';

include __DIR__ . '/includes/components/eventListing.php';
