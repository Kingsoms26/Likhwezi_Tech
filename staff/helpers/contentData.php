<?php
// contentData.php fetches and saves the website content edited on manageContent.php
// contains the cym programmes, service cards, cym photos and site details
// the public pages read the same tables through includes/helpers/siteContent.php

// folder where uploaded cym photos are saved and its path from the site root
const CYM_PHOTO_DIR = __DIR__ . '/../../assets/images/cym/';
const CYM_PHOTO_PATH = 'assets/images/cym/';

// photo size limit and allowed types
const CYM_PHOTO_MAX_BYTES = 5 * 1024 * 1024;

const CYM_PHOTO_TYPES = [
    'image/png'  => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];

// icons admins can pick for a service card
const SERVICE_ICONS = [
    'bi-diagram-3'       => 'Diagram',
    'bi-compass'         => 'Compass',
    'bi-database'        => 'Database',
    'bi-clipboard-check' => 'Clipboard',
    'bi-rocket-takeoff'  => 'Rocket',
    'bi-bar-chart'       => 'Bar chart',
    'bi-graph-up-arrow'  => 'Growth',
    'bi-shield-check'    => 'Shield',
    'bi-cloud'           => 'Cloud',
    'bi-cpu'             => 'Chip',
    'bi-code-slash'      => 'Code',
    'bi-gear'            => 'Gear',
    'bi-people'          => 'People',
    'bi-lightbulb'       => 'Light bulb',
    'bi-briefcase'       => 'Briefcase',
    'bi-puzzle'          => 'Puzzle',
];

// fields on the site details tab with their label, input type and hint
const SITE_SETTING_FIELDS = [
    'contactAddress'  => ['Office address', 'text', 'Footer and contact page.'],
    'contactPhone'    => ['Phone number', 'tel', 'Footer and contact page, e.g. +27 11 464 5083.'],
    'contactEmail'    => ['Email address', 'email', 'Footer, contact page, privacy policy and form error messages.'],
    'cymSiteURL'      => ['Cyber Young Minds website', 'url', 'The "Go to Cyber Young Minds" button on the CYM page.'],
    'cymParticipants' => ['Registered participants', 'text', 'The participants figure on the CYM page, e.g. 255.'],
];

// tables whose rows can be moved up and down, only these names ever reach the query
const ORDERABLE_TABLES = [
    'Programme' => ['programmeID', 'name'],
    'Service'   => ['serviceID', 'name'],
    'CymPhoto'  => ['photoID', 'photoID'],
];

// shared

// move a row one place up or down then renumber the order
function moveContentRow(mysqli $conn, string $table, int $id, string $direction): bool
{
    if (!isset(ORDERABLE_TABLES[$table]) || !in_array($direction, ['up', 'down'], true)) {
        return false;
    }
    [$idColumn, $tieBreak] = ORDERABLE_TABLES[$table];

    $result = $conn->query("SELECT $idColumn FROM $table ORDER BY sortOrder, $tieBreak");
    if (!$result) {
        return false;
    }
    $ids = array_map('intval', array_column($result->fetch_all(MYSQLI_ASSOC), $idColumn));

    $index = array_search($id, $ids, true);
    $swapWith = $direction === 'up' ? $index - 1 : $index + 1;
    if ($index === false || !isset($ids[$swapWith])) {
        return false;
    }
    [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];

    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE $table SET sortOrder = ? WHERE $idColumn = ?");

    foreach ($ids as $position => $rowID) {
        $order = $position + 1;
        $stmt->bind_param("ii", $order, $rowID);
        if (!$stmt->execute()) {
            $conn->rollback();
            return false;
        }
    }

    return $conn->commit();
}

// order number for a new row so it goes to the end of the list
function nextSortOrder(mysqli $conn, string $table): int
{
    $max = isset(ORDERABLE_TABLES[$table]) ? $conn->query("SELECT COALESCE(MAX(sortOrder), 0) FROM $table") : false;
    return $max ? (int) $max->fetch_row()[0] + 1 : 0;
}

// programmes

// every programme in the order cym.php shows them with how many registrations use each one
function getAllProgrammes(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT p.programmeID, p.name, p.isActive, p.dateAdd,
                (SELECT COUNT(*) FROM Registration r WHERE r.programme = p.name) AS registrations
         FROM Programme p
         ORDER BY p.sortOrder ASC, p.name ASC"
    );

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// one programme
function getProgramme(mysqli $conn, int $programmeID): ?array
{
    $stmt = $conn->prepare("SELECT programmeID, name, isActive FROM Programme WHERE programmeID = ?");
    $stmt->bind_param("i", $programmeID);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// check the programme name and return any error messages
function validateProgrammeName(mysqli $conn, string $name, ?int $programmeID): array
{
    if ($name === '') {
        return ['Programme name is required.'];
    }
    if (mb_strlen($name) > 255) {
        return ['Programme name must be 255 characters or fewer.'];
    }

    $ignoreID = $programmeID ?? 0;
    $stmt = $conn->prepare("SELECT 1 FROM Programme WHERE name = ? AND programmeID <> ?");
    $stmt->bind_param("si", $name, $ignoreID);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        return ['A programme named "' . $name . '" already exists.'];
    }

    return [];
}

