<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update user tags for farmers
$sql = "UPDATE users SET user_tag = 'FarmX Producer' WHERE user_type = 'farmer'";
if ($conn->query($sql) === TRUE) {
    echo "User tags updated successfully";
} else {
    echo "Error updating user tags: " . $conn->error;
}

$conn->close();
?> 