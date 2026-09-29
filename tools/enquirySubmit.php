<?php
/*
 * ENQUIRY SUBMISSION LOGIC
 *
 * Validates and inserts a new enquiry from the public Contact Us form.
 * Returns an array so the page decides how to display errors or a
 * success message; this file does not output any HTML itself.
 *
 * Usage from contact.php:
 *
 *     $result = submitEnquiry($conn, $_POST);
 *     if ($result['success']) { ... } else { $errors = $result['errors']; }
 */

// Normalises South African mobile numbers before they are stored or validated.
// Moved here from contact.php, since validation logic belongs with the
// rest of the enquiry submission logic rather than inside the page itself.
function normalise_phone(string $input): ?string
{
    // Removes spaces, brackets, hyphens, and any other non-digit characters.
    $digits = preg_replace('/\D/', '', $input);

    // Converts the international +27 format to the local 0 format.
    if (str_starts_with($digits, '27')) {
        $digits = '0' . substr($digits, 2);
    }

    // Converts the international 0027 format to the local 0 format.
    if (str_starts_with($digits, '0027')) {
        $digits = '0' . substr($digits, 4);
    }

    // Accepts only a South African number beginning with 0 and containing 10 digits.
    return preg_match('/^0[1-8]\d{8}$/', $digits) ? $digits : null;
}

function submitEnquiry(mysqli $conn, array $post): array
{
    $errors = [];

    $name = trim($post['name'] ?? '');
    $companyName = trim($post['organisation'] ?? '');
    $email = trim($post['email'] ?? '');
    $rawPhone = trim($post['phone'] ?? '');
    $description = trim($post['description'] ?? '');
    $meetingType = $post['meetingType'] ?? '';
    $consent = isset($post['privacyConsent']);

    if ($name === '' || mb_strlen($name) > 150) {
        $errors[] = "Please enter your full name.";
    }

    $phoneNumber = normalise_phone($rawPhone);
    if ($phoneNumber === null) {
        $errors[] = "Please enter a valid South African phone number.";
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($companyName === '' || mb_strlen($companyName) > 255) {
        $errors[] = "Please enter your organisation.";
    }

    if (!in_array($meetingType, ['virtual', 'physical'], true)) {
        $errors[] = "Please choose a meeting type.";
    }

    if ($description === '' || mb_strlen($description) > 5000) {
        $errors[] = "Please tell us how we can help.";
    }

    if (!$consent) {
        $errors[] = "Please confirm you accept the Privacy Notice before submitting.";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Two-step insert: create the ArchivableEntity row first, then the
    // Enquiry row using that same generated ID — the same pattern already
    // used for Partner elsewhere in this project.
    $conn->begin_transaction();
    $conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Enquiry')");
    $newEntityID = $conn->insert_id;

    $stmt = $conn->prepare(
        "INSERT INTO Enquiry
            (enquiryID, name, companyName, email, phoneNumber, description, meetingType, status, isArchived)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'new', FALSE)"
    );

    if (!$stmt) {
        $conn->rollback();
        return ['success' => false, 'errors' => ["Something went wrong. Please try again."]];
    }

    $stmt->bind_param(
        "issssss",
        $newEntityID, $name, $companyName, $email, $phoneNumber, $description, $meetingType
    );

    if (!$stmt->execute()) {
        $conn->rollback();
        return ['success' => false, 'errors' => ["Something went wrong. Please try again."]];
    }

    $conn->commit();

    // TODO: notify staff by email once SMTP/mail sending is set up.
    // This is a "Should", not a "Must", in the functional requirements.

    return ['success' => true, 'errors' => []];
}