// add a programme to the end of the list
function addProgramme(mysqli $conn, string $name): bool
{
    $sortOrder = nextSortOrder($conn, 'Programme');

    $stmt = $conn->prepare("INSERT INTO Programme (name, isActive, sortOrder) VALUES (?, TRUE, ?)");
    $stmt->bind_param("si", $name, $sortOrder);

    return $stmt->execute();
}

// rename a programme
function renameProgramme(mysqli $conn, int $programmeID, string $name): bool
{
    $stmt = $conn->prepare("UPDATE Programme SET name = ? WHERE programmeID = ?");
    $stmt->bind_param("si", $name, $programmeID);

    return $stmt->execute();
}

// show or hide a programme on the registration form
function toggleProgramme(mysqli $conn, int $programmeID): bool
{
    $stmt = $conn->prepare("UPDATE Programme SET isActive = NOT isActive WHERE programmeID = ?");
    $stmt->bind_param("i", $programmeID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// delete a programme
function deleteProgramme(mysqli $conn, int $programmeID): bool
{
    $stmt = $conn->prepare("DELETE FROM Programme WHERE programmeID = ?");
    $stmt->bind_param("i", $programmeID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// services

// every service card in order
function getAllServices(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT serviceID, name, tagline, icon, description, includes, isActive
         FROM Service ORDER BY sortOrder ASC, name ASC"
    );

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// one service card
function getService(mysqli $conn, int $serviceID): ?array
{
    $stmt = $conn->prepare(
        "SELECT serviceID, name, tagline, icon, description, includes, isActive FROM Service WHERE serviceID = ?"
    );
    $stmt->bind_param("i", $serviceID);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// tidy each includes line and drop blank ones
function cleanServiceIncludes(string $includes): string
{
    $lines = array_filter(array_map('trim', preg_split('/\R/', $includes)), fn ($line) => $line !== '');
    return implode("\n", $lines);
}

// check the service form and return any error messages
function validateServiceInput(mysqli $conn, array $input, ?int $serviceID): array
{
    $errors = [];

    if ($input['name'] === '') {
        $errors[] = 'Service name is required.';
    } elseif (mb_strlen($input['name']) > 255) {
        $errors[] = 'Service name must be 255 characters or fewer.';
    } else {
        $ignoreID = $serviceID ?? 0;
        $stmt = $conn->prepare("SELECT 1 FROM Service WHERE name = ? AND serviceID <> ?");
        $stmt->bind_param("si", $input['name'], $ignoreID);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'A service named "' . $input['name'] . '" already exists.';
        }
    }

    if ($input['tagline'] === '') {
        $errors[] = 'Tagline is required.';
    } elseif (mb_strlen($input['tagline']) > 255) {
        $errors[] = 'Tagline must be 255 characters or fewer.';
    }

    if (!isset(SERVICE_ICONS[$input['icon']])) {
        $errors[] = 'Please choose an icon.';
    }

    if ($input['description'] === '') {
        $errors[] = 'Description is required.';
    }

    if ($input['includes'] === '') {
        $errors[] = 'List at least one thing the service includes.';
    }

    return $errors;
}

// add a service card to the end of the list
function addService(mysqli $conn, array $input): bool
{
    $sortOrder = nextSortOrder($conn, 'Service');

    $stmt = $conn->prepare(
        "INSERT INTO Service (name, tagline, icon, description, includes, isActive, sortOrder)
         VALUES (?, ?, ?, ?, ?, TRUE, ?)"
    );
    $stmt->bind_param("sssssi", $input['name'], $input['tagline'], $input['icon'], $input['description'], $input['includes'], $sortOrder);

    return $stmt->execute();
}

// save changes to a service card
function updateService(mysqli $conn, int $serviceID, array $input): bool
{
    $stmt = $conn->prepare(
        "UPDATE Service SET name = ?, tagline = ?, icon = ?, description = ?, includes = ? WHERE serviceID = ?"
    );
    $stmt->bind_param("sssssi", $input['name'], $input['tagline'], $input['icon'], $input['description'], $input['includes'], $serviceID);

    return $stmt->execute();
}

// show or hide a service card
function toggleService(mysqli $conn, int $serviceID): bool
{
    $stmt = $conn->prepare("UPDATE Service SET isActive = NOT isActive WHERE serviceID = ?");
    $stmt->bind_param("i", $serviceID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// delete a service card
function deleteService(mysqli $conn, int $serviceID): bool
{
    $stmt = $conn->prepare("DELETE FROM Service WHERE serviceID = ?");
    $stmt->bind_param("i", $serviceID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// cym photos

// every cym photo in order
function getAllCymPhotos(mysqli $conn): array
{
    $result = $conn->query("SELECT photoID, src, altText FROM CymPhoto ORDER BY sortOrder ASC, photoID ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// one cym photo
function getCymPhoto(mysqli $conn, int $photoID): ?array
{
    $stmt = $conn->prepare("SELECT photoID, src, altText FROM CymPhoto WHERE photoID = ?");
    $stmt->bind_param("i", $photoID);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// check and save an uploaded photo, no src means no file was uploaded
function saveCymPhotoUpload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['src' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['src' => null, 'error' => 'The photo could not be uploaded. Please try again.'];
    }

    if ($file['size'] > CYM_PHOTO_MAX_BYTES) {
        return ['src' => null, 'error' => 'Photo must be 5 MB or smaller.'];
    }

    // check the real file contents, not just the extension
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(CYM_PHOTO_TYPES[$mime])) {
        return ['src' => null, 'error' => 'Photo must be a PNG, JPG or WEBP image.'];
    }

    if (!is_dir(CYM_PHOTO_DIR)) {
        mkdir(CYM_PHOTO_DIR, 0755, true);
    }

    $filename = uniqid('cym_') . '.' . CYM_PHOTO_TYPES[$mime];

    if (!move_uploaded_file($file['tmp_name'], CYM_PHOTO_DIR . $filename)) {
        return ['src' => null, 'error' => 'Failed to save the photo.'];
    }

    return ['src' => CYM_PHOTO_PATH . $filename, 'error' => null];
}

// delete a photo uploaded through this page, the original photos are left alone
function deleteCymPhotoFile(?string $src): void
{
    if ($src && str_starts_with($src, CYM_PHOTO_PATH . 'cym_')) {
        $path = CYM_PHOTO_DIR . basename($src);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

// add a cym photo to the end of the list
function addCymPhoto(mysqli $conn, string $src, string $altText): bool
{
    $sortOrder = nextSortOrder($conn, 'CymPhoto');

    $stmt = $conn->prepare("INSERT INTO CymPhoto (src, altText, sortOrder) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $src, $altText, $sortOrder);

    return $stmt->execute();
}

// save a photo's description and its image when a new one is given
function updateCymPhoto(mysqli $conn, int $photoID, string $altText, ?string $src): bool
{
    if ($src !== null) {
        $stmt = $conn->prepare("UPDATE CymPhoto SET altText = ?, src = ? WHERE photoID = ?");
        $stmt->bind_param("ssi", $altText, $src, $photoID);
    } else {
        $stmt = $conn->prepare("UPDATE CymPhoto SET altText = ? WHERE photoID = ?");
        $stmt->bind_param("si", $altText, $photoID);
    }

    return $stmt->execute();
}

// delete a cym photo
function deleteCymPhoto(mysqli $conn, int $photoID): bool
{
    $stmt = $conn->prepare("DELETE FROM CymPhoto WHERE photoID = ?");
    $stmt->bind_param("i", $photoID);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// site details

// saved site details
function getSiteSettings(mysqli $conn): array
{
    $result = $conn->query("SELECT settingKey, value FROM SiteSetting");
    return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'value', 'settingKey') : [];
}

// check the site details form and return any error messages
function validateSiteSettings(array $input): array
{
    $errors = [];

    foreach (SITE_SETTING_FIELDS as $key => [$label]) {
        if ($input[$key] === '') {
            $errors[] = $label . ' is required.';
        } elseif (mb_strlen($input[$key]) > 255) {
            $errors[] = $label . ' must be 255 characters or fewer.';
        }
    }

    if ($input['contactEmail'] !== '' && !filter_var($input['contactEmail'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
    }
    if ($input['contactPhone'] !== '' && !preg_match('/^\+?[0-9 ()-]{7,20}$/', $input['contactPhone'])) {
        $errors[] = 'Phone number can only contain digits, spaces, brackets, dashes and a leading +.';
    }
    if ($input['cymSiteURL'] !== '' && !filter_var($input['cymSiteURL'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Cyber Young Minds website must be a full address, e.g. https://cyberyoungminds.co.za';
    }

    return $errors;
}

// save the site details
function saveSiteSettings(mysqli $conn, array $input): bool
{
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "INSERT INTO SiteSetting (settingKey, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)"
    );

    foreach (array_keys(SITE_SETTING_FIELDS) as $key) {
        $stmt->bind_param("ss", $key, $input[$key]);
        if (!$stmt->execute()) {
            $conn->rollback();
            return false;
        }
    }

    return $conn->commit();
}
