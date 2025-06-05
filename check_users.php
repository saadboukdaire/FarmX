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

// Query to get all farmers
$sql = "SELECT id, username, user_type, user_tag FROM users WHERE user_type = 'farmer'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Farmers found:\n";
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"] . " - Username: " . $row["username"] . 
             " - Type: " . $row["user_type"] . " - Tag: " . $row["user_tag"] . "\n";
    }
} else {
    echo "No farmers found";
}

$conn->close();
?> 