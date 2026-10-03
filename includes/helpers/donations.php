<?php
// donations.php saves donations before payment and updates them when PayFast reports back
// a donation is pending until PayFast says it is complete, cancelled or failed
// only complete donations count in the totals

// donation limits and the preset amounts, PayFast's minimum is R5
const DONATION_MIN = 5;
const DONATION_MAX = 1000000;
const DONATION_PRESETS = [100, 250, 500];

// check the donation form and return a message for each field with a problem
function validateDonationInput(array &$input): array
{
    $errors = [];

    foreach (['firstName', 'lastName', 'email', 'phoneNumber', 'presetAmount', 'customAmount'] as $field) {
        $input[$field] = trim((string) ($input[$field] ?? ''));
    }
    $input['isAnonymous'] = !empty($input['isAnonymous']);

    // check the donor details
    if ($input['firstName'] === '' || mb_strlen($input['firstName']) > 100) {
        $errors['firstName'] = 'Please enter your first name.';
    }
    if ($input['lastName'] === '' || mb_strlen($input['lastName']) > 100) {
        $errors['lastName'] = 'Please enter your last name.';
    }
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($input['email']) > 100) {
        $errors['email'] = 'Please provide a valid email address.';
    }
    if ($input['phoneNumber'] !== '' && !preg_match('/^\+?[\d\s()-]{7,20}$/', $input['phoneNumber'])) {
        $errors['phoneNumber'] = 'Please enter a valid phone number, or leave it empty.';
    }

    // read the amount, spaces, an R and a comma as the decimal point are all accepted
    $amountText = $input['presetAmount'] !== '' && $input['presetAmount'] !== 'custom'
        ? $input['presetAmount']
        : preg_replace('/[\sR]/i', '', $input['customAmount']);
    $amountText = preg_match('/^\d+,\d{1,2}$/', $amountText) ? str_replace(',', '.', $amountText) : str_replace(',', '', $amountText);

    // check the amount is within the limits
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $amountText)) {
        $errors['amount'] = 'Please select or enter a donation amount greater than zero.';
    } elseif ((float) $amountText < DONATION_MIN || (float) $amountText > DONATION_MAX) {
        $errors['amount'] = 'Donations must be between R' . number_format(DONATION_MIN) . ' and R' . number_format(DONATION_MAX) . '.';
    } else {
        $input['amountValue'] = round((float) $amountText, 2);
    }

    return $errors;
}

// save a pending donation, only if the campaign is active and not archived
// returns the donation ready for payfastCheckoutFields() or null if the campaign is not taking donations
function createPendingDonation(mysqli $conn, int $campaignID, array $input): ?array
{
    $reference = 'DON-' . strtoupper(bin2hex(random_bytes(8)));
    $phone = $input['phoneNumber'] !== '' ? $input['phoneNumber'] : null;
    $anonymous = $input['isAnonymous'] ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO Donation (campaignID, firstName, lastName, phoneNumber, email, amount, paymentReference,
                               paymentStatus, isAnonymous)
         SELECT campaignID, ?, ?, ?, ?, ?, ?, 'pending', ?
         FROM Campaign
         WHERE campaignID = ? AND status = 'active' AND isArchived = FALSE"
    );
    if (!$stmt) {
        error_log('createPendingDonation failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param('ssssdsii', $input['firstName'], $input['lastName'], $phone, $input['email'],
        $input['amountValue'], $reference, $anonymous, $campaignID);

    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        if ($stmt->errno) {
            error_log('createPendingDonation failed: ' . $stmt->error);
        }
        return null;
    }

    return getDonationByReference($conn, $reference);
}

// one donation with its campaign's name
function getDonationByReference(mysqli $conn, string $reference): ?array
{
    $stmt = $conn->prepare(
        "SELECT d.donationID, d.campaignID, d.firstName, d.lastName, d.email, d.amount,
                d.paymentReference AS reference, d.paymentStatus, d.isAnonymous, c.name AS campaignName
         FROM Donation d
         JOIN Campaign c ON c.campaignID = d.campaignID
         WHERE d.paymentReference = ?"
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $reference);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        return null;
    }

    $row['amount'] = (float) $row['amount'];
    $row['isAnonymous'] = (bool) $row['isAnonymous'];
    return $row;
}

// save the result from PayFast, a complete payment always wins even over a cancelled donation
// nothing changes once a donation is complete, returns true when the donation changed
function setDonationPaymentStatus(mysqli $conn, string $reference, string $status, ?string $pfPaymentID = null): bool
{
    if ($status === 'complete') {
        $stmt = $conn->prepare(
            "UPDATE Donation SET paymentStatus = 'complete', pfPaymentID = ?, donationDate = NOW()
             WHERE paymentReference = ? AND paymentStatus <> 'complete'"
        );
        $stmt->bind_param('ss', $pfPaymentID, $reference);
    } else {
        $stmt = $conn->prepare(
            "UPDATE Donation SET paymentStatus = ?, pfPaymentID = COALESCE(?, pfPaymentID)
             WHERE paymentReference = ? AND paymentStatus = 'pending'"
        );
        $stmt->bind_param('sss', $status, $pfPaymentID, $reference);
    }

    return $stmt->execute() && $stmt->affected_rows === 1;
}
