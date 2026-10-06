<?php
    date_default_timezone_set('Africa/Johannesburg');
    // dbConnection.php connects to the database over ssl and gives every page $conn
    mysqli_report(MYSQLI_REPORT_OFF);

    // load the credentials from the .env file when running locally
    require_once __DIR__ . '/env.php';

    // use local defaults on localhost
    $isLocal = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1';

    if ($isLocal) {
        $username = getenv('username') ?: 'root';
        $password = getenv('password') ?: '';
        $host     = getenv('host')     ?: 'localhost';
        $port     = getenv('port')     ?: '3306';
        $database = getenv('database') ?: 'likhwezi';
    } else {
        // on Render there are no fallback defaults
        $username = getenv('username');
        $password = getenv('password');
        $host     = getenv('host');
        $port     = getenv('port');
        $database = getenv('database');
    }

    // certificate for the ssl connection
    $CA_Path = PHP_OS_FAMILY === 'Windows' ? __DIR__ . '/isrgrootx1.pem' : '/etc/ssl/certs/ca-certificates.crt';

    // connect to the database
    $conn = mysqli_init();
    $conn->ssl_set(null, null, $CA_Path, null, null);
    $conn->real_connect($host, $username, $password, $database, $port, null, MYSQLI_CLIENT_SSL);
    // $conn->query("SET time_zone = '+02:00'@);

    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
    }
