<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the very top
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root"; // Using root as default
$password = ""; // Empty password for local development
$dbname = "farmx";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Fetch user data
$profileId = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id']; // Get profile ID from URL or use logged-in user's ID
$isOwnProfile = $profileId == $_SESSION['user_id']; // Check if viewing own profile

$sql = "SELECT username, email, phone, profile_pic, bio, user_type, user_tag, gender, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profileId);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $profilePic, $bio, $userType, $userTag, $gender, $createdAt);
$stmt->fetch();
$stmt->close();

// Fetch user's posts
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profileId);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Only allow post deletion if it's the user's own profile
if ($isOwnProfile && isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'];
    
    // First, delete associated likes and comments
    $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    
    // Then delete the post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postId, $profileId);
    $stmt->execute();
    
    // Refresh the page
    header("Location: profile.php" . ($isOwnProfile ? "" : "?id=" . $profileId));
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Profil</title>
    <link rel="icon" href="Images/logo.jpg">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            overflow-y: scroll; /* Always show vertical scrollbar */
            margin-right: calc(100vw - 100%); /* Compensate for scrollbar width */
        }

        /* Header */
        header {
            background-color: #3e8e41;
            color: white;
            padding: 4px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            box-sizing: border-box;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            width: 100%;
        }

        .logo {
            margin-left: -140px;
        }

        .logo img {
            height: 65px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
        }

        .nav-links a i {
            font-size: 24px;
        }

        .nav-links a:hover {
            color: #3e8e41;
            background-color: white;
            transform: translateY(-2px);
        }

        .nav-links a.activated {
            color: #3e8e41;
            background-color: white;
        }

        .right-nav {
            display: flex;
            gap: 12px;
            margin-right: -70px;
            position: absolute;
            right: 0;
        }

        .right-nav a {
            color: white;
            text-decoration: none;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .right-nav a i {
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .right-nav a:hover {
            color: #3e8e41;
            background-color: white;
            transform: translateY(-2px);
        }

        .notification-container {
            position: relative;
            margin-left: 20px;
        }

        .notification-icon {
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .notification-icon:hover {
            color: #3e8e41;
            background-color: white;
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            display: none;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        /* Simplified Profile Container */
        .profile-container {
            max-width: 800px;
            margin: 0px auto;
            padding: 20px 0; /* Changed from 20px to 20px 0 to remove horizontal padding */
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Profile Header */
        .profile-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative; /* Add this for absolute positioning of edit button */
        }

        .edit-profile-button {
            position: absolute;
            top: 0;
            right: 50px; /* Adjusted to move further left */
        }

        .edit-profile-button button {
            background-color: #3e8e41;
            color: white;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .edit-profile-button button:hover {
            background-color: #2d682f;
            transform: scale(1.1);
        }

        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #3e8e41;
            margin-bottom: 20px;
        }

        .profile-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .profile-header .user-tag {
            font-size: 16px;
            color: #3e8e41;
            font-weight: 500;
            margin-top: 8px;
        }

        .profile-header p {
            font-size: 16px;
            color: #666;
        }

        /* Bio Section */
        .bio-section {
            background-color: white;
            padding: 15px 20px; /* Added horizontal padding to match original inner content indentation */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            margin-top: 25px;
        }

        .bio-section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #3e8e41;
            padding-bottom: 8px;
            position: relative; /* Re-added for pseudo-element positioning */
        }

        .bio-section h2::after {
            content: '';
            position: absolute;
            left: -20px; /* Adjusted to span full container width */
            right: -20px; /* Adjusted to span full container width */
            bottom: 0;
            height: 2px;
            background-color: #3e8e41;
        }

        .bio-section p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }

        /* Profile Info Section */
        .profile-section {
            background-color: white;
            padding: 20px; /* This section is already padded, but let's ensure it's consistent if we remove container padding */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-top: 25px;
            margin-bottom: 25px;
            width: 100%;
            box-sizing: border-box;
        }

        .profile-section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #3e8e41;
            padding-bottom: 8px;
            position: relative; /* Re-added for pseudo-element positioning */
        }

        .profile-section h2::after {
            content: '';
            position: absolute;
            left: -20px; /* Adjusted to span full container width */
            right: -20px; /* Adjusted to span full container width */
            bottom: 0;
            height: 2px;
            background-color: #3e8e41;
        }

        .profile-section .info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px; /* Changed from 12px to 15px to match .post */
            background-color: white; /* Changed from #f9f9f9 to white to match .post */
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Added box-shadow to match .post */
            transition: all 0.3s ease;
        }

        .profile-section .info:hover {
            /* Removed background-color and transform to match other sections */
            /* background-color: #f5f5f5; */
            /* transform: translateX(5px); */
        }

        .profile-section .info i {
            font-size: 20px;
            color: #3e8e41;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }

        .profile-section .info span {
            font-size: 15px;
            color: #333;
        }

        .profile-section .info .user-type {
            color: #3e8e41;
            font-weight: 500;
        }

        .profile-section .info .join-date {
            color: #666;
        }

        /* Posts Section */
        .posts-section {
            background-color: white; /* Added background to match other sections */
            padding: 20px; /* Added padding to match .profile-section */
            border-radius: 8px; /* Added border-radius */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Added box-shadow */
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .posts-section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #3e8e41;
            padding-bottom: 8px;
            position: relative; /* Re-added for pseudo-element positioning */
        }

        .posts-section h2::after {
            content: '';
            position: absolute;
            left: -20px; /* Adjusted to span full container width */
            right: -20px; /* Adjusted to span full container width */
            bottom: 0;
            height: 2px;
            background-color: #3e8e41;
        }

       .posts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* Changed from 3 to 2 columns */
    gap: 15px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .posts-grid {
        grid-template-columns: 1fr; /* On smaller screens, show 1 column */
    }
}

