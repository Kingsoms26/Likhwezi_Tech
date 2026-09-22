<?php
// Every past event (archived, or dated before today), newest first. Rules live in tools/events.php.
$eventType = 'past';
$pageTitle = 'Past Events';
$listingIntro = 'Look back at the workshops, talks and community days Likhwezi Technologies has hosted and taken part in.';

include __DIR__ . '/components/eventListing.php';
