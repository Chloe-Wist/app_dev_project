<?php
// Chloe Wist, Ethan Janssen, Hazel Lim, Jonathan Smith
// Handles changes saved for an event

if (isset($_POST['event_id'])) {
    // Handle location
    if (isset($_POST['']))

    // If the event is a new one
    if ($_POST['event_id'] == -1) {
        try {

            $query = 
                "INSERT INTO event (event_name, event_description, 
                event_date, start_time, end_time, manager_id, location_id,
                client_name)
                VALUES (".
                    $_POST['event_name'].", ".
                    $_POST['description'].", ".
                    $_POST['date'].", ".
                    $_POST['start_time'].", ".
                    $_POST['end_time'].", ".
                    $_POST['manager'].", ".
                    $_POST['event_name'].", ".
                ")";
        }
        catch (Exception $e) {
            echo '<p class="fail">Failed to save event</p>';
        }
    }
}
?>