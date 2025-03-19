<?php
$servername = "localhost";
$username = "root";
$password = "316213";
$dbname = "belaybuildingcom_xion_bms";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
