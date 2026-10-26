<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 * 
 * This file will create the info survey page for the viewer-facing simulation
 * that allows users to enter details about themselves before entering the simulation.
 * 
 * TODO: Add instructions for the simulation
*/

// Starts a PHP session
session_start();

// Connects to database
require_once "helpers/config.php";

// Redirects viewer if they did not enter through a valid session
if (!isset($_SESSION["session_id"])) {
    header("Location: SimulationLanding.php");
    exit();
}

$message = "";

// Processes the viewer's information when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST["first_name"]);
    $lastName = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $jobTitle = trim($_POST["job_title"]);
    $industryID = $_POST["industry_id"];
    $language = $_POST["language"];

    // Finds the published version matching the selected industry and language
    $versions = $conn->prepare(
        "SELECT Version.Version_ID
        FROM Version
        JOIN Simulation
            ON Version.Simulation_ID = Simulation.Simulation_ID
        JOIN Simulation_to_Session
            ON Simulation.Simulation_ID = Simulation_to_Session.Simulation_ID
        WHERE Simulation_to_Session.Session_ID = ?
            AND Simulation.Industry_ID = ?
            AND Version.Version_Language = ?
            AND Version.Is_Published = 'Published'"
    );

    $versions->bind_param(
        "iis",
        $_SESSION["session_id"],
        $industryID,
        $language
    );

    $versions->execute();

    $versionResults = $versions->get_result();

    if ($versionResults->num_rows == 0) {
        $message = "Unable to find a matching simulation.";
    } else {
        $version = $versionResults->fetch_assoc();
        // Stores the version ID for use in SimulationPage.php
        $_SESSION["version_id"] = $version["Version_ID"];

        // Saves the viewer's information to database
        $analytics = $conn->prepare(
            "INSERT INTO Analytics
                (Session_ID, First_Name, Last_Name, Email, Job_Title, Industry_ID)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $analytics->bind_param(
            "issssi",
            $_SESSION["session_id"],
            $firstName,
            $lastName,
            $email,
            $jobTitle,
            $industryID
        );

        $analytics->execute();
        
        header("Location: SimulationPage.php");
        exit();
    }
}

// Gets the industries available for simulations allowed in this session
$industries = $conn->prepare(
    "SELECT DISTINCT Industry.Industry_ID, Industry.Industry_Name
     FROM Industry
     JOIN Simulation
        ON Industry.Industry_ID = Simulation.Industry_ID
     JOIN Simulation_to_Session
        ON Simulation.Simulation_ID = Simulation_to_Session.Simulation_ID
     WHERE Simulation_to_Session.Session_ID = ?
     ORDER BY Industry.Industry_Name"
);

$industries->bind_param("i", $_SESSION["session_id"]);
$industries->execute();

$industryResults = $industries->get_result();

// Gets the published languages available for simulations allowed in this session
$languages = $conn->prepare(
    "SELECT DISTINCT Simulation.Industry_ID, Version.Version_Language
     FROM Version
     JOIN Simulation
        ON Version.Simulation_ID = Simulation.Simulation_ID
     JOIN Simulation_to_Session
        ON Simulation.Simulation_ID = Simulation_to_Session.Simulation_ID
     WHERE Simulation_to_Session.Session_ID = ?
        AND Version.Is_Published = 'Published'
     ORDER BY Version.Version_Language"
);

$languages->bind_param("i", $_SESSION["session_id"]);
$languages->execute();

$languageResults = $languages->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viewer Information</title>
    <link rel="stylesheet" href="styles/main.css">
</head>

<body>
    <div class="page">
        <div class="form">
            <!-- TODO: Replace with simulation instructions provided by client -->
            <h1>Simulation Instructions</h1>
            
            <p>
                Please enter your information below and select the appropriate
                industry and language to continue to the simulation.
            </p>

            <?php if (!empty($message)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="InfoSurvey.php">

                <label for="first-name">First Name</label>
                <input
                    type="text"
                    id="first-name"
                    name="first_name"
                    required
                >

                <label for="last-name">Last Name</label>
                <input
                    type="text"
                    id="last-name"
                    name="last_name"
                    required
                >

                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >

                <label for="industry">Industry</label>
                <select id="industry" name="industry_id" required>
                    <option value="">Select an industry</option>

                    <?php while ($industry = $industryResults->fetch_assoc()): ?>
                        <option value="<?php echo $industry["Industry_ID"]; ?>">
                            <?php echo htmlspecialchars($industry["Industry_Name"]); ?>
                        </option>
                    <?php endwhile; ?>

                </select>

                <label for="job-title">Job Title</label>
                <input
                    type="text"
                    id="job-title"
                    name="job_title"
                    required
                >

                <label for="language">Language</label>
                <select id="language" name="language" required>
                    <option value="">Select a language</option>

                    <?php while ($language = $languageResults->fetch_assoc()): ?>
                        <option
                            value="<?php echo htmlspecialchars($language["Version_Language"]); ?>"
                            data-industry="<?php echo $language["Industry_ID"]; ?>"
                            hidden
                        >
                            <?php echo htmlspecialchars($language["Version_Language"]); ?>
                        </option>
                    <?php endwhile; ?>

                </select>

                <button class="button-primary" type="submit">
                    Continue
                </button>

            </form>
        </div>
    </div>

    <script>
        const industrySelect = document.getElementById("industry");
        const languageSelect = document.getElementById("language");

        // Filters the language options based on the selected industry
        industrySelect.addEventListener("change", function() {
            const selectedIndustry = industrySelect.value;

            // Resets the language when the industry changes
            languageSelect.value = "";

            for (const option of languageSelect.options) {
                if (option.value === "") {
                    continue;
                }

                if (option.dataset.industry === selectedIndustry) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                }
            }
        });
    </script>
</body>
</html>