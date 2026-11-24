<?php
// Chloe Wist, Ethan Janssen, Jonathan Smith, Hazel Lim
// 10/9/26
// Connects to the database and configures the application timezone

    // Sets the application timezone to Eastern Time
    // Automatically accounts for daylight saving time
    date_default_timezone_set("America/New_York");

    $servername = 'localhost';
    $dbuser = "root";
    $dbpassword = "";
    $dbname = "erd";

    $conn = mysqli_connect($servername, $dbuser, $dbpassword, $dbname);

    // Tests connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Synchronizes the database connection's timezone with PHP
    $timezoneOffset = date("P");
    $conn->query("SET time_zone = '$timezoneOffset'");
?>
