<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 *
 * This file will create the events page that allows users
 * to browse events and view and edit their RSVP statuses.
 *
 * TODO: Display events in a calendar list (TeamReach style)
 * TODO: Display event name, date, time, location, and RSVP status
 * TODO: Link each event to Event.php for details and RSVP
 * TODO: Ensure past events are view-only
 * TODO: Add CSS
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

// Gets the selected event tab from the URL
$tab = $_GET["tab"] ?? "invited";

// Defines the available event tabs
$valid_tabs = ["invited", "signedup", "yourareas", "all", "past"];

// Defaults to Invited if the selected tab is invalid
if (!in_array($tab, $valid_tabs)) {
    $tab = "invited";
}

if ($tab === "invited") {
    // Gets events that the logged-in user has been invited to
    $sql = "SELECT Event.*
            FROM Event
            JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
            WHERE Event_Signup.User_ID = ?
                AND Event_Signup.Is_Invited = TRUE
                AND TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "signedup") {
    // Gets upcoming events the user has signed up for
    $sql = "SELECT Event.*
            FROM Event
            JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
            WHERE Event_Signup.User_ID = ?
                AND Event_Signup.Status IN ('Yes', 'Maybe')
                AND TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "yourareas") {
    // Gets upcoming events assigned to the user's areas
    $sql = "SELECT DISTINCT Event.*
            FROM Event
            JOIN Event_to_Area
                ON Event.Event_ID = Event_to_Area.Event_ID
            JOIN User_to_Area
                ON Event_to_Area.Area_ID = User_to_Area.Area_ID
            WHERE User_to_Area.User_ID = ?
                AND TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "all") {
    // Gets all upcoming events
    $sql = "SELECT Event.*
            FROM Event
            WHERE TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "past") {
    // Gets all events that have already ended
    $sql = "SELECT Event.*
            FROM Event
            WHERE TIMESTAMP(Event.Event_Date, Event.End_Time) < NOW()
            ORDER BY Event.Event_Date DESC, Event.Start_Time DESC";
}

// Executes the query for the selected tab
$stmt = $conn->prepare($sql);
if ($tab === "invited" || $tab === "signedup" || $tab === "yourareas") {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/events.css">
</head>

<body>
    <?php
    // Displays the shared dashboard header
    $title = "Events";
    include "helpers/header.php";
    ?>

    <div class="page">
        <main>
            <p>Browse upcoming events and manage your RSVPs.</p>

            <nav class="event-tabs">
                <a href="Events.php?tab=invited"
                class="<?php echo ($tab === 'invited') ? 'active' : ''; ?>">
                    Invited
                </a>

                <a href="Events.php?tab=signedup"
                class="<?php echo ($tab === 'signedup') ? 'active' : ''; ?>">
                    Signed Up
                </a>

                <a href="Events.php?tab=yourareas"
                class="<?php echo ($tab === 'yourareas') ? 'active' : ''; ?>">
                    Your Areas
                </a>

                <a href="Events.php?tab=all"
                class="<?php echo ($tab === 'all') ? 'active' : ''; ?>">
                    All Events
                </a>

                <a href="Events.php?tab=past"
                class="<?php echo ($tab === 'past') ? 'active' : ''; ?>">
                    Past
                </a>
            </nav>

            <section class="event-list">
                <?php
                if ($result->num_rows > 0) {
                    while ($event = $result->fetch_assoc()) {
                        ?>
                        <div class="event-card">
                            <h2><?php echo htmlspecialchars($event["Event_Name"]); ?></h2>

                            <p>
                                Date: <?php echo $event["Event_Date"]; ?>
                            </p>

                            <p>
                                Time: <?php echo $event["Start_Time"]; ?>
                                -
                                <?php echo $event["End_Time"]; ?>
                            </p>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No events found.</p>";
                }
                ?>
            </section>
        </main>
    </div>
</body>
</html>