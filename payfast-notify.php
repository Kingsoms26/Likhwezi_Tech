<?php
// payfast-notify.php receives the payment notification PayFast sends after every payment
// visitors never see this page and it is the only place a donation is marked complete
// PayFast only needs a 200 response, anything rejected is written to the error log
require_once __DIR__ . '/includes/helpers/cache.php';
require_once __DIR__ . '/includes/helpers/payfast.php';
require_once __DIR__ . '/includes/helpers/donations.php';

http_response_code(200);

// ignore anything that is not a payfast post
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['m_payment_id'])) {
    exit;
}

$reference = (string) $_POST['m_payment_id'];

$conn = db();
if (!$conn) {
    // a non 200 response makes PayFast try again later
    http_response_code(503);
    error_log("PayFast ITN $reference: database unavailable");
    exit;
}

// find the donation this payment belongs to
$donation = getDonationByReference($conn, $reference);
if (!$donation) {
    error_log("PayFast ITN $reference: no donation with this reference");
    exit;
}

// check the notification really came from payfast and the amount matches
$problem = payfastValidateNotification($_POST, $donation['amount']);
if ($problem !== null) {
    error_log("PayFast ITN $reference rejected: $problem");
    exit;
}

// turn the payfast status into ours
$statuses = ['COMPLETE' => 'complete', 'CANCELLED' => 'cancelled', 'FAILED' => 'failed'];
$status = $statuses[strtoupper((string) ($_POST['payment_status'] ?? ''))] ?? null;

if ($status === null) {
    error_log("PayFast ITN $reference: unknown payment_status " . ($_POST['payment_status'] ?? ''));
    exit;
}

// save the status and clear the cache so the campaign totals update
if (setDonationPaymentStatus($conn, $reference, $status, (string) ($_POST['pf_payment_id'] ?? '') ?: null)) {
    clearCache();
}
