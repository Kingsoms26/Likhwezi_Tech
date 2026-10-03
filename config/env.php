<?php
    // env.php loads the credentials from the .env file when running locally
    // on Render they are set as environment variables instead
    // only reads the file the first time so it is safe to include more than once
    if (!defined('LIKHWEZI_ENV_LOADED')) {
        define('LIKHWEZI_ENV_LOADED', true);

        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                // skip blank lines and comments then save each key and value
                [$key, $value] = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }
