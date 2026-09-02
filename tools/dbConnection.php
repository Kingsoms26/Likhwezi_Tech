<?php
    $username = "3tjvSWMUy2Kxos5.root";
    $password = "mOehuWu8iraTiBNr";
    $host = "gateway01.eu-central-1.prod.aws.tidbcloud.com";
    $port = "4000";
    $CA_Path = 'D:\xampp\htdocs\MyProjects\Likhwezi_Tech\isrgrootx1.pem';
    $database = "tester";

    $conn = mysqli_init();
    $conn->ssl_set(null, null, $CA_Path, null, null);
    $conn->real_connect($host, $username, $password, $database, $port, null, MYSQLI_CLIENT_SSL);

    if($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>