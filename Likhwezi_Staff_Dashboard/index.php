<?php
session_start();

$_SESSION['username'] = $_SESSION['username'] ?? 'Name Surname';
$_SESSION['role'] = $_SESSION['role'] ?? 'Admin';

header('Location: adminDashboard.php');
exit;
