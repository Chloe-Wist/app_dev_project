<?php
// Chloe Wist, Ethan Janssen, Hazel Lim, Jonathan Smith

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
    <title>Event Volunteer Management</title>
    <link rel="stylesheet" href="styles/main.css">
    <script src="scripts/manageEventVolunteers.js"></script>
</head>
<body>
<?php

$title = "Manage Event Volunteers";
include("helpers/header.php");

$event_id = $_GET['event_id'];

// Handle invites
if (isset($_POST['invited_user'])) {
    $query = "INSERT INTO event_signup (event_id, 
        user_id, status, is_invited) VALUES";

    // Used for making the statement correct
    $count = 0;
    foreach ($_POST['invited_user'] as $u) {
        if ($count > 0) {
            $query .= ",";
        }
        $query .= " (".$event_id.", ".$u.", 'No Reply', 1)";
        
        $count++;
    }

    try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e) {
        echo '<p class="fail">Failed to invite users</p>';
    }
}
else if (isset($_POST['remove_rsvp'])) {
    $query = "DELETE FROM event_signup WHERE";

    // Used for making the statement correct
    $count = 0;
    foreach ($_POST['remove_rsvp'] as $u) {
        if ($count > 0) {
            $query .= " OR";
        }
        $query .= " (event_id = ".$event_id." AND user_id = ".$u.")";
        
        $count++;
    }

    try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e) {
        echo '<p class="fail">Failed to delete RSVP</p>';
    }
}

// Get name of event
$query = "SELECT event_name, start_time, end_time, event_date, manager_id 
    FROM event WHERE event_id = ".$event_id;
$result = mysqli_fetch_assoc(mysqli_query($conn, $query));
$event_name = $result['event_name'];
$event_start_time = new DateTimeImmutable($result['event_date']." ".
    $result['start_time']);
$event_end_time = new DateTimeImmutable($result['event_date']." ".
    $result['end_time']);
$manager_id = $result['manager_id'];

// Display name
echo '<h1>'.$event_name.'</h1>';

// Return button 
echo '<a href="EventManagement.php">Return</a>';

// Get areas
$event_areas = [];
$query = "SELECT area_name, area.area_id FROM event_to_area 
    JOIN area ON area.area_id = event_to_area.area_id
    WHERE event_id = ".$event_id;
$result = mysqli_query($conn, $query);
while ($a = mysqli_fetch_assoc($result)) {
    array_push($event_areas, ["area_id" => $a['area_id'], 
                        "area_name" => $a['area_name']]);
}

// Display Areas
echo'<p class="areas">';
$s = "";
foreach ($event_areas as $a) {
    $s .= $a['area_name'].", ";
}
echo $s;
echo '</p>';

// Get user RSVP information
$query = "SELECT status, users.user_id, first_name, last_name, 
    role_name
    FROM event_signup JOIN users
    ON users.user_id = event_signup.user_id
    JOIN roles ON users.role_id = roles.role_id
    WHERE event_id = ".$event_id;

try {
    // Retrieve RSVPs
    $result = mysqli_query($conn, $query);

    // New query for finding users who are not signed up
    $query = "SELECT user_id, first_name, last_name, role_name
        FROM users JOIN roles ON users.role_id = roles.role_id
        WHERE user_id != ".$_SESSION['user_id']." AND user_id != "
        .$manager_id;

    // Stores users by RSVP
    $rsvp_yes = [];
    $rsvp_maybe = [];
    $rsvp_no = [];
    $rsvp_no_reply = [];


    while ($a = mysqli_fetch_assoc($result)) {
        if ($a['status'] == "Yes") {
            array_push($rsvp_yes, $a);
        }
        else if ($a['status'] == "Maybe") {
            array_push($rsvp_maybe, $a);
        }
        else if ($a['status'] == "No") {
            array_push($rsvp_no, $a);
        }
        else {
            array_push($rsvp_no_reply, $a);
        }
        $query .= " AND user_id != ".$a['user_id'];
    }

    // Display RSVPs
    ?>
    <div id="rsvp">
        <button id="rsvp_yes">Yes (<?=count($rsvp_yes)?>)</button>
        <button id="rsvp_maybe">Maybe (<?=count($rsvp_maybe)?>)</button>
        <button id="rsvp_no">No (<?=count($rsvp_no)?>)</button>
        <button id="rsvp_no_reply">No Reply (<?=count($rsvp_no_reply)?>)</button>
    </div>

    
    <!-- Wrap the invite section in a div -->
    <div id="invite_section">

    <!-- Buttons for choosing which users to show -->
    <button id="show_all_areas">Show all areas</button>
    <button hidden disabled id="show_matching_areas">Show only matching areas</button>

    <button id="show_unavailable">Show unavailable</button>
    <button hidden disabled id="show_available">Show only available</button>
    
    <?php


    // Find users who have not RSVPed or been invited
    $result = mysqli_query($conn, $query);

    echo '<div id="list_users">';
    echo '<form method="POST" 
        action="manageEventVolunteers.php?event_id='.$event_id.'" 
        id="invite">';
    // Gather data for these users
    while ($a = mysqli_fetch_assoc($result)) {

        $availability_matches = false;
        
        // Get availability for the day that matches
        $query = "SELECT Day, Start_Time, End_Time 
            FROM availability WHERE user_id = ".$a['user_id']."
            AND Day = '".$event_start_time->format('l')."'";

        $data = mysqli_query($conn, $query);

        // Test if the user is available
        while ($d = mysqli_fetch_assoc($data)) {
            // Create dates that happen on the same day as the event,
            // but during the availability time
            $user_start_time = new DateTime(
                $event_start_time->format('m/d/Y')
                ." ".$d['Start_Time']);

            $user_end_time = new DateTime(
                $event_end_time->format('m/d/Y')
                ." ".$d['End_Time']);

            // If the event happens within a user's availability,
            // mark them as available
            if (($user_start_time <= $event_start_time)
                && ($user_end_time >= $event_end_time)) {
                $availability_matches = true;
            }
        }

        // Find out if areas match
        $area_matches = false;

        $user_areas = [];
        if ($a['role_name'] == 'Owner') {
            $area_matches = true;
        }
        else {
            $query = "SELECT area.area_id, area_name FROM user_to_area 
            JOIN area ON user_to_area.area_id = area.area_id
            WHERE user_id = ".$a['user_id'];
        

            $data = mysqli_query($conn, $query);

            while ($d = mysqli_fetch_assoc($data)) {
                $user_areas[] = ['area_id' => $d['area_id'],
                                'area_name' => $d['area_name']];
            }

            if (!isset($user_areas)) {
                $user_areas[] = ['area_id' => -1,
                                'area_name' => 'No user areas'];
            } 
        }

        // Compare user areas to event areas to see if any match

        foreach($user_areas as $u_area) {
            foreach($event_areas as $e_area) {
                if ($e_area['area_name'] == $u_area['area_name']) {
                    $area_matches = true;
                    break 2;
                }
            }
            
        }

        // Display those users ?>
        <div class="user<?=($availability_matches?"1":"0").($area_matches?"1":"0")?>"
             <?=(!$availability_matches || !$area_matches)?
                "hidden":""?>>
            <p class="name"><?=$a['first_name']." ".$a['last_name']?></p>
            <p class="role"><?=$a['role_name']?></p>
            <p class="availability_matches"><?=($availability_matches)?
                "Available":"Not Available"?></p>
            <p class="area_matches"><?=($area_matches)?
                "Area Matches":"No Matching Areas"?></p>
            <input type="checkbox" name="invited_user[]" value="<?=$a["user_id"]?>">
            <label for="invited_user">Check to invite</label>
        </div>

        <?php
    }
    ?>
    <button>Invite</button>
    </form>
    </div>
    
<?php   
}
catch (Exception $e) {
    echo '<p class="fail">Failed to retrieve RSVPs</p>';
}

