<?php
    date_default_timezone_set('Africa/Johannesburg');
    // dbConnection.php connects to the database over ssl and gives every page $conn
    mysqli_report(MYSQLI_REPORT_OFF);

    // load the credentials from the .env file when running locally
    require_once __DIR__ . '/env.php';

    // use local defaults on localhost
    $isLocal = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1';

    if ($isLocal) {
        // On Windows getenv('username') may be the OS account even when no DB user was configured.
        $hasLocalEnv = is_file(__DIR__ . '/.env');
        $username = $hasLocalEnv ? (getenv('username') ?: 'root') : 'root';
        $password = $hasLocalEnv ? (getenv('password') ?: '') : '';
        $host     = $hasLocalEnv ? (getenv('host') ?: 'localhost') : 'localhost';
        $port     = $hasLocalEnv ? (getenv('port') ?: '3306') : '3306';
        $database = $hasLocalEnv ? (getenv('database') ?: 'LikhweziTechDB') : 'LikhweziTechDB';
    } else {
        // on Render there are no fallback defaults
        $username = getenv('username');
        $password = getenv('password');
        $host     = getenv('host');
        $port     = getenv('port');
        $database = getenv('database');
    }

    // connect to the database
    $conn = mysqli_init();
    $clientFlags = 0;
    if (!$isLocal) {
        // certificate for the remote SSL connection
        $CA_Path = PHP_OS_FAMILY === 'Windows' ? __DIR__ . '/isrgrootx1.pem' : '/etc/ssl/certs/ca-certificates.crt';
        $conn->ssl_set(null, null, $CA_Path, null, null);
        $clientFlags = MYSQLI_CLIENT_SSL;
    }
    $conn->real_connect($host, $username, $password, $database, $port, null, $clientFlags);
    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
    } else {
        // match the database clock to south african time
        $conn->query("SET time_zone = '+02:00'");
    }
