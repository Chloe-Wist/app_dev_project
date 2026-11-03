<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 * 
 * This file will create the availability page that allows users
 * to enter and manage their weekly availability.
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

$message = "";

// Processes availability changes when a form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_SESSION["user_id"];

    // Adds a new availability period
    if ($_POST["action"] == "add") {
        $day = $_POST["day"];
        $startTime = $_POST["start_time"];
        $endTime = $_POST["end_time"];

        if ($endTime <= $startTime) {
            $message = "End time must be later than start time.";
        } else {
            // Checks whether the new availability overlaps an existing period
            $overlap = $conn->prepare(
                "SELECT Availability_ID
                FROM Availability
                WHERE User_ID = ?
                    AND Day = ?
                    AND Start_Time < ?
                    AND End_Time > ?"
            );

            $overlap->bind_param(
                "isss",
                $userID,
                $day,
                $endTime,
                $startTime
            );

            $overlap->execute();
            $overlapResults = $overlap->get_result();
            $overlap->close();

            if ($overlapResults->num_rows > 0) {
                $message = "This availability overlaps an existing period.";
            } else {
                // Adds the availability period to the database
                $availability = $conn->prepare(
                    "INSERT INTO Availability (User_ID, Day, Start_Time, End_Time)
                    VALUES (?, ?, ?, ?)"
                );

                $availability->bind_param(
                    "isss",
                    $userID,
                    $day,
                    $startTime,
                    $endTime
                );

                $availability->execute();
                $availability->close();

                header("Location: Availability.php");
                exit();
            }
        }
    }

    // Deletes an availability period
    elseif ($_POST["action"] == "delete") {
        $availabilityID = $_POST["availability_id"];

        $availability = $conn->prepare(
            "DELETE FROM Availability
             WHERE Availability_ID = ?
                AND User_ID = ?"
        );

        $availability->bind_param(
            "ii",
            $availabilityID,
            $userID
        );

        $availability->execute();
        $availability->close();

        header("Location: Availability.php");
        exit();
    }
}

// Gets the availability periods for the logged-in user
$availability = $conn->prepare(
    "SELECT Availability_ID, Day, Start_Time, End_Time
     FROM Availability
     WHERE User_ID = ?
     ORDER BY FIELD(
        Day,
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
     ), Start_Time"
);

$availability->bind_param("i", $_SESSION["user_id"]);
$availability->execute();

$availabilityResults = $availability->get_result();
$availability->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Availability</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/availability.css">
</head>

<body>
    <!-- Displays the shared dashboard header -->
    <?php
    $title = "Availability";
    include "helpers/header.php";
    ?>

    <div class="page">

        <main class="availability">
            <h1>Add Availability</h1>
            <p>Enter and manage your weekly availability.</p>

            <?php if (!empty($message)): ?>
                <div class="message" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="Availability.php">
                <input type="hidden" name="action" value="add">
                <label for="day">Day</label>
                <select id="day" name="day" required>
                    <option value="">Select a day</option>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>

                <label for="start_time">Start Time</label>
                <input
                    type="time"
                    id="start_time"
                    name="start_time"
                    required
                >

                <label for="end_time">End Time</label>
                <input
                    type="time"
                    id="end_time"
                    name="end_time"
                    required
                >

                <button class="button-primary" type="submit">
                    Add Availability
                </button>
            </form>

            <h2>Your Availability</h2>

            <?php if ($availabilityResults->num_rows == 0): ?>
                <p>No availability has been added yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $availabilityResults->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["Day"]); ?></td>
                                <td>
                                    <?php echo date("g:i A", strtotime($row["Start_Time"])); ?>
                                </td>
                                <td>
                                    <?php echo date("g:i A", strtotime($row["End_Time"])); ?>
                                </td>
                                <td>
                                    <form method="POST" action="Availability.php">
                                        <input type="hidden" name="action" value="delete">
                                        <input
                                            type="hidden"
                                            name="availability_id"
                                            value="<?php echo $row["Availability_ID"]; ?>"
                                        >

                                        <button type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </main>

    </div>
</body>
</html>