<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Get post ID from request
$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if ($postId <= 0) {
    die(json_encode(["status" => "error", "message" => "Invalid post ID"]));
}

// Fetch comments for the post
$sql = "SELECT c.*, u.username, u.profile_pic, u.id as user_id 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        'id' => $row['id'],
        'content' => $row['content'],
        'created_at' => $row['created_at'],
        'username' => $row['username'],
        'profile_pic' => $row['profile_pic'],
        'user_id' => $row['user_id']
    ];
}

$stmt->close();
$conn->close();

// Return comments as JSON
header('Content-Type: application/json');
echo json_encode($comments);
?> 