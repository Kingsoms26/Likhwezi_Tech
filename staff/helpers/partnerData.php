<?php
// partnerData.php fetches and saves the partners for staff/manageContent.php
// the logo is a cloudinary address, the original logos are a filename in assets/images/partners

require_once __DIR__ . '/../../includes/helpers/imageStorage.php';

// address of the original logos folder from the staff pages
const PARTNER_LOGO_URL = '../assets/images/partners/';

// logo size limit and allowed types
const PARTNER_LOGO_MAX_BYTES = 2 * 1024 * 1024;

const PARTNER_LOGO_TYPES = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];

// every partner in the same order as partners.php, archived ones at the bottom so staff can restore them
function getAllPartners(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT partnerID, name, description, logo, websiteURL, sortOrder, dateAdd, isArchived
         FROM Partner
         ORDER BY isArchived ASC, sortOrder ASC, dateAdd ASC"
    );

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// one partner
function getPartner(mysqli $conn, int $partnerID): ?array
{
    $stmt = $conn->prepare(
        "SELECT partnerID, name, description, logo, websiteURL, isArchived
         FROM Partner WHERE partnerID = ?"
    );
    $stmt->bind_param("i", $partnerID);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// counts for the summary cards
function getPartnerCounts(array $partners): array
{
    $archived = count(array_filter($partners, fn ($p) => (bool) $p['isArchived']));

    return [
        'total'    => count($partners),
        'active'   => count($partners) - $archived,
        'archived' => $archived,
    ];
}

// true if another partner already uses this name
function partnerNameExists(mysqli $conn, string $name, ?int $ignoreID = null): bool
{
    $ignoreID = $ignoreID ?? 0;

    $stmt = $conn->prepare("SELECT 1 FROM Partner WHERE name = ? AND partnerID <> ?");
    $stmt->bind_param("si", $name, $ignoreID);
    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

// check the partner form and return any error messages
function validatePartnerInput(mysqli $conn, array $input, ?int $partnerID): array
{
    $errors = [];

    if ($input['name'] === '') {
        $errors[] = 'Partner name is required.';
    } elseif (mb_strlen($input['name']) > 255) {
        $errors[] = 'Partner name must be 255 characters or fewer.';
    } elseif (partnerNameExists($conn, $input['name'], $partnerID)) {
        $errors[] = 'A partner named "' . $input['name'] . '" already exists.';
    }

    if ($input['websiteURL'] !== '' && !filter_var($input['websiteURL'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Website URL must be a full address, e.g. https://example.com';
    }

    return $errors;
}

// check and save an uploaded logo, no logo means no file was uploaded
function savePartnerLogo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['logo' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['logo' => null, 'error' => 'The logo could not be uploaded. Please try again.'];
    }

    if ($file['size'] > PARTNER_LOGO_MAX_BYTES) {
        return ['logo' => null, 'error' => 'Logo must be 2 MB or smaller.'];
    }

    // check the real file contents, not just the extension
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(PARTNER_LOGO_TYPES[$mime])) {
        return ['logo' => null, 'error' => 'Logo must be a PNG, JPG or WEBP image.'];
    }

    $url = uploadImage($file['tmp_name'], 'partners');

    if (!$url) {
        return ['logo' => null, 'error' => 'Failed to save the logo image.'];
    }

    return ['logo' => $url, 'error' => null];
}

// image address for a logo from the staff pages
function partnerLogoSrc(string $logo): string
{
    return imageSrc($logo, PARTNER_LOGO_URL);
}

// delete a logo uploaded through this page, the original logos are left alone
function deletePartnerLogo(?string $logo): void
{
    deleteImage($logo);
}

// add a partner, the archivable entity is saved first then the partner with the same id
function addPartner(mysqli $conn, array $input, string $logo): ?int
{
    $conn->begin_transaction();

    $entity = $conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('Partner')");
    if (!$entity) {
        $conn->rollback();
        return null;
    }

    $partnerID = $conn->insert_id;
    $websiteURL = $input['websiteURL'];

    // new partners go to the end of the order
    $max = $conn->query("SELECT COALESCE(MAX(sortOrder), 0) AS maxOrder FROM Partner");
    $sortOrder = $max ? (int) $max->fetch_assoc()['maxOrder'] + 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO Partner (partnerID, name, description, logo, websiteURL, sortOrder, isArchived)
         VALUES (?, ?, ?, ?, ?, ?, FALSE)"
    );
    $stmt->bind_param("issssi", $partnerID, $input['name'], $input['description'], $logo, $websiteURL, $sortOrder);

    if (!$stmt->execute()) {
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $partnerID;
}

// save changes to a partner, pass no logo to keep the current one
function updatePartner(mysqli $conn, int $partnerID, array $input, ?string $logo): bool
{
    $websiteURL = $input['websiteURL'];

    if ($logo !== null) {
        $stmt = $conn->prepare(
            "UPDATE Partner SET name = ?, description = ?, logo = ?, websiteURL = ? WHERE partnerID = ?"
        );
        $stmt->bind_param("ssssi", $input['name'], $input['description'], $logo, $websiteURL, $partnerID);
    } else {
        $stmt = $conn->prepare(
            "UPDATE Partner SET name = ?, description = ?, websiteURL = ? WHERE partnerID = ?"
        );
        $stmt->bind_param("sssi", $input['name'], $input['description'], $websiteURL, $partnerID);
    }

    return $stmt->execute();
}

// save the display order, nothing changes if any update fails
function savePartnerOrder(mysqli $conn, array $partnerIDs): bool
{
    $partnerIDs = array_values(array_unique(array_map('intval', $partnerIDs)));
    if (!$partnerIDs) {
        return false;
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE Partner SET sortOrder = ? WHERE partnerID = ?");
    if (!$stmt) {
        $conn->rollback();
        return false;
    }

    foreach ($partnerIDs as $index => $partnerID) {
        $position = $index + 1;
        $stmt->bind_param("ii", $position, $partnerID);

        if (!$stmt->execute()) {
            $conn->rollback();
            return false;
        }
    }

    $conn->commit();
    return true;
}

// archive a partner or restore it and log who did it
function togglePartnerArchive(mysqli $conn, int $partnerID, int $accountID): ?string
{
    $partner = getPartner($conn, $partnerID);
    if (!$partner) {
        return null;
    }

    $newState = $partner['isArchived'] ? 0 : 1;
    $action = $newState ? 'archived' : 'restored';

    $conn->begin_transaction();

    $update = $conn->prepare("UPDATE Partner SET isArchived = ? WHERE partnerID = ?");
    $update->bind_param("ii", $newState, $partnerID);

    $log = $conn->prepare("INSERT INTO ArchiveLog (entityID, performedBy, action) VALUES (?, ?, ?)");
    $log->bind_param("iis", $partnerID, $accountID, $action);

    if (!$update->execute() || !$log->execute()) {
        $conn->rollback();
        return null;
    }

    $conn->commit();
    return $action;
}
