<?php
// imageStorage.php uploads and deletes the photos staff add through the portal
// photos are kept on cloudinary because files saved on render are lost on every deploy
// the database keeps the full cloudinary address, the original images keep a path from the site root

require_once __DIR__ . '/../../config/env.php';

// upload a checked image to a cloudinary folder, returns its address or null when it fails
function uploadImage(string $tmpPath, string $folder): ?string {
    $response = cloudinaryRequest('upload', [
        'folder' => 'likhwezi/' . $folder,
        'file'   => new CURLFile($tmpPath),
    ]);

    return $response['secure_url'] ?? null;
}

// delete an image uploaded to cloudinary, the original images in assets are left alone
function deleteImage(?string $url): void {
    $cloud = getenv('CLOUDINARY_CLOUD_NAME');

    if (!$url || !$cloud || !str_starts_with($url, "https://res.cloudinary.com/$cloud/image/upload/")) {
        return;
    }

    // the public id is the path after the version number without the extension
    if (preg_match('#/image/upload/(?:v\d+/)?(.+)\.[a-z0-9]+$#i', $url, $match)) {
        // invalidate clears the cached copy so the old image stops showing straight away
        cloudinaryRequest('destroy', ['public_id' => $match[1], 'invalidate' => 'true']);
    }
}

// the address to show an image at, cloudinary addresses are used as they are
// older paths get $prefix in front, like ../ on the staff pages
function imageSrc(string $image, string $prefix = ''): string {
    return preg_match('#^https?://#i', $image) ? $image : $prefix . ltrim($image, '/');
}

// send a signed request to the cloudinary image api, returns the decoded reply or null when it fails
function cloudinaryRequest(string $action, array $params): ?array {
    $cloud  = getenv('CLOUDINARY_CLOUD_NAME');
    $key    = getenv('CLOUDINARY_API_KEY');
    $secret = getenv('CLOUDINARY_API_SECRET');

    if (!$cloud || !$key || !$secret) {
        error_log('cloudinary is not set up, add the CLOUDINARY_ keys to the environment');
        return null;
    }

    // sign every field except the file, sorted by name
    $params['timestamp'] = time();
    $signed = array_diff_key($params, ['file' => true]);
    ksort($signed);
    $params['signature'] = sha1(urldecode(http_build_query($signed)) . $secret);
    $params['api_key'] = $key;

    $curl = curl_init("https://api.cloudinary.com/v1_1/$cloud/image/$action");
    curl_setopt_array($curl, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    $response = is_string($body) ? json_decode($body, true) : null;

    if ($status !== 200 || !is_array($response)) {
        error_log("cloudinary $action failed ($status): " . ($error ?: $body));
        return null;
    }

    return $response;
}
