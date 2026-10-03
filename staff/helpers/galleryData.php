<?php
// galleryData.php fetches and saves the event and campaign photos for manageEvents.php and manageCampaigns.php
// each photo belongs to one event or one campaign and the first photo is the cover
// the image is a cloudinary address, older ones are a path from the site root that staff pages add ../ in front of
// photos are deleted rather than archived

require_once __DIR__ . '/../../includes/helpers/imageStorage.php';

// the size limit and allowed types
const GALLERY_PHOTO_MAX_BYTES = 2 * 1024 * 1024;
const GALLERY_PHOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// event photos, oldest first so the cover comes first
function getEventPhotos(mysqli $conn): array
{
    $result = $conn->query(
        "SELECT galleryItemID, eventID, image
         FROM GalleryItem
         WHERE eventID IS NOT NULL
         ORDER BY eventID, galleryItemID"
    );

    if (!$result) {
        error_log('getEventPhotos failed: ' . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// one campaign's photos, oldest first so the cover comes first
function getCampaignPhotos(mysqli $conn, int $campaignID): array
{
    $stmt = $conn->prepare(
        "SELECT galleryItemID, campaignID, image
         FROM GalleryItem
         WHERE campaignID = ?
         ORDER BY galleryItemID"
    );
    if (!$stmt) {
        error_log('getCampaignPhotos failed: ' . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $campaignID);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// one photo
function getGalleryItem(mysqli $conn, int $galleryItemID): ?array
{
    $stmt = $conn->prepare("SELECT galleryItemID, eventID, campaignID, image FROM GalleryItem WHERE galleryItemID = ?");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $galleryItemID);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// image address for the staff pages
function galleryImageSrc(string $image): string
{
    return imageSrc($image, '../');
}

// largest upload the server accepts at once, used to warn before too many photos are sent
function galleryPostLimitBytes(): int
{
    $value = trim((string) ini_get('post_max_size'));
    $number = (int) $value;

    return match (strtoupper(substr($value, -1))) {
        'G'     => $number * 1024 ** 3,
        'M'     => $number * 1024 ** 2,
        'K'     => $number * 1024,
        default => $number,
    };
}

// turn the uploaded photos into a simple list, skipping empty slots
function normaliseUploadedFiles(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $list = [];
    foreach (array_keys($files['name']) as $i) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $list[] = [
            'name'     => $files['name'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'size'     => $files['size'][$i],
            'error'    => $files['error'][$i],
        ];
    }

    return $list;
}

// check and save one uploaded photo
function saveGalleryPhoto(array $file): array
{
    $label = '"' . basename($file['name']) . '"';

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > GALLERY_PHOTO_MAX_BYTES) {
        return ['path' => null, 'error' => "$label is larger than 2 MB."];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => "$label could not be uploaded. Please try again."];
    }

    // check the real file contents, not just the extension
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(GALLERY_PHOTO_TYPES[$mime])) {
        return ['path' => null, 'error' => "$label must be a JPG, PNG or WEBP image."];
    }

    $url = uploadImage($file['tmp_name'], 'gallery');

    if (!$url) {
        return ['path' => null, 'error' => "$label could not be saved."];
    }

    return ['path' => $url, 'error' => null];
}

// delete a photo uploaded through this page, the original images are left alone
function deleteGalleryPhotoFile(?string $path): void
{
    deleteImage($path);
}

// add a photo to an event or a campaign
// the archivable entity is saved first then the photo with the same id
function addGalleryPhoto(mysqli $conn, ?int $eventID, ?int $campaignID, string $path): bool
{
    $conn->begin_transaction();

    if (!$conn->query("INSERT INTO ArchivableEntity (entityType) VALUES ('GalleryItem')")) {
        $conn->rollback();
        return false;
    }

    $galleryItemID = $conn->insert_id;

    $stmt = $conn->prepare("INSERT INTO GalleryItem (galleryItemID, eventID, campaignID, image) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        $conn->rollback();
        return false;
    }

    $stmt->bind_param("iiis", $galleryItemID, $eventID, $campaignID, $path);

    if (!$stmt->execute()) {
        error_log('addGalleryPhoto failed: ' . $stmt->error);
        $conn->rollback();
        return false;
    }

    $conn->commit();
    return true;
}

// save every uploaded photo to the event or campaign, a photo that fails is skipped and the rest still save
function addGalleryPhotos(mysqli $conn, ?int $eventID, ?int $campaignID, array $files): array
{
    $added = 0;
    $errors = [];

    foreach ($files as $file) {
        $upload = saveGalleryPhoto($file);

        if ($upload['error']) {
            $errors[] = $upload['error'];
            continue;
        }

        if (addGalleryPhoto($conn, $eventID, $campaignID, $upload['path'])) {
            $added++;
        } else {
            deleteGalleryPhotoFile($upload['path']);
            $errors[] = '"' . basename($file['name']) . '" could not be saved. Please try again.';
        }
    }

    return ['added' => $added, 'errors' => $errors];
}

// message after saving an event or campaign with photos
function savedWithPhotosMessage(string $saved, ?array $result, string $noPhotosTail = '.'): array
{
    if ($result === null) {
        return ['success', $saved . $noPhotosTail];
    }

    $added = $result['added'] === 1 ? '1 photo' : $result['added'] . ' photos';

    if (!$result['errors']) {
        return ['success', $saved . ' with ' . $added . '.'];
    }

    return ['error', $saved . ($result['added'] ? ' with ' . $added : '') . ', but some photos were not saved. '
        . implode(' ', $result['errors'])];
}

// delete a photo from the database and its file
function deleteGalleryPhoto(mysqli $conn, int $galleryItemID): bool
{
    $item = getGalleryItem($conn, $galleryItemID);
    if (!$item) {
        return false;
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("DELETE FROM GalleryItem WHERE galleryItemID = ?");
    $stmt->bind_param("i", $galleryItemID);
    if (!$stmt->execute()) {
        $conn->rollback();
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM ArchivableEntity WHERE entityID = ? AND entityType = 'GalleryItem'");
    $stmt->bind_param("i", $galleryItemID);
    if (!$stmt->execute()) {
        $conn->rollback();
        return false;
    }

    $conn->commit();
    deleteGalleryPhotoFile($item['image']);

    return true;
}
