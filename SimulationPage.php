<?php // Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
    // Ensure php session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Ensures the user visited the info survey page first
    if (!isset($_SESSION['version_id']) || !isset($_SESSION['session_id'])) {
        header("Location: SimulationLanding.php");
        exit();
    } 

    // Connects to database
    require "helpers/config.php";

    // Ensures the session is open
    $query = "SELECT Start_Date_Time, End_Date_Time
         FROM Sessions JOIN version
         WHERE session_id = ".$_SESSION['session_id']
         ." AND is_published = 'Published'";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

    $currentTime = new DateTime();
    $startTime = new DateTime($result["Start_Date_Time"]);
    $endTime = new DateTime($result["End_Date_Time"]);

    if ($currentTime < $startTime) {
        $message = "This access code is not active yet.";
        echo "<a href='SimulationLanding.php'>Exit</a>";
        die();
    } elseif ($currentTime > $endTime) {
        $message = "This access code is no longer active.";
        echo "<a href='SimulationLanding.php'>Exit</a>";
        die();
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Simulation</title>
        <link rel="stylesheet" href="styles/main.css">
    </head>
    <body>
    <div class="page-container">
<?php

    // Variable Declaration-----------------------------------------------------------------------
    $version_id;
    $page_id;

    // A choice was clicked
    if (isset($_GET['page_id'])) {
        $version_id = $_SESSION['version_id'];
        $page_id = $_GET['page_id'];
    }
    // First page
    else {
        $version_id = $_SESSION['version_id'];
        $query = "SELECT page_id FROM pages WHERE page_type = 'Start' AND version_id =".$version_id;
        try {
            $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $page_id = $result['page_id'];
        }
        catch (Exception $e) {
            echo "<p class='fail'>Failed to find simulation</p>";
        }
    }

    // Prints the page content-----------------------------------------------------------------------------------

    // Queries the database for the page content that matches the current page
    $query = "SELECT page_content FROM page_content WHERE page_id = ".$page_id;
    $result = mysqli_query($conn, $query);
    // Queries the database, fetches the retrived table, stores the prompt value from the table
    while($text = mysqli_fetch_assoc($result)) {
        echo ("<div class='card'><p>".$text['page_content']."</p></div>");
    }

    // Prints the choices----------------------------------------------------------------------------------

    // Queries the database for the choices on the current page
    $query = "SELECT choice_text, next_page_id FROM choice WHERE page_id = ".$page_id;
    // Queries the database, and stores the result
    $result = mysqli_query($conn, $query);

    
    echo "<ul class='choice'>"; // Starts an unordered list
    // Goes through each row of the returned table
    while ($choice = mysqli_fetch_assoc($result)) {
        // Creates a link to lead to the next page by sending the page id for the
        // next page
        $a = $choice['next_page_id'];
        // If there is no further prompt the action leads to
        if ($a == $page_id) {
            $href = "";
        }
        else {
            $href = "SimulationPage.php?page_id=".$a;
        }
        
        echo "<li><a class='button-primary' href='".$href."'>".$choice['choice_text']."</a></li>";
    }
    echo "</ul>"; // ends the list


    // If this prompt is an end, the page will display "The End"
    $query = "SELECT page_type FROM pages WHERE page_id = ".$page_id;
    $result =  mysqli_fetch_assoc(mysqli_query($conn, $query));
    if ($result['page_type'] == 'End') {
        echo "<p class='card' id='end'>The End</p>";
    }
?>
    </div>

    </body>
</html>