<?php
// cache.php saves slow lookups so most visitors never wait on the database

// keep the result of a slow lookup for $cacheSeconds
// $load returns null when the lookup fails, failures are not saved so the next visit tries again
function cached(string $key, int $cacheSeconds, callable $load) {
    $cacheFile = sys_get_temp_dir() . '/likhwezi_cache_' . md5($key) . '.json';

    // use the saved copy if it is still fresh
    if (is_file($cacheFile) && time() - filemtime($cacheFile) < $cacheSeconds) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $cached['value'];
        }
    }

    // otherwise run the lookup and save it
    $value = $load();

    if ($value !== null) {
        @file_put_contents($cacheFile, json_encode(['value' => $value]), LOCK_EX);
    }

    return $value;
}

// delete every saved lookup so the public pages show a change straight away
// called whenever staff save something and after a donation is recorded
function clearCache(): void {
    foreach (glob(sys_get_temp_dir() . '/likhwezi_cache_*.json') ?: [] as $cacheFile) {
        @unlink($cacheFile);
    }
}

// connect to the database the first time it is needed and reuse that connection afterwards
// returns null when the database cannot be reached
function db(): ?mysqli {
    static $conn = null;

    if ($conn === null) {
        require __DIR__ . '/../../config/dbConnection.php';
    }

    return $conn instanceof mysqli && !$conn->connect_errno ? $conn : null;
}