echo "</div>";

echo '<button id="return_to_invite" hidden disabled>Return to invitation page</button>';


// Hidden lists that appear when the RSVP buttons are clicked
// They show a list of users that have different RSVPs and will
// allow managers to remove them from the event/RSVP
echo '<form method="POST" 
        action="manageEventVolunteers.php?event_id='.$event_id.'" 
        id="remove_user">';

// Yes RSVPs
echo '<fieldset hidden disabled id="rsvp_yes_list">';
if (count($rsvp_yes) == 0) {
    echo '<p class="empty_rsvp">No users<p>';
}
else {
    foreach($rsvp_yes as $u) {
        ?>
        <div class="user">
            <p class="user_name"><?=$u['first_name']." ".$u['last_name'] ?></p>
            <p class="role_name"><?=$u['role_name']?></p>

            <input type="checkbox" name="remove_rsvp[]" value="<?=$u["user_id"]?>">
            <label for="remove_rsvp">Check to Remove User</label>

        </div>
        <?php
    }
    echo '<button>Remove</button>';
}
echo '</fieldset>';


// Maybe RSVPs
echo '<fieldset hidden disabled id="rsvp_maybe_list">';
if (count($rsvp_maybe) == 0) {
    echo '<p class="empty_rsvp">No users<p>';
}
else {
    foreach($rsvp_maybe as $u) {
        ?>
        <div class="user">
            <p class="user_name"><?=$u['first_name']." ".$u['last_name'] ?></p>
            <p class="role_name"><?=$u['role_name']?></p>

            <input type="checkbox" name="remove_rsvp[]" value="<?=$u["user_id"]?>">
            <label for="remove_rsvp">Check to Remove User</label>

        </div>
        <?php
    }
    echo '<button>Remove</button>';
}
echo '</fieldset>';

// No RSVPs
echo '<fieldset hidden disabled id="rsvp_no_list">';
if (count($rsvp_no) == 0) {
    echo '<p class="empty_rsvp">No users<p>';
}
else {
    foreach($rsvp_no as $u) {
        ?>
        <div class="user">
            <p class="user_name"><?=$u['first_name']." ".$u['last_name'] ?></p>
            <p class="role_name"><?=$u['role_name']?></p>

            <input type="checkbox" name="remove_rsvp[]" value="<?=$u["user_id"]?>">
            <label for="remove_rsvp">Check to Remove User</label>

        </div>
        <?php
    }
    echo '<button>Remove</button>';
}
echo '</fieldset>';

// No Reply RSVPs
echo '<fieldset hidden disabled id="rsvp_no_reply_list">';
if (count($rsvp_no_reply) == 0) {
    echo '<p class="empty_rsvp">No users<p>';
}
else {
    foreach($rsvp_no_reply as $u) {
        ?>
        <div class="user">
            <p class="user_name"><?=$u['first_name']." ".$u['last_name'] ?></p>
            <p class="role_name"><?=$u['role_name']?></p>

            <input type="checkbox" name="remove_rsvp[]" value="<?=$u["user_id"]?>">
            <label for="remove_rsvp">Check to Remove User</label>

        </div>
        <?php
    }
    echo '<button>Remove</button>';
}
echo '</fieldset>';

