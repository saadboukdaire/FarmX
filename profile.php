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
$userId = $_SESSION['user_id']; // Use 'user_id' instead of 'id'
$sql = "SELECT username, email, phone, profile_pic, bio FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $profilePic, $bio);
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
$stmt->bind_param("i", $userId);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle post deletion if requested
if (isset($_POST['delete_post'])) {
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
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    
    // Refresh the page
    header("Location: profile.php");
    exit();
}

// Fetch user's posts (your existing query)
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Profile</title>
    <link rel="icon" href="Images/logo.jpg">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        }

        /* Header (unchanged) */
        header {
            background-color: #3e8e41;
            color: white;
            padding: 8px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .search-bar {
            flex-grow: 1;
            margin: 0 20px;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-bar input {
            width: 100%;
            padding: 8px 15px 8px 35px;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
        }

        .nav-links {
            display: flex;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            padding: 10px 15px;
            display: inline-block;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-links a:hover {
            color: #3e8e41;
            background-color: white;
        }

        .nav-links a.activated {
            color: #3e8e41 !important;
            background-color: white !important;
        }

        /* Simplified Profile Container */
        .profile-container {
            max-width: 800px;
            margin: 0px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Profile Header */
        .profile-header {
            text-align: center;
            margin-bottom: 30px; /* Added space below the profile header */
        }

        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #3e8e41;
            margin-bottom: 15px;
        }

        .profile-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #333;
        }

        .profile-header p {
            font-size: 16px;
            color: #666;
        }

        /* Profile Info Section */
        .profile-section {
            margin-top: 70px;
            margin-bottom: 200px; /* Added space below the profile info */
        }

        .profile-section h2 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #3e8e41;
            border-bottom: 2px solid #3e8e41;
            padding-bottom: 5px;
        }

        .profile-section .info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .profile-section .info i {
            font-size: 20px;
            color: #3e8e41;
            margin-right: 10px;
        }

        .profile-section .info span {
            font-size: 16px;
            color: #333;
        }

        /* Buttons Container */
        .buttons-container {
            display: flex;
            justify-content: center;
            align-items: center; /* Align buttons vertically */
            gap: 20px; /* Space between buttons */
            margin-top: 20px; /* Space above the buttons */
        }

       /* Button Styles with Animations */
.edit-profile-button button, .logout-button a {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    transition: background-color 0.3s ease, transform 0.2s ease;
    display: inline-flex; /* Ensure consistent alignment */
    align-items: center; /* Center text vertically */
    justify-content: center; /* Center text horizontally */
    height: 40px; /* Set a fixed height for both buttons */
}

.edit-profile-button button {
    background-color: #3e8e41;
    color: white;
}

.edit-profile-button button:hover {
    background-color: #2d682f;
    transform: translateY(-2px); /* Add hover animation */
}

.logout-button a {
    background-color: #ff4d4d;
    color: white;
}

.logout-button a:hover {
    background-color: #cc0000;
    transform: translateY(-2px); /* Add hover animation */
}

        /* Bio Section */
        .bio-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #3e8e41;
        }

        .bio-section h2 {
            font-size: 18px;
            color: #3e8e41;
            margin-bottom: 10px;
        }

        .bio-section p {
            font-size: 16px;
            color: #666;
            line-height: 1.5;
        }

        /* Posts Section */
        .posts-section {
            margin-top: 30px;
        }

        .posts-section h2 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #3e8e41;
            border-bottom: 2px solid #3e8e41;
            padding-bottom: 5px;
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
            padding: 15px;
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
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="Images/logoinv.png" height="60px" title="Cultivez l'avenir, récoltez le succès">    
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search FarmX...">
                <i class='bx bx-search-alt-2'></i>
            </div>
            <div class="nav-links">
                <a href="main.php">Home</a>
                <a href="message.php">Messages</a>
                <a href="market.php">Marketplace</a>
                <a href="profile.php" class="activated">Profile</a>
            </div>
        </div>
    </header>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <!-- Add a timestamp to the image URL to force browser refresh -->
            <img id="profile-picture" src="<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Profile Picture">
            <h1 id="profile-name"><?php echo htmlspecialchars($username ?? ''); ?></h1>
        </div>

        <!-- Bio Section -->
        <div class="bio-section">
            <h2>About Me</h2>
            <p><?php echo !empty($bio) ? htmlspecialchars($bio) : 'No bio added yet.'; ?></p>
        </div>

        <!-- Profile Info Section -->
        <div class="profile-section">
            <h2>Profile Information</h2>
            <div class="info">
                <i class='bx bx-envelope'></i>
                <span id="profile-email"><?php echo htmlspecialchars($email ?? ''); ?></span>
            </div>
            <div class="info">
                <i class='bx bx-phone'></i>
                <span id="profile-phone"><?php echo htmlspecialchars($phone ?? ''); ?></span>
            </div>
        </div>

      <!-- Posts Section -->
<div class="posts-section">
    <h2>My Posts</h2>
    <?php if (empty($posts)): ?>
        <p>No posts yet.</p>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img id="profile-picture" src="<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Profile Picture" class="profile-pic">
                        <div class="post-info">
                            <h3><?php echo htmlspecialchars($username ?? ''); ?></h3>
                            <span class="post-date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                        <!-- Add edit and delete buttons -->
                        <div class="post-actions">
                            <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="edit-post" title="Edit Post">
                                <i class='bx bx-edit'></i>
                            </a>
                            <form method="POST" class="delete-form">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" name="delete_post" class="delete-post" title="Delete Post" onclick="return confirm('Are you sure you want to delete this post?');">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                    <?php if (!empty($post['media_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Media" class="post-media">
                    <?php endif; ?>
                    <div class="post-stats">
                        <span><i class='bx bx-heart'></i> <?php echo $post['like_count']; ?></span>
                        <span><i class='bx bx-comment'></i> <?php echo $post['comment_count']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

        <!-- Buttons Container -->
        <div class="buttons-container">
            <!-- Edit Profile Button -->
            <div class="edit-profile-button">
                <a href="edit_profile.php"><button>Edit Profile</button></a>
            </div>

            <!-- Logout Button -->
            <div class="logout-button">
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>