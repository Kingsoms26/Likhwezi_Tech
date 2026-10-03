<?php
// siteStatus.php checks whether an external website is online so a page can hide links to sites that are not live yet
// the answer is cached so visitors only wait on the check once per $cacheSeconds
function siteIsOnline(string $url, int $cacheSeconds = 3600): bool {
    $cacheFile = sys_get_temp_dir() . '/likhwezi_site_' . md5($url) . '.json';

    // use the saved answer if it is still fresh
    if (is_file($cacheFile) && time() - filemtime($cacheFile) < $cacheSeconds) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached) && isset($cached['online'])) {
            return (bool) $cached['online'];
        }
    }

    $online = false;

    // a domain that is registered but not hosted fails here without waiting on a connection
    $host = parse_url($url, PHP_URL_HOST);
    if ($host && function_exists('curl_init') && gethostbyname($host) !== $host) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        // some servers reject head requests but are still up so only server errors and no answer count as offline
        $online = $status >= 200 && $status < 500;
    }

    // save the answer for next time
    @file_put_contents($cacheFile, json_encode(['online' => $online]));

    return $online;
}
