<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ensures correct access
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Owner'
    && $_SESSION['role'] != 'Facilitator') {
    header("Location: UserDashboard.php");
    exit;
}

// Connects to the database
require("helpers/config.php"); 

?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Management</title>
    <link rel="stylesheet" href="styles/main.css">
</head>
<body>
<?php

$title = "Event Management";
include("helpers/header.php");

?>
</body>
</html>