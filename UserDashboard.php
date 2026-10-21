<?php
// Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
// 10/10/26
// User Dashboard Page


// Connect to the database
require_once "helpers/config.php";

// If there is no session, start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <?php 
    $title="Home";
    include("helpers/header.php")
    ?>
    <div class="page">
        <div class="tab" id="home">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ); ?></h2>
            <?php
            // If the user is an admin, display the user management card
            if ($_SESSION['role'] == 'Owner') {
                echo '<a href="UserManagement.php" class="card">User Management</a>';
            }
            ?>
        </div>
        <div class="tab" id="Scheduling">
            <h2>Scheduling</h2>
            <a href="Events.php" class="card">Events</a>
            <a href="Availability.php">Availability</a>
            <?php
            // If the user is a facilitator or an admin, display the event management card
            if ($_SESSION['role'] == 'Admin' 
            || $_SESSION['role'] == 'Facilitator'
            || $_SESSION['role'] == 'Owner') {
                echo'<a href="EventManagement.php">Event Management</a>';
            }
            ?>
        </div>
        <?php
        // If the user is a facilitator or admin, display these cards
        if ($_SESSION['role'] == 'Admin' 
        || $_SESSION['role'] == 'Facilitator'
        || $_SESSION['role'] == 'Owner') { ?>

        <div class="tab" id="Simulation">
            <h2>Simulation</h2>
            <?php if ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Owner') { ?>
            <a href="SimulationManagement.php">Simulation Management</a>
            <?php } ?>
            <a href="SessionManagement.php">Session Management</a>
        </div>
        <?php
        }
        ?>
    </div>
</body>