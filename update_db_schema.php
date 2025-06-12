<?php

$servername = "localhost";
$username = "root";
$password = ""; // Assuming no password for root in Laragon
$dbname = "farmx";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "ALTER TABLE users MODIFY user_type ENUM('farmer', 'consommateur') NOT NULL DEFAULT 'consommateur';";

if ($conn->query($sql) === TRUE) {
    echo "Table users modified successfully: user_type column updated to ENUM('farmer', 'consommateur')\n";
} else {
    echo "Error modifying table: " . $conn->error . "\n";
}

$conn->close();

?> 