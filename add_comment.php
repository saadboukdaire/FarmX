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

// Handle adding a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        die(json_encode(["status" => "error", "message" => "User not logged in."]));
    }

    // Get the JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        die(json_encode(["status" => "error", "message" => "Invalid JSON input."]));
    }

    if (!isset($data['post_id']) || !isset($data['content'])) {
        die(json_encode(["status" => "error", "message" => "Post ID and content are required."]));
    }

    $postId = $data['post_id'];
    $content = $data['content'];
    $userId = $_SESSION['user_id'];

    // Insert the comment into the database
    $sql = "INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die(json_encode(["status" => "error", "message" => "Failed to prepare SQL statement: " . $conn->error]));
    }

    $stmt->bind_param("iis", $userId, $postId, $content);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Comment added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error adding comment: " . $stmt->error]);
    }

    $stmt->close();
}

// Handle fetching comments
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Ensure the post_id is provided
    if (!isset($_GET['post_id'])) {
        die(json_encode(["status" => "error", "message" => "Post ID is required."]));
    }

    $postId = $_GET['post_id'];

    // Fetch comments for the post
    $sql = "
        SELECT c.*, u.username, u.profile_pic 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die(json_encode(["status" => "error", "message" => "Failed to prepare SQL statement: " . $conn->error]));
    }

    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
    }

    echo json_encode($comments);

    $stmt->close();
}

$conn->close();
?>