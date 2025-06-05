<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$userId = $_SESSION['user_id'];
$postId = json_decode(file_get_contents('php://input'), true)['post_id'];

// Check if the user has already liked the post
$sql = "SELECT id FROM likes WHERE user_id = ? AND post_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $postId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Unlike the post
    $sql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $postId);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Post unliked."]);
} else {
    // Like the post
    $sql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $postId);
    $stmt->execute();
    echo json_encode(["status" => "success", "message" => "Post liked."]);
}

$stmt->close();
$conn->close();
?>