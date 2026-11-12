<?php
// Chloe Wist, Ethan Janssen, Jonathan Smith, Hazel Lim
// 10/9/26
// Connects to the database

    $servername = 'localhost';
    $dbuser = "root";
    $dbpassword = "password";
    $dbname = "erd";

    $conn = mysqli_connect($servername, $dbuser, $dbpassword, $dbname);

    // Tests connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
?>
