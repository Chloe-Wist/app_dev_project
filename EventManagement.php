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

// If an event was edited, handle the edits
require("helpers/events_changes_handler.php");


// Get user areas
$query = "SELECT area.area_id, area_name FROM user_to_area 
JOIN area ON user_to_area.area_id = area.area_id
WHERE user_id = ".$_SESSION['user_id'];
$result = mysqli_query($conn, $query);

$user_areas;
while ($a = mysqli_fetch_assoc($result)) {
    $user_areas[] = ['area_id' => $a['area_id'],
                    'area_name' => $a['area_name']];
}


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

// If the user wants to create an event or to edit an event
if (isset($_GET['event_id']) || isset($_GET['new_event'])) {
    // Retrieve information on the event
    if (isset($_GET['event_id'])) {
        $e = new Events($_GET['event_id']);
    }

    // Make a form for saving event info
    ?>
    <form method="POST" action="eventManagement.php" id="edit_event">
        <!-- Event ID (ID of -1 means it is a new event)-->
         <input type="hidden" name="event_id" id="event_id"
             value="<?=isset($e)?$_GET["event_id"]:-1?>">

        <!-- Event Name -->
        <label for="event_name">Event</label>
        <input type="text" name="event_name" id="event_name" 
            value="<?=isset($e)?$e->name:""?>">

        <!-- Description -->
        <label for="description">Description</label>
        <textarea name="description" id="description" placeholder="Type here...">
            <?=isset($e)?$e->description:""?></textarea>

        <!-- Date -->
        <label for="date">Date</label>
        <input type="date" id="date" name="date"
        value="<?=$e?$e->start_datetime->format('Y-m-d'):""?>">

        <!-- Start Time -->
        <label for="start_time">Start Time</label>
        <input type="time" id="start_time" name="start_time"
        value="<?=$e?$e->start_datetime->format('H:i'):""?>">

        <!-- End Time -->
        <label for="end_time">End Time</label>
        <input type="time" id="end_time" name="end_time"
        value="<?=$e?$e->end_datetime->format('H:i'):""?>">

        <!-- Managed By -->
        <?php 
        //  Facilitators cannot change the manager of the event
        if ($_SESSION['role'] == "Facilitator") {
            echo "<p>Managed By</p>";
            if ($e) {
                echo "<p>".$e->manager_id['first_name']." ".$e->manager_id['last_name']."</p>";
                echo '<input type="hidden" name="manager" value="'.$e->manager_id['manager_id'].'">';      
            }
            else {
                $query = "SELECT first_name, last_name FROM users WHERE user_id = ".$_SESSION['user_id'];
                $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
                echo "<p>".$result['first_name']." ".$result['last_name']."</p>";
                echo '<input type="hidden" name="manager" value="'.$_SESSION['user_id'].'">';
            }    
        }
        // Owners or admins can change the manager of an event to be anyone in the area
        else {
            // Get list of all possible people to manage
            $query = "SELECT DISTINCT first_name, last_name, users.user_id FROM users JOIN user_to_area 
                ON users.user_id = user_to_area.user_id WHERE area_id = "
                .(isset($e)?$e->areas[0]['area_id']:$user_areas[0]['area_id']); 
                    // If the event has already been made, you can select any area that the event has.
                    
            if (isset($e)) {
                for ($i = 1; $i < count($e->areas); $i++) {
                    $query .= " OR area_id = ".$e->areas[$i];
                }
            }

            $result = mysqli_query($conn, $query);

            echo '<label for="manager">Managed By</label>';
            echo '<select id="manager" name="manager">';
            while ($a = mysqli_fetch_assoc($result)) {
                echo('<option value="'.$a['manager_id'].'" 
                '.($a['manager_id'] == $e->manager_id['manager_id'])?"selected":"".'>
                '.$a['first_name'].' '.$a['last_name'].'</option>');
            }
            echo '</select>';
        } 

        ?>
        <!-- Location -->
        <p>Location</p>
        <p id="current_location"> <?php
            if (isset($e)) { ?>
                <?=$e->location['name']."\n".
                $e->location['address']."\n".$e->location['city'].", ".
                $e->location['state']." ".$e->location['zip_code']?>
            <?php } 
            else {
                echo "No location set";
            }?>
        </p>
        <input type="hidden" name="location_id_original" id="location_id_original"
            value="<?=isset($e)?$e->location['location_id']:-1?>">

        <button type="button" id="change_location">Change to different location</button>
        <?php
        // Only allow editing the current location if there is a current location
        if (isset($e)) { ?>
            <button type="button" id="edit_location_button">Edit Current Location</button>
        <?php } ?>
        <button type="button" id="create_location_button">Create Location</button>

        <select id="location_select" hidden disabled id="location" name="location">
            <?php 
            // Retrieve all locations
            $query = "SELECT location_id, name FROM location";
            $result = mysqli_query($conn, $query);
            while ($a = mysqli_fetch_assoc($result)) {
                echo '<option value="'.$a['location_id'].'"
                '.($a['location_id'] == $e->location['location_id'])?"selected":"".'>
                '.$a['location_name'].'</option';
            }
            ?>
        </select>
        <button type="button" hidden disabled id="cancel_location_select">Cancel</button>
        
        <fieldset hidden disabled id="edit_location">
            <input type="hidden" name="location_id" 
            value="<?=isset($e)?$e->location['location_id']:-1?>">

            <label for="location_address">Address</label>
            <input type="text" name="location_address" id="location_address"
                value="<?=(isset($e))?$e->location['address']:""?>">

            <label for="location_city">City</label>
            <input type="text" name="location_city"  id="location_city"
                value="<?=(isset($e))?$e->location['city']:""?>">

            <label for="location_state">State</label>
            <input type="text" name="location_state"  id="location_state"
                value="<?=isset($e)?$e->location['state']:""?>">

            <label for="location_zipcode">Zip Code</label>
            <input type="text" name="location_zipcode"  id="location_zipcode"
                value="<?=isset($e)?$e->location['zip_code']:""?>">

            <?php if(isset($e)) { ?>
                <label for="for_all_events">
                    Check this box to change the address for only this event
                    instead of all events with this location.
                </label>
                <input type="checkbox" name="for_all_events" id="for_all_events" value="no">
            <?php }
            else { // If it is a new event, don't check the box?>
                <input type="hidden" name="for_all_events" value="no">
            <?php } ?>

            <button type="button" id="cancel_edit_location">Cancel</button>
        </fieldset>

        <!-- Client -->
        <label for="client">Client</label>
        <input type="text" id="Client" value="<?=isset($e)?$e->client_name:""?>">

        <!-- Areas -->
        <fieldset>
            <legend>Areas</legend>

            <?php
            // If the user is a facilitator or an admin,
            // they can select the areas that either are already on the event,
            // or that they have
            $areas;

            // Areas of the user
            foreach ($user_areas as $a) {
                $areas[] = $a;
            }

            // If owner, do all areas
            if ($_SESSION['role'] == 'Owner') {
                $query = "SELECT area_id, area_name FROM area";
                $result = mysqli_query($conn, $query);

                while ($aa = mysqli_fetch_assoc($result)) {
                    $a = ["area_id" => $aa['area_id'],
                        "area_name" => $aa['area_name']];
                    if (!in_array($a, $areas)) {
                        $areas[] = $a;
                    }
                }
            }

            // Areas of the event
            if (isset($e)) {
                foreach($e->areas as $a) {
                    if (!in_array($a, $areas)) {
                        $areas[] = $a;
                    }
                }
            }

            // Add all the areas to the checklist
            foreach ($areas as $a) {
                echo '<input type="checkbox" id="area'.$a['area_id'].'" 
                    name="area" value="'.$a['area_id'].'">';
                echo '<label for="area'.$a['area_id'].'">'.
                    $a['area_name'].'</label>';
            }
            ?>
        </fieldset>

        <p>Placeholder for document upload</p>
        <?php

        // TODO ensure this works with session page
        if (isset($e) && $e->sessions) {
            echo '<button type="button" id="edit_session">View Session</button>';
            // echo '<input type="hidden" id="session_id" name="session_id" value="'.$e->$session.'">';
        } 
        else {
            echo '<p class="session">No session</p>';
            echo '<button type="button" id="new_session">Create a session</a>';
        }
        echo '<button type="submit">Save</button>';

    echo "</form>";
}
// If the user wants to see lists of events
else {
    // If the user wants to see past events
    if (isset($_GET['past_events'])) {
        echo '<a href="eventManagement.php" id="show_user_events">Your Events</a>';
    } 
    else {
        // If all_events is set, have button which changes it
        // to the user's events
        if (isset($_GET['all_events'])) {
            echo  '<a href="eventManagement.php" id="show_user_events">Your Events</a>';
        }
        else {
            echo '<a href="eventManagement.php?all_events=True" id="show_all_events">Show All Events</a>';
        }
        // Button to show past events
        echo '<a href="eventManagement.php?past_events=true" id="show_past_events">Past Events</a>';
    }


    // Query to select all events
    if (isset($_GET['past_events'])) { 
        $query = "SELECT event_id FROM event WHERE event_date < CURDATE()";
    }
    else {
        $query = "SELECT event_id FROM event WHERE event_date >= CURDATE()";
    }

    // If the user does not want to display all events
    if (!isset($_GET['all_events']) && !isset($_GET['past_events'])
    && $_SESSION['role'] != 'Owner') {
        // If facilitator, they only see the events they manager
        if ($_SESSION['role'] == 'Facilitator') {
            $query .= " AND manager_id = ".$_SESSION['user_id'];
        }
        // If admin they see all events in their area
        else if ($_SESSION['role'] == 'Admin') {
            // For if the admin is managing an event outside their area
            $query .= " OR manager_id = ".$_SESSION['user_id'];

            if (isset($user_areas)) {
                // First area
                $query .= " AND (area_id = ".$user_areas[0]['area_id'];

                // Other areas
                for ($i = 1; $i < count($user_areas); $i++) {
                    $query .= " OR area_id = ".$user_areas[$i]['area_id'];
                }
                $query .= ")";
            }
            else {
                echo '<p class="fail">Failed to find user areas</p>';
                die();
            }

        }
    }
    // Run query
    $result = mysqli_query($conn, $query);

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
            <p class="Date"><?=$e->start_datetime->format('m/d/Y')?></p>
            <p class="Start-Time"><?=$e->start_datetime->format('h:i A')?></p>
            <p class="End-Time"><?=$e->end_datetime->format('h:i A')?></p>
            <p class="Manager">Managed By: 
                <?=$e->manager_id['first_name']." ".$e->manager_id['last_name']?>
            </p>
            <p class="location">
                <?=$e->location['name']."\n".
                $e->location['address']."\n".$e->location['city'].", ".
                $e->location['state']." ".$e->location['zip_code']?>
            </p>
            <p class="client_name">Client: <?=$e->client_name?></p>
            <p class="areas"><?php
                $s = "";
                foreach ($e->areas as $a) {
                    $s .= ", ".$a['area_name'];
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

            if ($e->sessions) {
                echo '<a id="view_session" href="SessionManagement.php?session_id='.$e->$session.'"
                    class="session">Session</a>';
                echo '<input type="hidden" id="session_id" name="session_id" value="'.$e->$session.'">';
            } 
            else {
                echo '<p class="session">No session</p>';
            }
            
            // Editing button
            // If facilitator
            if ($_SESSION['role'] == 'Facilitator') {
                if ($_SESSION['user_id'] == $e->manager_id) {
                    echo '<a href="eventManagement.php?event_id='.$e->id.'" class="eventEditor">Edit Event</a>';
                }
            }
            // If admin
            else if ($_SESSION['role'] == 'Admin') {
                foreach ($user_areas as $a) {
                    if ($e->area['area_id'] == $a['area_id']) {
                        echo '<a href="eventManagement.php?event_id='.$e->id.'" class="eventEditor">Edit Event</a>';
                        break;
                    }
                }
            }
            // If owner
            else {
                echo '<a href="eventManagement.php?event_id='.$e->id.'" class="eventEditor">Edit Event</a>';
            }
            ?>
        </div>
    <?php 
    }
    echo "</div>";
    ?>
    <a href="eventManagement.php?new_event=true" id="create_event">Create New Event</a>
<?php
}
?>
</body>
</html>