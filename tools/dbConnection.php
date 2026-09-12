<?php
    // Report connection failures as ordinary errors instead of throwing,
    // so pages can fall back to placeholder content when the DB is unreachable.
    mysqli_report(MYSQLI_REPORT_OFF);

    $username = getenv('username') ?: 'root';
    $password = getenv('password') ?: '';
    $host     = getenv('host')     ?: 'localhost';
    $port     = getenv('port')     ?: '3306';
    $database = getenv('database') ?: 'likhwezi';

    // Local XAMPP uses your downloaded PEM; Render uses the system CA bundle
    $CA_Path = getenv('host')
        ? '/etc/ssl/certs/ca-certificates.crt'
        : 'D:\path\to\isrgrootx1.pem';

    $conn = mysqli_init();
    $conn->ssl_set(null, null, $CA_Path, null, null);
    $conn->real_connect($host, $username, $password, $database, $port, null, MYSQLI_CLIENT_SSL);

    if ($conn->connect_error) {
        error_log("DB connection failed: " . $conn->connect_error);
    }
?>