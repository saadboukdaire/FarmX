<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the very top
session_start();

// Set response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "farmx"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $conn->connect_error]);
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $password = $_POST['password'] ?? '';

    // Fetch user's hashed password from the database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $hashedPassword)) {
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect.']);
        $conn->close();
        exit();
    }

    // Password is correct, proceed with account deletion
    // First, fetch user's profile picture and post media URLs to delete files
    $profilePicToDelete = null;
    $postMediaToDelete = [];

    $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($profilePicToDelete);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT media_url FROM posts WHERE user_id = ? AND media_url IS NOT NULL AND media_url != ''");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $postMediaToDelete[] = $row['media_url'];
    }
    $stmt->close();

    // Delete related data (likes, comments, posts) and then the user
    $conn->begin_transaction();

    try {
        // Delete user's likes
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Delete user's comments
        $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Get post IDs to delete associated likes and comments
        $postIds = [];
        $stmt = $conn->prepare("SELECT id FROM posts WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $postIds[] = $row['id'];
        }
        $stmt->close();

        if (!empty($postIds)) {
            $placeholders = implode(',', array_fill(0, count($postIds), '?'));
            $types = str_repeat('i', count($postIds));

            // Delete likes associated with user's posts
            $stmt = $conn->prepare("DELETE FROM likes WHERE post_id IN ($placeholders)");
            call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $postIds));
            $stmt->execute();
            $stmt->close();

            // Delete comments associated with user's posts
            $stmt = $conn->prepare("DELETE FROM comments WHERE post_id IN ($placeholders)");
            call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $postIds));
            $stmt->execute();
            $stmt->close();
        }

        // Delete user's posts
        $stmt = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Finally, delete the user account
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Delete profile picture file (if not default) and post media files
        if ($profilePicToDelete && $profilePicToDelete !== 'Images/profile.jpg' && file_exists($profilePicToDelete)) {
            unlink($profilePicToDelete);
        }

        foreach ($postMediaToDelete as $mediaUrl) {
            if (file_exists($mediaUrl)) {
                unlink($mediaUrl);
            }
        }

        // Destroy session and redirect
        session_unset();
        session_destroy();

        echo json_encode(['success' => true, 'message' => 'Votre compte a été supprimé avec succès.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Account deletion failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du compte: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
}

$conn->close();
?> 