/* Remove the 480px media query since we're already handling small screens above */

        @media (max-width: 480px) {
            .posts-grid {
                grid-template-columns: 1fr;
            }
        }

        .post {
            background-color: white;
            border-radius: 8px;
            padding: 15px 20px; /* Changed from 15px to 15px 20px */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .post-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .post-header .post-info {
            flex-grow: 1;
        }

        .post-header .post-info h3 {
            font-size: 16px;
            margin: 0;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .post-header .post-info .post-date {
            font-size: 12px;
            color: #666;
        }

        .post-content {
            margin: 10px 0;
            font-size: 16px;
            color: #333;
            line-height: 1.5;
            flex-grow: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
             max-height: 4.5em;
        }

        .post-media {
            max-width: 100%;
            border-radius: 8px;
            margin: 10px 0;
            max-height: 200px;
            object-fit: cover;
        }

        .post-stats {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 14px;
            margin-top: auto;
        }

        .post-stats i {
            margin-right: 5px;
        }
        /* Add to your existing CSS */
.post-actions {
    display: flex;
    gap: 10px;
    margin-left: auto; /* Push buttons to the right */
}

.edit-post, .delete-post {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    padding: 5px;
    transition: all 0.3s ease;
}

.edit-post {
    color: #3e8e41;
}

.delete-post {
    color: #ff4d4d;
}

.edit-post:hover {
    color: #2d682f;
    transform: scale(1.1);
}

.delete-post:hover {
    color: #cc0000;
    transform: scale(1.1);
}

.delete-form {
    display: inline;
}

/* New styles for buttons in post footer */
.post-footer-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.view-in-feed-btn, .delete-post-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 20px;
    padding: 5px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}

.view-in-feed-btn {
    color: #3e8e41;
}

.delete-post-btn {
    color: #ff4d4d;
}

.view-in-feed-btn:hover {
    color: #2d682f;
    transform: scale(1.1);
}

.delete-post-btn:hover {
    color: #cc0000;
    transform: scale(1.1);
}

        /* Add these styles to your existing CSS */
        .user-tag {
            font-size: 16px;
            color: #3e8e41;
            font-weight: 500;
            margin: 5px 0;
        }

        .join-date {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .profile-header p {
            margin: 3px 0;
        }

        /* Add this to your existing styles */
        .edit-profile-btn, .view-in-feed-btn {
            text-decoration: none;
            color: inherit;
        }

        .edit-profile-btn:hover, .view-in-feed-btn:hover {
            text-decoration: none;
        }

        /* Update the icon styles */
        .edit-profile-btn i {
            font-size: 32px;
            color: #3e8e41;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .view-in-feed-btn i, .delete-post-btn i {
            font-size: 28px;  /* Same size for both view post and trash icons */
            color: #3e8e41;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .delete-post-btn i {
            color: #ff4d4d;
        }

        .edit-profile-button {
            margin-top: 10px;
            background-color: rgba(62, 142, 65, 0.1);
            padding: 10px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .edit-profile-button:hover {
            background-color: rgba(62, 142, 65, 0.2);
        }

        .edit-profile-btn, .view-in-feed-btn, .delete-post-btn {
            text-decoration: none;
            color: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.3s ease;
            background: none;
            border: none;
            cursor: pointer;
        }

        .edit-profile-btn:hover i, .view-in-feed-btn:hover i {
            color: #2d682f;
            transform: scale(1.1);
        }

        .delete-post-btn:hover {
            color: #cc0000;
            transform: scale(1.1);
        }

        .edit-profile-btn:hover, .view-in-feed-btn:hover, .delete-post-btn:hover {
            text-decoration: none;
            background-color: rgba(62, 142, 65, 0.1);
        }

        .delete-post-btn:hover {
            background-color: rgba(255, 77, 77, 0.1);
        }

        /* Custom Alert Styles (copied from edit_profile.php) */
        .custom-alert {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8); /* Keeping this as per attached file */
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert-content {
            background: rgba(255, 255, 255, 0.98);
            padding: 35px;
            border-radius: 25px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: scale(0.9);
            animation: scaleIn 0.3s ease forwards;
            position: relative;
            overflow: hidden;
        }

        .alert-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #ff4d4d, #cc0000);
        }

        @keyframes scaleIn {
            from { transform: scale(0.9); }
            to { transform: scale(1); }
        }

        .alert-content p {
            font-size: 16px;
            margin: 20px 0;
            color: #333;
            line-height: 1.5;
            font-weight: 500;
        }

        .alert-content button {
            padding: 14px 35px;
            background: linear-gradient(45deg, #ff4d4d, #cc0000);
            color: #fff;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 18px rgba(255, 77, 77, 0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .alert-content button:hover {
            background: linear-gradient(45deg, #cc0000, #ff4d4d);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 77, 77, 0.5);
        }

        .alert-content button:active {
            transform: translateY(0);
        }

        .alert-content i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            color: #ff4d4d;
        }

        .error-alert .alert-content i {
            color: #ff4d4d;
        }

        /* Custom Alert Modals (copied from edit_profile.php) */
        .confirm-modal, .password-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8); /* Unified background-color to match custom-alert */
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1001;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.98);
            padding: 35px;
            border-radius: 25px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: scale(0.9);
            animation: scaleIn 0.3s ease forwards;
            position: relative;
            overflow: hidden;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #ff4d4d, #cc0000);
        }

        .modal-content h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-content p {
            font-size: 16px;
            margin: 20px 0;
            color: #333;
            line-height: 1.5;
            font-weight: 500;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 14px 35px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15);
        }

        .modal-buttons .confirm-btn {
            background: linear-gradient(45deg, #FF6B6B, #FF4D4D);
            color: #fff;
            box-shadow: 0 5px 18px rgba(255, 77, 77, 0.4);
        }

        .modal-buttons .confirm-btn:hover {
            background: linear-gradient(45deg, #FF4D4D, #E60000);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 77, 77, 0.5);
        }

        .modal-buttons .cancel-btn {
            background: linear-gradient(45deg, #b0b0b0, #9e9e9e);
            color: #fff;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.1); /* Keep this as is for a neutral button, matching other general button shadows */
        }

        .modal-buttons .cancel-btn:hover {
            background: linear-gradient(45deg, #9e9e9e, #8a8a8a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        /* Overrides for custom alert to ensure it appears above modals */
        .custom-alert {
            z-index: 1002;
        }

        /* Ensure confirm-modal content and buttons match custom-alert content */
        #confirmPostDeleteModal .modal-content {
            background: rgba(255, 255, 255, 0.95); /* Match custom-alert background */
            padding: 30px; /* Match custom-alert padding */
            border-radius: 20px; /* Match custom-alert border-radius */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2); /* Match custom-alert box-shadow */
            max-width: 350px; /* Match custom-alert max-width */
        }

        #confirmPostDeleteModal .modal-content::before {
            height: 5px; /* Match custom-alert height */
        }

        #confirmPostDeleteModal .modal-content p {
            font-size: 16px; /* Match custom-alert p font-size */
            margin: 20px 0; /* Match custom-alert p margin */
            line-height: 1.5; /* Match custom-alert p line-height */
            font-weight: 500; /* Match custom-alert p font-weight */
        }

        #confirmPostDeleteModal .modal-buttons button {
            padding: 12px 30px; /* Match custom-alert button padding */
            border-radius: 25px; /* Match custom-alert button border-radius */
            font-size: 15px; /* Match custom-alert button font-size */
            font-weight: 600; /* Match custom-alert button font-weight */
            letter-spacing: 1px; /* Match custom-alert button letter-spacing */
        }

        #confirmPostDeleteModal .modal-buttons .confirm-btn {
            box-shadow: 0 4px 15px rgba(255, 77, 77, 0.3); /* Match custom-alert button box-shadow */
        }

        #confirmPostDeleteModal .modal-buttons .cancel-btn {
            background: linear-gradient(45deg, #b0b0b0, #9e9e9e); /* Ensure grey gradient */
            color: #fff; /* Ensure white text */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); /* Use a neutral shadow, consistent with general button shadows */
        }

        #confirmPostDeleteModal .modal-buttons .cancel-btn:hover {
            background: linear-gradient(45deg, #9e9e9e, #8a8a8a); /* Darker grey gradient on hover */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2); /* Neutral shadow on hover */
        }

        /* Success Message Styles */
        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .success-message i {
            font-size: 24px;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <a href="main.php">
                    <img src="Images/logoinv.png" alt="Logo FarmX">
                </a>
            </div>
            <div class="nav-links">
                <a href="message.php" title="Messages">
                    <i class='bx bxs-message-dots'></i>
                </a>
                <a href="main.php" title="Accueil">
                    <i class='bx bxs-home'></i>
                </a>
                <a href="market.php" title="Marché">
                    <i class='bx bxs-store'></i>
                </a>
            </div>
            <div class="right-nav">
                <div class="notification-container">
                    <a href="notifications.php" title="Notifications">
                        <i class='bx bx-bell notification-icon'></i>
                        <span class="notification-badge">0</span>
                    </a>
                </div>
                <a href="profile.php" class="activated" title="Profil">
                    <i class='bx bxs-user'></i>
                </a>
                <a href="logout.php" title="Déconnexion">
                    <i class='bx bx-log-out'></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Success Message -->
        <?php if (isset($_SESSION['profile_update_success'])): ?>
            <div class="success-message">
                <i class='bx bx-check-circle'></i>
                <?php 
                    echo htmlspecialchars($_SESSION['profile_update_success']);
                    unset($_SESSION['profile_update_success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <!-- Add a timestamp to the image URL to force browser refresh -->
            <img id="profile-picture" src="<?php echo $profilePic ? $profilePic . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Image de profil">
            <h1 id="profile-name">
                <i class="fas fa-seedling" style="color:#4CAF50;margin-right:8px;"></i>
                <?php echo htmlspecialchars($username ?? ''); ?>
            </h1>
            <p class="user-tag"><?php echo htmlspecialchars($userTag ?? ''); ?></p>
            <!-- Move edit profile button here -->
            <?php if ($isOwnProfile): ?>
            <div class="edit-profile-button">
                <a href="edit_profile.php" class="edit-profile-btn" title="Modifier le profil">
                    <i class='bx bxs-edit-alt'></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bio Section -->
        <div class="bio-section">
            <h2>À propos de moi</h2>
            <p><?php echo !empty($bio) ? htmlspecialchars($bio) : 'Aucune bio ajoutée pour le moment.'; ?></p>
        </div>

        <!-- Profile Info Section -->
        <div class="profile-section">
            <h2>Informations de profil</h2>
            <div class="info">
                <i class='bx bx-badge-check'></i>
                <span class="user-type"><?php echo htmlspecialchars($userTag ?? ''); ?></span>
            </div>
            <div class="info">
                <i class='bx bx-calendar'></i>
                <span class="join-date">Membre depuis le <?php echo date('F Y', strtotime($createdAt)); ?></span>
            </div>
            <div class="info">
                <i class='bx bx-envelope'></i>
                <span id="profile-email"><?php echo htmlspecialchars($email ?? ''); ?></span>
            </div>
            <div class="info">
                <i class='bx bx-phone'></i>
                <span id="profile-phone"><?php echo '0' . substr(htmlspecialchars($phone ?? ''), -8); ?></span>
            </div>
            <?php if (!empty($gender)): ?>
            <div class="info">
                <i class='bx bx-user'></i>
                <span id="profile-gender"><?php echo ucfirst(htmlspecialchars($gender)); ?></span>
            </div>
            <?php endif; ?>
        </div>

      <!-- Posts Section -->
<div class="posts-section">
    <h2>Mes publications</h2>
    <?php if (empty($posts)): ?>
        <p>Aucune publication pour le moment.</p>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?php echo $profilePic ? $profilePic . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Image de profil" class="profile-pic">
                        <div class="post-info">
                            <h3><?php echo htmlspecialchars($username ?? ''); ?></h3>
                            <span class="post-date"><?php echo date('j F Y', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                    <?php if (!empty($post['media_url'])): ?>
                        <?php
                            $media_url = htmlspecialchars($post['media_url']);
                            $extension = pathinfo($media_url, PATHINFO_EXTENSION);
                            $video_extensions = ['mp4', 'webm', 'ogg', 'mov'];

                            if (in_array(strtolower($extension), $video_extensions)) {
                                echo '<video src="' . $media_url . '" class="post-media" controls></video>';
                            } else {
                                echo '<img src="' . $media_url . '" alt="Média de publication" class="post-media">';
                            }
                        ?>
                    <?php endif; ?>
                    <div class="post-footer-actions">
                        <a href="main.php?post_id=<?php echo $post['id']; ?>" class="view-in-feed-btn" title="Voir dans le fil">
                            <i class='bx bx-show'></i>
                        </a>
                        <?php if ($isOwnProfile): ?>
                        <form method="POST" class="delete-post-form" data-post-id="<?php echo $post['id']; ?>" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="delete_post" value="1">
                            <button type="submit" class="delete-post-btn" title="Supprimer la publication">
                                <i class='bx bx-trash'></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
        </div>
    </div>

    <!-- Confirmation Modal for Post Deletion -->
    <div id="confirmPostDeleteModal" class="confirm-modal">
        <div class="modal-content">
            <h3>Confirmer la suppression</h3>
            <p>Êtes-vous sûr de vouloir supprimer cette publication? Cette action est irréversible.</p>
            <div class="modal-buttons">
                <button type="button" class="confirm-btn" id="confirm-post-delete-btn">Supprimer</button>
                <button type="button" class="cancel-btn" id="cancel-post-delete-btn">Annuler</button>
            </div>
        </div>
    </div>

    <script>
        // Function to update notification count
        function updateNotificationCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Notification count data in profile.php:', data);
                    const badge = document.querySelector('.notification-badge');
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                })
                .catch(error => console.error('Error updating notification count:', error));
        }

        // Update notification count when page loads
        document.addEventListener('DOMContentLoaded', updateNotificationCount);

        // Update notification count every 30 seconds
        setInterval(updateNotificationCount, 30000);

        // Function to apply hover style based on current page
        function applyCurrentPageStyle() {
            const currentPath = window.location.pathname;
            const profileLink = document.querySelector('a[href="profile.php"]');
            const notificationsLink = document.querySelector('a[href="notifications.php"]');
            
            if (currentPath.includes('/profile.php')) {
                profileLink.style.color = '#3e8e41';
                profileLink.style.backgroundColor = 'white';
                profileLink.style.transform = 'translateY(-2px)';
            } else if (currentPath.includes('/notifications.php')) {
                notificationsLink.style.color = '#3e8e41';
                notificationsLink.style.backgroundColor = 'white';
                notificationsLink.style.transform = 'translateY(-2px)';
            }
        }

        // Apply styles when page loads
        document.addEventListener('DOMContentLoaded', applyCurrentPageStyle);

        // Post Deletion Confirmation Logic
        const deletePostButtons = document.querySelectorAll('.delete-post-btn');
        const confirmPostDeleteModal = document.getElementById('confirmPostDeleteModal');
        const confirmPostDeleteBtn = document.getElementById('confirm-post-delete-btn');
        const cancelPostDeleteBtn = document.getElementById('cancel-post-delete-btn');
        let currentPostForm = null; // To store the form to be submitted

        deletePostButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                currentPostForm = this.closest('.delete-post-form');
                confirmPostDeleteModal.style.display = 'flex';
            });
        });

        cancelPostDeleteBtn.addEventListener('click', function() {
            confirmPostDeleteModal.style.display = 'none';
            currentPostForm = null; // Clear the stored form
        });

        confirmPostDeleteBtn.addEventListener('click', function() {
            console.log('Confirm delete button clicked.');
            if (currentPostForm) {
                console.log('Submitting form for post:', currentPostForm.dataset.postId);
                currentPostForm.submit(); // Submit the form if confirmed
            } else {
                console.log('No form to submit.');
            }
            confirmPostDeleteModal.style.display = 'none';
        });

        // Close modal if clicked outside
        confirmPostDeleteModal.addEventListener('click', function(event) {
            if (event.target === confirmPostDeleteModal) {
                console.log('Clicked outside modal, closing.');
                confirmPostDeleteModal.style.display = 'none';
                currentPostForm = null; // Clear the stored form
            }
        });
    </script>
</body>
</html>