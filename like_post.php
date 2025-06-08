<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = $_SESSION['user_id'];
    $postId = json_decode(file_get_contents('php://input'), true)['post_id'];

    // Check if the user has already liked the post
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$userId, $postId]);
    
    if ($stmt->rowCount() > 0) {
        // Unlike the post
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        echo json_encode(["status" => "success", "message" => "Post unliked."]);
    } else {
        // Like the post
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$userId, $postId]);

        // Get the post owner's ID
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        $postOwnerId = $stmt->fetchColumn();

        // Create notification for the post owner if they're not the one who liked it
        if ($postOwnerId != $userId) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, post_id, content) VALUES (?, ?, 'like', ?, 'liked your post')");
            $stmt->execute([$postOwnerId, $userId, $postId]);
        }

        echo json_encode(["status" => "success", "message" => "Post liked."]);
    }

} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]));
}
?>