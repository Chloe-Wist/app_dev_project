<?php
// Chloe Wist, Ethan Janssen, Hazel Lim, Jonathan Smith
// Handles creating, deleting, and editing events

// Deleting event
if (isset($_GET['event_id']) && isset($_GET['delete_event'])) {
    try {
        // Delete from event_to_area
        $query = "DELETE FROM event_to_area WHERE event_id = ".$_GET['event_id'];
        mysqli_query($conn, $query);

        $query = "DELETE FROM event WHERE event_id = ".$_GET['event_id'];
        mysqli_query($conn, $query);

        echo '<p class="success">Deleted event</p>';
    }
    catch (Exception $e) {
        echo '<p class="fail">Failed to delete event</p>';
    }
}

// Creating and editing events
else if (isset($_POST['event_id'])) {
    // Save all data in variables
    $event_id = trim(htmlspecialchars($_POST["event_id"]));
    $event_name = trim(htmlspecialchars($_POST["event_name"]));
    $description = trim(htmlspecialchars($_POST["description"]));
    $date = trim(htmlspecialchars($_POST["date"]));
    $start_time = trim(htmlspecialchars($_POST["start_time"]));
    $end_time = trim(htmlspecialchars($_POST["end_time"]));

    $area;
    foreach($_POST["area"] as $p) {
        $area[] = trim(htmlspecialchars($p));
    }

    $manager = trim(htmlspecialchars($_POST["manager"]));

    $location_id;
    // Only set if the user picked from a drop down
    if (isset($_POST['location'])) {
        $location_id = trim(htmlspecialchars($_POST["location"]));
    }
    // Only set if the user is creating a new location or 
    // editing a current one
    else if (isset($_POST["location_id"])) {
        $location_id = trim(htmlspecialchars($_POST["location_id"]));
        $location_name = trim(htmlspecialchars($_POST["location_name"]));
        $address = trim(htmlspecialchars($_POST["location_address"]));
        $city = trim(htmlspecialchars($_POST["location_city"]));
        $state = trim(htmlspecialchars($_POST["location_state"]));
        $zipcode = trim(htmlspecialchars($_POST["location_zipcode"]));
        if (isset($_POST["for_all_events"])) {
            $for_all_events = false;
        }
        else {
            $for_all_events = true;
        }
    }
    // Chose original location
    else {
        $location_id = trim(htmlspecialchars($_POST["location_id_original"]));
    }

    $client = trim(htmlspecialchars($_POST["client"]));

    // If the location is new, or they don't want the location 
    // they're editing to affect other locations
    if ($location_id == -1 || (isset($location_name) && 
            !$for_all_events)) {
        $query = "INSERT INTO location (name,
            address, city, state, zip_code)
            VALUES ('".
                $location_name."', '".
                $address."', '".
                $city."', '".
                $state."', '".
                $zipcode."')";
        try {
            mysqli_query($conn, $query);
            // Reassign location ID to newly inserted location ID
            $location_id = mysqli_insert_id($conn);
        }
        catch (Exception $e) {
            echo '<p class="error">Failed to create location</p>';
            // Stop updating data
            return;
        }
    }
    // If a user is editing an existing location
    else if (isset($location_name)) {
        $query = "UPDATE location
            SET name = '".$location_name."',
            address = '".$address."',
            city = '".$city."',
            state = '".$state."', 
            zip_code = '".$zipcode."'
            WHERE location_id = ".$location_id;

        try {
            mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo '<p class="error">Failed to update location</p>';
            echo $e;
            echo $query;
            // Stop updating data
            return;
        }
    }


    // If the event is a new one
    if ($event_id == -1) {
        $query = 
                "INSERT INTO event (event_name, event_description, 
                event_date, start_time, end_time, manager_id, location_id,
                client_name)
                VALUES ('".
                    $event_name."', '".
                    $description."', '".
                    $date."', '".
                    $start_time."', '".
                    $end_time."', ".
                    $manager.", ".
                    $location_id.", '".
                    $client."')";
        try {
            mysqli_query($conn, $query);
            // Reassign event ID to newly inserted event's ID
            $event_id = mysqli_insert_id($conn);
        }
        catch (Exception $e) {
            echo '<p class="error">Failed to create event</p>';
            return;
        }
    }
    // Update existing event
    else {
        $query = "UPDATE event set
            event_name = '".$event_name."',
            event_description = '".$description."', 
            event_date = '".$date."', 
            start_time = '".$start_time."', 
            end_time = '".$end_time."',
            manager_id = ".$manager.",
            location_id = ".$location_id.",
            client_name = '".$client."'
            WHERE event_id = ".$event_id;

        try {
            mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo '<p class="error">Failed to update event</p>';
            return;
        }
    }

    // Update areas

    // Query to delete extra areas
    $query = "DELETE FROM event_to_area WHERE event_id = ".$event_id;
    // Run through each area
    foreach($area as $a) {
        // Insert all checked areas
        $q = "INSERT IGNORE INTO event_to_area (event_id, area_id) 
            VALUES (".$event_id.", ".$a.")";
        try {
            mysqli_query($conn, $q);
        }
        catch (Exception $e) {
            echo '<p class="error">Failed to update event areas</p>';
            return;
        }

        // Prevent the delete statement from deleting necessary areas
        $query .= " AND area_id != ".$a;
    }

    // Delete unmatching areas
    try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e) {
        echo '<p class="error">Failed to delete changed event areas</p>';
        return;
    }
}
?>