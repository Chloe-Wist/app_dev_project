<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 *
 * This file will create the events page that allows users
 * to browse events and view and edit their RSVP statuses.
 *
 * TODO: Link each event to Event.php for details and RSVP
 * TODO: Ensure past events are view-only
 * TODO: Make address clickable and open a Google search for the address
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
    $sql = "SELECT Event.*, Location.*, Event_Signup.Status
            FROM Event
            JOIN Location
                ON Event.Location_ID = Location.Location_ID
            JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
            WHERE Event_Signup.User_ID = ?
                AND Event_Signup.Is_Invited = TRUE
                AND TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "signedup") {
    // Gets upcoming events the user has signed up for
    $sql = "SELECT Event.*, Location.*, Event_Signup.Status
            FROM Event
            JOIN Location
                ON Event.Location_ID = Location.Location_ID
            JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
            WHERE Event_Signup.User_ID = ?
                AND Event_Signup.Status IN ('Yes', 'Maybe')
                AND TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "yourareas") {
    // Gets upcoming events assigned to the user's areas
    $sql = "SELECT DISTINCT Event.*, Location.*, Event_Signup.Status
            FROM Event
            JOIN Location
                ON Event.Location_ID = Location.Location_ID
            LEFT JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
                AND Event_Signup.User_ID = ?
            JOIN Event_to_Area
                ON Event.Event_ID = Event_to_Area.Event_ID
            JOIN User_to_Area
                ON Event_to_Area.Area_ID = User_to_Area.Area_ID
            WHERE User_to_Area.User_ID = ?
                AND TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "all") {
    // Gets all upcoming events
    $sql = "SELECT Event.*, Location.*, Event_Signup.Status
            FROM Event
            JOIN Location
                ON Event.Location_ID = Location.Location_ID
            LEFT JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
                AND Event_Signup.User_ID = ?
            WHERE TIMESTAMP(Event.Event_Date, Event.End_Time) >= NOW()
            ORDER BY Event.Event_Date ASC, Event.Start_Time ASC";
} elseif ($tab === "past") {
    // Gets all events that have already ended
    $sql = "SELECT Event.*, Location.*, Event_Signup.Status
            FROM Event
            JOIN Location
                ON Event.Location_ID = Location.Location_ID
            LEFT JOIN Event_Signup
                ON Event.Event_ID = Event_Signup.Event_ID
                AND Event_Signup.User_ID = ?
            WHERE TIMESTAMP(Event.Event_Date, Event.End_Time) < NOW()
            ORDER BY Event.Event_Date DESC, Event.Start_Time DESC";
}

// Executes the query for the selected tab
$stmt = $conn->prepare($sql);
if ($tab === "yourareas") {
    $stmt->bind_param("ii", $user_id, $user_id);
} else {
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
                        // Formats the event date
                        $month = date("M", strtotime($event["Event_Date"]));
                        $day = date("d", strtotime($event["Event_Date"]));
                        $weekday = date("D", strtotime($event["Event_Date"]));

                        // Formats the event start and end times
                        $start_time = date("g:i A", strtotime($event["Start_Time"]));
                        $end_time = date("g:i A", strtotime($event["End_Time"]));
                        ?>
                        <div class="event-card">

                            <div class="event-date">
                                <span class="event-month">
                                    <?php echo $month; ?>
                                </span>

                                <span class="event-day">
                                    <?php echo $day; ?>
                                </span>

                                <span class="event-weekday">
                                    <?php echo $weekday; ?>
                                </span>
                            </div>

                            <div class="event-info">
                                <h2><?php echo htmlspecialchars($event["Event_Name"]); ?></h2>

                                <p>
                                    <?php echo $start_time; ?>
                                    -
                                    <?php echo $end_time; ?>
                                </p>

                                <p>
                                    <?php echo htmlspecialchars($event["Address"]); ?>
                                </p>
                            </div>

                            <div class="event-rsvp">
                                <?php echo htmlspecialchars($event["Status"] ?? "No Reply"); ?>
                            </div>

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