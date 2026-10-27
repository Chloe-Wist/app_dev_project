<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 * 
 * This file will create the landing page for the viewer-facing simulation
 * that allows users to enter an access code to access the simulation.
 * 
 * TODO: Add external access request form URL
*/

// Starts a PHP session
session_start();

// Connects to database
require_once "helpers/config.php";

$message = "";

// Checks the access code when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $passcode = trim($_POST["passcode"]);

    $sessions = $conn->prepare(
        "SELECT Session_ID, Start_Date_Time, End_Date_Time
         FROM Sessions
         WHERE Passcode = ?"
    );

    $sessions->bind_param("s", $passcode);
    $sessions->execute();

    $sessionResults = $sessions->get_result();

    if ($sessionResults->num_rows == 0) {
        $message = "Invalid access code.";
    } else {
        $session = $sessionResults->fetch_assoc();

        $currentTime = new DateTime();
        $startTime = new DateTime($session["Start_Date_Time"]);
        $endTime = new DateTime($session["End_Date_Time"]);

        if ($currentTime < $startTime) {
            $message = "This access code is not active yet.";
        } elseif ($currentTime > $endTime) {
            $message = "This access code is no longer active.";
        } else {
            // Stores the valid session for use on following simulation pages
            $_SESSION["session_id"] = $session["Session_ID"];

            header("Location: InfoSurvey.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIL Simulation</title>
    <link rel="stylesheet" href="styles/main.css">
</head>

<body>
    <div class="page">
        <div class="form">
            <h1>Hope Inspire Love Simulation</h1>

            <?php if (!empty($message)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="SimulationLanding.php">
                <label for="passcode">Access Code</label>
                <input
                    type="text"
                    id="passcode"
                    name="passcode"
                    required
                    placeholder="Enter your access code"
                >
                
                <!-- TODO: Replace with external access request form URL -->
                <a href="#" class="request-access">Request Access</a>

                <button class="button-primary" type="submit">
                    Continue
                </button>
            </form>
        </div>
    </div>
</body>
</html>