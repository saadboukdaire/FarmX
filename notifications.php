<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mark all notifications as read
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Fetch notifications
    $stmt = $pdo->prepare("
        SELECT n.*, 
               u.username as sender_username,
               u.profile_pic as sender_picture,
               p.content as post_content,
               p.media_url as post_media,
               m.content as message_content
        FROM notifications n
        LEFT JOIN users u ON n.sender_id = u.id
        LEFT JOIN posts p ON n.post_id = p.id
        LEFT JOIN messages m ON n.message_id = m.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Notifications</title>
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
            overflow-y: scroll;
            margin-right: calc(100vw - 100%);
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
            align-items: center;
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

        .notification-container {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4d4d;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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

        .notifications-header h2 {
            color: #3e8e41;
            font-size: 24px;
        }

        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .notification-card:hover {
            transform: translateY(-2px);
        }

        .notification-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .notification-content {
            flex: 1;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .notification-sender {
            font-weight: bold;
            color: #3e8e41;
        }

        .notification-time {
            color: #666;
            font-size: 0.9em;
        }

        .notification-text {
            color: #333;
            margin-bottom: 5px;
        }

        .notification-preview {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
            padding: 5px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .no-notifications {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .no-notifications i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        .no-notifications p {
            color: #666;
            font-size: 1.1em;
        }

        @media (max-width: 600px) {
            .container {
                padding: 0 10px;
            }

            .notification-card {
                padding: 10px;
            }

            .notification-avatar {
                width: 40px;
                height: 40px;
            }
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

    <div class="container">
        <div class="notifications-header">
            <h2>Notifications</h2>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <i class='bx bx-bell'></i>
                <p>No notifications yet</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card" onclick="handleNotificationClick(<?php echo htmlspecialchars(json_encode($notification)); ?>)">
                    <img src="<?php echo $notification['sender_picture'] ?: 'Images/profile.jpg'; ?>" 
                         alt="Profile" 
                         class="notification-avatar">
                    <div class="notification-content">
                        <div class="notification-header">
                            <span class="notification-sender"><?php echo htmlspecialchars($notification['sender_username']); ?></span>
                            <span class="notification-time"><?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?></span>
                        </div>
                        <div class="notification-text">
                            <?php
                            switch ($notification['type']) {
                                case 'like':
                                    echo 'liked your post';
                                    break;
                                case 'comment':
                                    echo 'commented on your post';
                                    break;
                                case 'message':
                                    echo 'sent you a message';
                                    break;
                            }
                            ?>
                        </div>
                        <?php if ($notification['type'] === 'message' && $notification['message_content']): ?>
                            <div class="notification-preview">
                                <?php echo htmlspecialchars(substr($notification['message_content'], 0, 100)) . (strlen($notification['message_content']) > 100 ? '...' : ''); ?>
                            </div>
                        <?php elseif ($notification['type'] !== 'message' && $notification['post_content']): ?>
                            <div class="notification-preview">
                                <?php echo htmlspecialchars(substr($notification['post_content'], 0, 100)) . (strlen($notification['post_content']) > 100 ? '...' : ''); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function handleNotificationClick(notification) {
            if (notification.type === 'message') {
                window.location.href = `message.php?user=${notification.sender_id}`;
            } else if (notification.type === 'like' || notification.type === 'comment') {
                window.location.href = `view_post.php?id=${notification.post_id}`;
            }
        }

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