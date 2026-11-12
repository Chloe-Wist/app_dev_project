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
    <script src="scripts/eventManagement.js"></script>
</head>
<body>
<?php

$title = "Event Management";
include("helpers/header.php");

require("helpers/events.php");

// If all_events is set, have button which changes it
// to the user's events
if (isset($_GET['all_events'])) {
    echo '<button id="show_user_events">Show Only Your Events</button>';
}
else {
    echo '<button id="show_all_events">Show All Events</button>';
}

// If facilitator, they only see the events they manager
if ($_SESSION['role'] == 'Facilitator') {
    if (isset($_GET['all_events'])) {
        $query = "SELECT event_id FROM event WHERE event_date > CURDATE()";
    }
    $query = "SELECT event_id FROM event WHERE manager_id = ".$_SESSION['user_id']
        ." AND event_date > CURDATE()";
    $result = mysqli_query($conn, $query);
}
// If admin they see all events in their area
else if ($_SESSION['role'] == 'Admin') {
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
// If owner, show all events
else {
    $query = "SELECT event_id FROM event";
    $result = mysqli_query($conn, $query);
}

$events = [];
// Create event objects for each event
while ($a = mysqli_fetch_assoc($result)) {
    array_push($events, new Events($a['event_id']));
}

echo '<div id="event_list">';
// Display events
foreach ($events as $e) {
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
echo "</div>";
?>


</body>
</html>