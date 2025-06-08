<?php
session_start();
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$userId, $postId, $content])) {
            // Get the post owner's ID
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $postOwnerId = $stmt->fetchColumn();

            // Create notification for the post owner if they're not the one who commented
            if ($postOwnerId != $userId) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id, content) VALUES (?, ?, 'comment', ?, 'commented on your post')");
                $stmt->execute([$postOwnerId, $userId, $postId]);
            }

            echo json_encode(["status" => "success", "message" => "Comment added successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error adding comment."]);
        }
    }
    // Handle fetching comments
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Ensure the post_id is provided
        if (!isset($_GET['post_id'])) {
            die(json_encode(["status" => "error", "message" => "Post ID is required."]));
        }

        $postId = $_GET['post_id'];

        // Fetch comments for the post
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.profile_pic 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");

        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($comments);
    }

} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]));
}
?>