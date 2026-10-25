<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 * 
 * This file will create the landing page for the viewer-facing simulation
 * that allows users to enter a passcode to access the simulation.
*/

// Connects to database
require_once "helpers/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $passcode = trim($_POST["passcode"]);

    $stmt = $conn->prepare(
        "SELECT Session_ID, Start_Date_Time, End_Date_Time
         FROM Sessions
         WHERE Passcode = ?"
    );

    $stmt->bind_param("s", $passcode);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
    $message = "Invalid passcode.";
    } else {
        $session = $result->fetch_assoc();

        $currentTime = new DateTime();
        $startTime = new DateTime($session["Start_Date_Time"]);
        $endTime = new DateTime($session["End_Date_Time"]);

        if ($currentTime < $startTime) {
            $message = "This session is not active yet.";
        } elseif ($currentTime > $endTime) {
            $message = "This session is no longer active.";
        } else {
            $message = "Session is active!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation</title>
    <link rel="stylesheet" href="styles/main.css">
</head>

<body>
    <div class="page">
        <div class="form">
            <h1>Simulation</h1>

            <?php if (!empty($message)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="SimulationLanding.php">
                <label for="passcode">Session Passcode</label>
                <input
                    type="text"
                    id="passcode"
                    name="passcode"
                    required
                    placeholder="Enter your session passcode"
                >

                <button class="button-primary" type="submit">
                    Continue
                </button>
            </form>
        </div>
    </div>
</body>
</html>