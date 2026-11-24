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


// Get user areas
// If the user is an owner, return all areas. Otherwise get the areas 
// associated with the user. If no areas are found, store -1 as area_id
$user_areas;
if ($_SESSION['role'] == 'Owner') {
    $query = "SELECT area_id, area_name FROM area";
}
else {
    $query = "SELECT area.area_id, area_name FROM user_to_area 
    JOIN area ON user_to_area.area_id = area.area_id
    WHERE user_id = ".$_SESSION['user_id'];
}

$result = mysqli_query($conn, $query);

while ($a = mysqli_fetch_assoc($result)) {
    $user_areas[] = ['area_id' => $a['area_id'],
                    'area_name' => $a['area_name']];
}

if (!isset($user_areas)) {
    $user_areas[] = ['area_id' => -1,
                    'area_name' => 'No user areas'];
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Management</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/eventManagement.css">
    <script src="scripts/eventManagement.js"></script>
</head>
<body>
<?php

$title = "Event Management";
include("helpers/header.php");

require("helpers/events.php");


// If an event was edited, handle the edits
require("helpers/events_changes_handler.php");

// If the user wants to create an event or to edit an event
if ((isset($_GET['event_id']) || isset($_GET['new_event']))
        && !isset($_GET['delete_event'])) {
    // Retrieve information on the event
    if (isset($_GET['event_id'])) {
        $e = new Events($_GET['event_id']);
    }

    // Make a form for saving event info
    ?>
    <a href="eventManagement.php">Cancel</a>
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
        <textarea name="description" id="description" placeholder="Type here..."><?=isset($e)?$e->description:""?></textarea>

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

        <!-- Areas -->
        <fieldset id="areas_fieldset">
            <legend>Areas</legend>

            <?php
            // If the user is a facilitator or an admin,
            // they can select the areas that either are already on the event,
            // or that they have
            $areas;

            // Areas of the event
            if (isset($e) && !empty($e->areas)) {
                foreach($e->areas as $a) {
                    $areas[] = ['area_id' => $a['area_id'],
                                'area_name' => $a['area_name'],
                                'is_checked' => true];
                }
            }

            // Areas of the user
            foreach ($user_areas as $a) {
                $new = ['area_id' => $a['area_id'],
                        'area_name' => $a['area_name'],
                        'is_checked' => true]; // true just for checking
                                                // if in array
                
                if (!isset($areas) || !in_array($new, $areas)) {
                    $areas[] = ['area_id' => $a['area_id'],
                                'area_name' => $a['area_name'],
                                'is_checked' => false];
                }
            }

            // Add all the areas to the checklist
            foreach ($areas as $a) {
                echo '<input type="checkbox" id="area'.$a['area_id'].'" 
                    name="area[]" value="'.$a['area_id'].'" '.
                    ($a['is_checked']?"checked":"").'>';
                echo '<label for="area'.$a['area_id'].'">'.
                    $a['area_name'].'</label>';
            }
            ?>
        </fieldset>

        <!-- Managed By -->
        <?php 
        //  Facilitators cannot change the manager of the event
        if ($_SESSION['role'] == "Facilitator") {
            echo "<p>Managed By</p>";
            if (isset($e)) {
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
                .(!empty($e->areas)?$e->areas[0]['area_id']:$user_areas[0]['area_id']); 
                    // If the event has already been made, you can select any person
                    // in the area that the event has
                    
            if (!empty($e->areas)) {
                for ($i = 1; $i < count($e->areas); $i++) {
                    $query .= " OR area_id = ".$e->areas[$i]['area_id'];
                }
            }

            $result = mysqli_query($conn, $query);

            // Record all users who match
            $possible_managers;
            while ($a = mysqli_fetch_assoc($result)) {
                $possible_managers[] = ["manager_id" => $a['user_id'],
                                        "name" => $a['first_name'].' '.$a['last_name'],
                                        "area_matches" => true];
            }

            // Get all other users
            $query = "SELECT user_id, first_name, last_name FROM users 
                    JOIN roles ON users.role_id = roles.role_id
                    WHERE role_name != 'Volunteer'"; 

            if(isset($possible_managers) && !empty($possible_managers)) {
                foreach ($possible_managers as $p) {
                    $query .= " AND user_id != ".$p['manager_id'];
                }
            }

            $result = mysqli_query($conn, $query);

            while ($a = mysqli_fetch_assoc($result)) {
                $possible_managers[] = ["manager_id" => $a['user_id'],
                                        "name" => $a['first_name'].' '.$a['last_name'],
                                        "area_matches" => false];
            }

            // Create list of possible managers
            echo '<label for="manager">Managed By</label>';
            echo '<select id="manager" name="manager">';
            // Default option
            if (!isset($e)) {
                echo '<option value="-1" selected disabled hidden>-- Select a manager --</option>';
            }
            // Actual options
            foreach ($possible_managers as $p) {
                echo('<option value="'.$p['manager_id'].'" 
                '.(((isset($e) && $p['manager_id'] == $e->manager_id['user_id']) ||
                    !isset($e) && $p['manager_id'] == $_SESSION['user_id'])?"selected":"").'>
                '.$p['name'].' ('.
                ($p['area_matches']?"Area Matches":"No Matching Areas").')</option>');
            }
            echo '</select>';
        } 

        ?>
        <!-- Location -->
        <p>Location</p>

        <!-- Current location information -->
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
            
        <!-- Change to a different previously defined location -->
        <button type="button" id="change_location">Change to different location</button>

        <?php
        // Edit current location
        // Only allow editing the current location if there is a current location
        if (isset($e)) { ?>
            <button type="button" id="edit_location_button">Edit Current Location</button>
        <?php } 

        // Create new location 
        // ?>
        <button type="button" id="create_location_button">Create Location</button>

        <!-- Change to a previously defined location -->
        <select id="location_select" hidden disabled name="location">
            <?php 
            // Default location
            echo '<option value="-1" selected disabled hidden>-- Select a location --</option>';

            // Retrieve all locations
            $query = "SELECT location_id, name FROM location";
            $result = mysqli_query($conn, $query);
            while ($a = mysqli_fetch_assoc($result)) {
                echo '<option value="'.$a['location_id'].'"
                '.((isset($e) && $a['location_id'] == $e->location['location_id'])?"selected":"").'>
                '.(isset($a['name'])?$a['name']:"Unnamed Location").'</option>';
            }
            ?>
        </select>
        <button type="button" hidden disabled id="cancel_location_select">Cancel</button>
        
        <!-- Edit current location, or create new one -->
        <fieldset hidden disabled id="edit_location">
            <input type="hidden" name="location_id" 
            value="<?=isset($e)?$e->location['location_id']:-1?>">

            <label for="location_name">Location Name</label>
            <input type="text" name="location_name" id="location_name"
                value="<?=(isset($e))?$e->location['name']:""?>">

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
        <input type="text" id="client" name="client" value="<?=isset($e)?$e->client_name:""?>">

        <!--Document -->
        <p>Placeholder for document upload</p>
        <?php

        // TODO ensure this works with session page
        if (isset($e) && $e->sessions) {
            echo '<button type="button" id="edit_session">View Session</button>';
            // echo '<input type="hidden" id="session_id" name="session_id" value="'.$e->$session.'">';
        } 
        else {
            echo '<p class="session">No session</p>';
            echo '<button type="button" id="new_session">Create a session</button>';
        }
        echo '<p hidden id="error"></p>';
        echo '<button type="submit" id="submit_event_changes">Save</button>';

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
        $query = "SELECT event.event_id FROM event 
            JOIN event_to_area ON event.event_id = event_to_area.event_id
            WHERE event_date < CURDATE()";
    }
    else {
        $query = "SELECT event.event_id FROM event 
            JOIN event_to_area ON event.event_id = event_to_area.event_id
            WHERE event_date >= CURDATE()";
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
            $query .= " AND (manager_id = ".$_SESSION['user_id'];

            if (isset($user_areas)) {
                // First area
                $query .= " OR (area_id = ".$user_areas[0]['area_id'];

                // Other areas
                for ($i = 1; $i < count($user_areas); $i++) {
                    $query .= " OR area_id = ".$user_areas[$i]['area_id'];
                }
                $query .= "))";
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
            <p class="client_name">Client: <?=isset($e->client_name)?$e->client_name:"No client name"?></p>
            <p class="areas"><?php
                $s = "";
                foreach ($e->areas as $a) {
                    $s .= $a['area_name'].", ";
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

            // Show RSVPs
            $query = "SELECT status FROM event_signup WHERE event_id = ".$e->id;

            try {
                // Retrieve RSVPs
                $result = mysqli_query($conn, $query);

                $rsvp = ['Yes' => 0,'Maybe' => 0,'No' => 0,'No Reply' => 0];
                while ($a = mysqli_fetch_assoc($result)) {
                    if ($a['status'] == "Yes") {
                        $rsvp["Yes"]++;
                    }
                    else if ($a['status'] == "Maybe") {
                        $rsvp["Maybe"]++;
                    }
                    else if ($a['status'] == "No") {
                        $rsvp["No"]++;
                    }
                    else {
                        $rsvp["No Reply"]++;
                    }
                }

                // Display RSVPs
                ?>
                <div class="rsvp">
                    <p>Yes: <?=$rsvp['Yes']?></p>
                    <p>Maybe: <?=$rsvp['Maybe']?></p>
                    <p>No: <?=$rsvp['No']?></p>
                    <p>No Reply: <?=$rsvp['No Reply']?></p>
                </div>
                <?php
            }
            catch (Exception $e) {
                echo '<p class="fail">Failed to retrieve RSVPs</p>';
            }

            // Manage volunteers
            echo '<a class="manage_volunteers" href="manageEventVolunteers.php?event_id='.$e->id.'">Manage Volunteers</a>';
            
            // Editing button
            // If facilitator
            if ($_SESSION['role'] == 'Facilitator') {
                if ($_SESSION['user_id'] == $e->manager_id) {
                    echo '<a href="eventManagement.php?event_id='.$e->id.'" class="eventEditor">Edit Event</a>';
                }
            }
            // If admin
            else if ($_SESSION['role'] == 'Admin') {
                foreach ($user_areas as $u_area) {
                    foreach($e->areas as $e_area) {
                        if ($e_area['area_id'] == $u_area['area_id']) {
                            echo '<a href="eventManagement.php?event_id='.$e->id.'" class="eventEditor">Edit Event</a>';
                            break;
                        }
                    }
                }
            }
            // If owner
            else {
                echo '<a href="eventManagement.php?event_id='.$e->id.'" class="eventEditor">Edit Event</a>';
            }

            // Delete button
            echo '<a class="delete_events" href="eventManagement.php?event_id='.$e->id.'&delete_event=true">Delete Event</a>';
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