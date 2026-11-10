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

require("helpers/events.php");

// If facilitator, they only see the events they manager
if ($_SESSION['role_name'] == 'Facilitator') {
    $query = "SELECT event_id FROM event WHERE manager_id = ".$_SESSION['user_id'];
    $result = mysqli_query($conn, $query);
}
// If admin they see all events in their area
else if ($_SESSION['role_name'] == 'Admin') {
    $query = "SELECT area_id FROM user_to_area WHERE user_id = ".$_SESSION['user_id'];
    $result = mysqli_query($conn, $query);

    if ($a = mysqli_fetch_assoc($result)) {
        $query = "SELECT event_id FROM event 
            WHERE area_id = ".$a['area_id'];

    }
    while ($a = mysqli_fetch_assoc($result)) {
        $query += " OR area_id = ".$a['area_id'];
    }
    $result = mysqli_query($conn, $query);
}
// If admin, show all events
else {
    $query = "SELECT event_id FROM event";
    $result = mysqli_query($conn, $event);
}

$events = [];
// Create event objects for each event
while ($a = mysqli_fetch_assoc($result)) {
    array_push($events, new Events($a['event_id']));
}

// Display events
foreach ($events as $e) {
    if ($e->$start_datetime < new DateTimeImmutable('now')) {
?>  
    <div class="event">
        <h3 class="event_name"><?=$e->name?></h3>
        <p class="description"><?=$e->description?></p>
        <p class="Date"><?=$e->start_datetime->format('d/m/Y')?></p>
        <p class="Start-Time"><?=$e->start_datetime->format('h:i A')?></p>
        <p class="End-Time"><?=$e->end_datetime->format('h:i A')?></p>
        <p class="Manager">Managed By: 
            <?=$e->manager_id['first_name']." ".$e->manager_id['last_name']?>
        </p>
        <p class="location">
            <?=$e->location['address']."\n".$e->location['city'].", ".
            $e->location['state']." ".$e->location['zip_code']?>
        </p>
        <p class="client_name">Client: <?=$e->client_name?></p>
        <p class="areas"><?php
            $s = "";
            foreach ($e->areas as $a) {
                $s += ", ".$a;
            }
            echo $s;
        ?></p>
        <div class="documents"> <?php
            foreach($e->documents as $d) { 
                echo '<a href="'.$d['document_path'].'" 
                    class="document_link">
                    '.$d['document_name'].'</a>';
            }
        echo "</div>";

        if ($e->session) {
            echo '<a href="SessionManagement.php?session_id='.$e->$session.'"
                class="session">Session</a>';
        } 
        else {
            echo '<p class="session">No session</p>';
        }?>
    </div>
<?php 
    }
}
?>


</body>
</html>