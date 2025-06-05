<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

$userId = $_SESSION['user_id'];

// Fetch posts with likes, comments, and whether the current user has liked each post
$sql = "
    SELECT 
        p.*, 
        COUNT(DISTINCT l.id) AS likes, 
        COUNT(DISTINCT c.id) AS comments,
        EXISTS(SELECT 1 FROM likes WHERE user_id = ? AND post_id = p.id) AS is_liked
    FROM posts p
    LEFT JOIN likes l ON p.id = l.post_id
    LEFT JOIN comments c ON p.id = c.post_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); // Bind the current user's ID
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

echo json_encode($posts);

$stmt->close();
$conn->close();
?>