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

        .tooltip {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .nav-links a:hover .tooltip {
            opacity: 1;
            visibility: visible;
            bottom: -35px;
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

        .right-nav a:hover .tooltip {
            opacity: 1;
            visibility: visible;
            bottom: -35px;
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

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
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
            margin-bottom: 35px;
            position: relative; /* Add this for absolute positioning of edit button */
        }

        .edit-profile-button {
            position: absolute;
            top: 0;
            right: 0;
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

        /* Profile Info Section */
        .profile-section {
            margin-top: 40px;
            margin-bottom: 25px;
            width: 100%;
            box-sizing: border-box;
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
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f9f9f9;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .profile-section .info:hover {
            background-color: #f0f0f0;
            transform: translateX(5px);
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
            margin-top: 25px;
        }

        .posts-section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #3e8e41;
            border-bottom: 2px solid #3e8e41;
            padding-bottom: 8px;
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
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <a href="main.php">
                    <img src="Images/logoinv.png" alt="FarmX Logo">
                </a>
            </div>
            <div class="nav-links">
                <a href="message.php" title="Messages">
                    <i class='bx bxs-message-dots'></i>
                    <span class="tooltip">Messages</span>
                </a>
                <a href="main.php" title="Home">
                    <i class='bx bxs-home'></i>
                    <span class="tooltip">Home</span>
                </a>
                <a href="market.php" title="Marketplace">
                    <i class='bx bxs-store'></i>
                    <span class="tooltip">Marketplace</span>
                </a>
            </div>
            <div class="right-nav">
                <a href="notifications.php" class="notification-container" title="Notifications">
                    <i class='bx bx-bell notification-icon'></i>
                    <span class="notification-badge">0</span>
                    <span class="tooltip">Notifications</span>
                </a>
                <a href="profile.php" title="Profile">
                    <i class='bx bxs-user'></i>
                    <span class="tooltip">Profile</span>
                </a>
                <a href="logout.php" title="Logout">
                    <i class='bx bx-log-out'></i>
                    <span class="tooltip">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <!-- Add a timestamp to the image URL to force browser refresh -->
            <img id="profile-picture" src="<?php echo $profilePic ? $profilePic . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Profile Picture">
            <h1 id="profile-name"><?php echo htmlspecialchars($username ?? ''); ?></h1>
            <p class="user-tag"><?php echo htmlspecialchars($userTag ?? ''); ?></p>
            <!-- Move edit profile button here -->
            <?php if ($isOwnProfile): ?>
            <div class="edit-profile-button">
                <a href="edit_profile.php"><button title="Edit Profile"><i class='bx bx-edit'></i></button></a>
            </div>
            <?php endif; ?>
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
                <i class='bx bx-badge-check'></i>
                <span class="user-type"><?php echo htmlspecialchars($userTag ?? ''); ?></span>
            </div>
            <div class="info">
                <i class='bx bx-calendar'></i>
                <span class="join-date">Member since <?php echo date('F Y', strtotime($createdAt)); ?></span>
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
    <h2>My Posts</h2>
    <?php if (empty($posts)): ?>
        <p>No posts yet.</p>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?php echo $profilePic ? $profilePic . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Profile Picture" class="profile-pic">
                        <div class="post-info">
                            <h3><?php echo htmlspecialchars($username ?? ''); ?></h3>
                            <span class="post-date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
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
                                echo '<img src="' . $media_url . '" alt="Post Media" class="post-media">';
                            }
                        ?>
                    <?php endif; ?>
                    <div class="post-footer-actions">
                        <a href="main.php?post_id=<?php echo $post['id']; ?>" class="view-in-feed-btn" title="View in Feed">
                            <i class='bx bx-show'></i>
                        </a>
                        <?php if ($isOwnProfile): ?>
                        <form method="POST" class="delete-post-form" onsubmit="return confirm('Are you sure you want to delete this post?');">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="delete_post" class="delete-post-btn" title="Delete Post">
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

    <script>
        // Function to update notification count
        function updateNotificationCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
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
    </script>
</body>
</html>