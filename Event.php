<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 *
 * This file displays the details of a selected event
 * and allows users to view and edit their RSVP status.
 *
 * TODO: Integrate notifications to event overseer when RSVP changes to No
 */

// Connects to database
require_once "helpers/config.php";

// Starts a PHP session if one has not already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirects user to login page if they are not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Gets the logged-in user's ID
$user_id = $_SESSION["user_id"];

// Gets the selected event's ID from the URL
$event_id = $_GET["event_id"] ?? null;

// Redirects to Events.php if the event ID is missing or invalid
if (!filter_var($event_id, FILTER_VALIDATE_INT) || $event_id <= 0) {
    header("Location: Events.php");
    exit();
}

// Gets the selected event, its location, and the user's RSVP status
$sql = "SELECT Event.*, Location.*, Event_Signup.Status,
            TIMESTAMP(Event.Event_Date, Event.End_Time) < NOW() AS Is_Past
        FROM Event
        JOIN Location
            ON Event.Location_ID = Location.Location_ID
        LEFT JOIN Event_Signup
            ON Event.Event_ID = Event_Signup.Event_ID
            AND Event_Signup.User_ID = ?
        WHERE Event.Event_ID = ?";

// Executes the query
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

// Redirects to Events.php if the event does not exist
if ($result->num_rows === 0) {
    header("Location: Events.php");
    exit();
}

// Retrieves the selected event
$event = $result->fetch_assoc();

// Formats the event date and times
$event_date = date("l, F j, Y", strtotime($event["Event_Date"]));
$start_time = date("g:i A", strtotime($event["Start_Time"]));
$end_time = date("g:i A", strtotime($event["End_Time"]));

// Determines whether the event has already ended
$is_past = (bool) $event["Is_Past"];

// Constructs the full event address
$address = $event["Address"] . ", " . $event["City"] . ", "
           . $event["State"] . " " . $event["Zip_Code"];

// Constructs a Google search URL for the event address
$address_url = "https://www.google.com/search?q=" . urlencode($address);

// Gets the user's current RSVP status
$status = $event["Status"] ?? "No Reply";

// Generates a CSRF token for the RSVP form
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Handles RSVP submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Validates the submitted CSRF token
    $submitted_token = $_POST["csrf_token"] ?? "";

    if (!is_string($submitted_token) ||
        !hash_equals($_SESSION["csrf_token"], $submitted_token)) {
        http_response_code(403);
        exit("Invalid CSRF token.");
    }

    // Prevents RSVP changes for past events
    if ($is_past) {
        http_response_code(403);
        exit("RSVPs cannot be changed for past events.");
    }

    // Gets and validates the submitted RSVP status
    $new_status = $_POST["status"] ?? "";
    $valid_statuses = ["Yes", "Maybe", "No"];

    if (!in_array($new_status, $valid_statuses, true)) {
        http_response_code(400);
        exit("Invalid RSVP status.");
    }

    // Inserts or updates the user's RSVP
    $sql = "INSERT INTO Event_Signup (Event_ID, User_ID, Status)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE Status = VALUES(Status)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $event_id, $user_id, $new_status);
    $stmt->execute();

    // Redirects back to the event page to display the updated RSVP
    header("Location: Event.php?event_id=" . $event_id);
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/event.css">
</head>

<body>
    <?php
    // Displays the shared dashboard header
    $title = "Event Details";
    include "helpers/header.php";
    ?>

    <div class="page">
        <main>
            <a href="Events.php">Back to Events</a>

            <section class="event-details">
                <h1><?php echo htmlspecialchars($event["Event_Name"]); ?></h1>

                <p>
                    <strong>Date:</strong>
                    <?php echo $event_date; ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?php echo $start_time . " - " . $end_time; ?>
                </p>

                <p>
                    <strong>Location:</strong>
                    <a href="<?php echo htmlspecialchars($address_url); ?>"
                        target="_blank" rel="noopener noreferrer">
                         <?php echo htmlspecialchars($address); ?>
                    </a>
                </p>

                <?php if (!empty($event["Client_Name"])) { ?>
                    <p>
                        <strong>Client:</strong>
                        <?php echo htmlspecialchars($event["Client_Name"]); ?>
                    </p>
                <?php } ?>

                <h2>Description</h2>
                <p>
                    <?php echo htmlspecialchars($event["Event_Description"]); ?>
                </p>
            </section>

            <section class="event-response">
                <h2>RSVP</h2>

                <p>
                    <strong>Current response:</strong>
                    <?php echo htmlspecialchars($status); ?>
                </p>

                <?php if (!$is_past) { ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                        <button type="submit" name="status" value="Yes">Yes</button>
                        <button type="submit" name="status" value="Maybe">Maybe</button>
                        <button type="submit" name="status" value="No">No</button>
                    </form>
                <?php } else { ?>
                    <p>This event has ended. RSVPs can no longer be changed.</p>
                <?php } ?>
            </section>
        </main>
    </div>
</body>
</html>