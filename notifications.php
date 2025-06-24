<?php
session_start();
require_once 'config.php';
date_default_timezone_set('Africa/Casablanca');

function formatTimeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return 'il y a ' . $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
    }
    if ($diff->m > 0) {
        return $diff->m . ' mois';
    }
    if ($diff->d > 0) {
        return 'il y a ' . $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
    }
    if ($diff->h > 0) {
        return 'il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    }
    if ($diff->i > 0) {
        return 'il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }
    return 'à l\'instant';
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
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

    // Group notifications by time period
    $groupedNotifications = [
        'today' => [],
        'yesterday' => [],
        'this_week' => [],
        'this_month' => [],
        'older' => []
    ];

    $now = new DateTime();
    $yesterday = new DateTime('yesterday');
    $weekAgo = new DateTime('-1 week');
    $monthAgo = new DateTime('-1 month');

    foreach ($notifications as $notification) {
        $notificationDate = new DateTime($notification['created_at']);
        
        if ($notificationDate->format('Y-m-d') === $now->format('Y-m-d')) {
            $groupedNotifications['today'][] = $notification;
        } elseif ($notificationDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $groupedNotifications['yesterday'][] = $notification;
        } elseif ($notificationDate >= $weekAgo) {
            $groupedNotifications['this_week'][] = $notification;
        } elseif ($notificationDate >= $monthAgo) {
            $groupedNotifications['this_month'][] = $notification;
        } else {
            $groupedNotifications['older'][] = $notification;
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
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

        .notifications-header h2 {
            color: #3e8e41;
            font-size: 24px;
        }

        .notification-group {
            margin-bottom: 30px;
        }

        .notification-group-header {
            color: #666;
            font-size: 1.1em;
            font-weight: 500;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .notification-group:empty {
            display: none;
        }

        .notification-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid #3e8e41;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-icon.like {
            background-color: #ff6b6b;
            color: white;
        }

        .notification-icon.comment {
            background-color: #4dabf7;
            color: white;
        }

        .notification-icon.message {
            background-color: #845ef7;
            color: white;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .notification-type {
            font-weight: 600;
            color: #2d682f;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #e8f5e9;
        }

        .notification-type.like {
            background-color: #ffe3e3;
            color: #e03131;
        }

        .notification-type.comment {
            background-color: #e7f5ff;
            color: #1971c2;
        }

        .notification-type.message {
            background-color: #f3f0ff;
            color: #5f3dc4;
        }

        .notification-text {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .notification-post {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 8px;
            border-left: 3px solid #e0e0e0;
        }

        .notification-post-text {
            color: #444;
            font-size: 14px;
            line-height: 1.5;
        }

        .notification-time {
            color: #999;
            font-size: 12px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .notification-link {
            color: #3e8e41;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .notification-link:hover {
            text-decoration: underline;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 13px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .mark-read-btn:hover {
            background-color: #f0f0f0;
            color: #333;
        }

        .unread {
            background-color: #f8f9fa;
            border-left-color: #3e8e41;
        }

        .unread .notification-type {
            font-weight: 700;
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

            .notification-item {
                padding: 10px;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
            }
        }

        @keyframes highlight-post {
            0% {
                background-color: rgba(62, 142, 65, 0.1);
            }
            50% {
                background-color: rgba(62, 142, 65, 0.2);
            }
            100% {
                background-color: transparent;
            }
        }

        .highlighted-post {
            animation: highlight-post 2s ease-out;
            border-left: 4px solid #3e8e41;
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
                </a>
                <a href="main.php" title="Home">
                    <i class='bx bxs-home'></i>
                </a>
                <a href="market.php" title="Marketplace">
                    <i class='bx bxs-store'></i>
                </a>
            </div>
            <div class="right-nav">
                <div class="notification-container">
                    <a href="notifications.php" class="activated" title="Notifications">
                        <i class='bx bx-bell notification-icon'></i>
                        <span class="notification-badge"></span>
                    </a>
                </div>
                <a href="profile.php" title="Profile">
                    <i class='bx bxs-user'></i>
                </a>
                <a href="logout.php" title="Logout">
                    <i class='bx bx-log-out'></i>
                </a>
            </div>
        </div>
    </header>

    <?php if (!empty($notifications)): ?>
    <div class="container">
        <div class="notifications-header">
            <h2>Notifications</h2>
        </div>

            <?php if (!empty($groupedNotifications['today'])): ?>
                <div class="notification-group">
                    <div class="notification-group-header">Today</div>
                    <?php foreach ($groupedNotifications['today'] as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $notification['type']; ?>">
                                <?php if ($notification['type'] === 'like'): ?>
                                    <i class='bx bxs-heart'></i>
                                <?php elseif ($notification['type'] === 'comment'): ?>
                                    <i class='bx bxs-comment-detail'></i>
                                <?php else: ?>
                                    <i class='bx bxs-message-detail'></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-type <?php echo $notification['type']; ?>">
                                        <?php 
                                        switch($notification['type']) {
                                            case 'like':
                                                echo 'Like';
                                                break;
                                            case 'comment':
                                                echo 'Commentaire';
                                                break;
                                            case 'message':
                                                echo 'Message';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <span class="notification-time">
                                        <?php echo formatTimeAgo($notification['created_at']); ?>
                                    </span>
                                </div>
                                <div class="notification-text">
                                    <strong><?php echo htmlspecialchars($notification['sender_username']); ?></strong>
                                    <?php 
                                    switch($notification['type']) {
                                        case 'like':
                                            echo ' a aimé votre publication';
                                            break;
                                        case 'comment':
                                            echo ' a commenté votre publication';
                                            break;
                                        case 'message':
                                            echo ' vous a envoyé un message';
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php if ($notification['post_content']): ?>
                                    <div class="notification-post">
                                        <p class="notification-post-text"><?php echo htmlspecialchars(substr($notification['post_content'], 0, 100)) . (strlen($notification['post_content']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="notification-actions">
                                    <?php if ($notification['type'] === 'message'): ?>
                                        <a href="message.php?contact_id=<?php echo $notification['sender_id']; ?>" class="notification-link">
                                            <i class='bx bx-message-square-detail'></i>
                                            Voir le message
                                        </a>
                                    <?php else: ?>
                                        <a href="main.php?post_id=<?php echo $notification['post_id']; ?>" class="notification-link">
                                            <i class='bx bx-show'></i>
                                            Voir la publication
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <form action="notifications.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="mark-read-btn">
                                                <i class='bx bx-check'></i>
                                                Marquer comme lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($groupedNotifications['yesterday'])): ?>
                <div class="notification-group">
                    <div class="notification-group-header">Hier</div>
                    <?php foreach ($groupedNotifications['yesterday'] as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $notification['type']; ?>">
                                <?php if ($notification['type'] === 'like'): ?>
                                    <i class='bx bxs-heart'></i>
                                <?php elseif ($notification['type'] === 'comment'): ?>
                                    <i class='bx bxs-comment-detail'></i>
                                <?php else: ?>
                                    <i class='bx bxs-message-detail'></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-type <?php echo $notification['type']; ?>">
                                        <?php 
                                        switch($notification['type']) {
                                            case 'like':
                                                echo 'Like';
                                                break;
                                            case 'comment':
                                                echo 'Commentaire';
                                                break;
                                            case 'message':
                                                echo 'Message';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <span class="notification-time">
                                        <?php echo formatTimeAgo($notification['created_at']); ?>
                                    </span>
                                </div>
                                <div class="notification-text">
                                    <strong><?php echo htmlspecialchars($notification['sender_username']); ?></strong>
                                    <?php 
                                    switch($notification['type']) {
                                        case 'like':
                                            echo ' a aimé votre publication';
                                            break;
                                        case 'comment':
                                            echo ' a commenté votre publication';
                                            break;
                                        case 'message':
                                            echo ' vous a envoyé un message';
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php if ($notification['post_content']): ?>
                                    <div class="notification-post">
                                        <p class="notification-post-text"><?php echo htmlspecialchars(substr($notification['post_content'], 0, 100)) . (strlen($notification['post_content']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="notification-actions">
                                    <?php if ($notification['type'] === 'message'): ?>
                                        <a href="message.php?contact_id=<?php echo $notification['sender_id']; ?>" class="notification-link">
                                            <i class='bx bx-message-square-detail'></i>
                                            Voir le message
                                        </a>
                                    <?php else: ?>
                                        <a href="main.php?post_id=<?php echo $notification['post_id']; ?>" class="notification-link">
                                            <i class='bx bx-show'></i>
                                            Voir la publication
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <form action="notifications.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="mark-read-btn">
                                                <i class='bx bx-check'></i>
                                                Marquer comme lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($groupedNotifications['this_week'])): ?>
                <div class="notification-group">
                    <div class="notification-group-header">Cette semaine</div>
                    <?php foreach ($groupedNotifications['this_week'] as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $notification['type']; ?>">
                                <?php if ($notification['type'] === 'like'): ?>
                                    <i class='bx bxs-heart'></i>
                                <?php elseif ($notification['type'] === 'comment'): ?>
                                    <i class='bx bxs-comment-detail'></i>
                                <?php else: ?>
                                    <i class='bx bxs-message-detail'></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-type <?php echo $notification['type']; ?>">
                                        <?php 
                                        switch($notification['type']) {
                                            case 'like':
                                                echo 'Like';
                                                break;
                                            case 'comment':
                                                echo 'Commentaire';
                                                break;
                                            case 'message':
                                                echo 'Message';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <span class="notification-time">
                                        <?php echo formatTimeAgo($notification['created_at']); ?>
                                    </span>
                                </div>
                                <div class="notification-text">
                                    <strong><?php echo htmlspecialchars($notification['sender_username']); ?></strong>
                                    <?php 
                                    switch($notification['type']) {
                                        case 'like':
                                            echo ' a aimé votre publication';
                                            break;
                                        case 'comment':
                                            echo ' a commenté votre publication';
                                            break;
                                        case 'message':
                                            echo ' vous a envoyé un message';
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php if ($notification['post_content']): ?>
                                    <div class="notification-post">
                                        <p class="notification-post-text"><?php echo htmlspecialchars(substr($notification['post_content'], 0, 100)) . (strlen($notification['post_content']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="notification-actions">
                                    <?php if ($notification['type'] === 'message'): ?>
                                        <a href="message.php?contact_id=<?php echo $notification['sender_id']; ?>" class="notification-link">
                                            <i class='bx bx-message-square-detail'></i>
                                            Voir le message
                                        </a>
                                    <?php else: ?>
                                        <a href="main.php?post_id=<?php echo $notification['post_id']; ?>" class="notification-link">
                                            <i class='bx bx-show'></i>
                                            Voir la publication
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <form action="notifications.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="mark-read-btn">
                                                <i class='bx bx-check'></i>
                                                Marquer comme lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($groupedNotifications['this_month'])): ?>
                <div class="notification-group">
                    <div class="notification-group-header">Ce mois-ci</div>
                    <?php foreach ($groupedNotifications['this_month'] as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $notification['type']; ?>">
                                <?php if ($notification['type'] === 'like'): ?>
                                    <i class='bx bxs-heart'></i>
                                <?php elseif ($notification['type'] === 'comment'): ?>
                                    <i class='bx bxs-comment-detail'></i>
                                <?php else: ?>
                                    <i class='bx bxs-message-detail'></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-type <?php echo $notification['type']; ?>">
                                        <?php 
                                        switch($notification['type']) {
                                            case 'like':
                                                echo 'Like';
                                                break;
                                            case 'comment':
                                                echo 'Commentaire';
                                                break;
                                            case 'message':
                                                echo 'Message';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <span class="notification-time">
                                        <?php echo formatTimeAgo($notification['created_at']); ?>
                                    </span>
                                </div>
                                <div class="notification-text">
                                    <strong><?php echo htmlspecialchars($notification['sender_username']); ?></strong>
                                    <?php 
                                    switch($notification['type']) {
                                        case 'like':
                                            echo ' a aimé votre publication';
                                            break;
                                        case 'comment':
                                            echo ' a commenté votre publication';
                                            break;
                                        case 'message':
                                            echo ' vous a envoyé un message';
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php if ($notification['post_content']): ?>
                                    <div class="notification-post">
                                        <p class="notification-post-text"><?php echo htmlspecialchars(substr($notification['post_content'], 0, 100)) . (strlen($notification['post_content']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="notification-actions">
                                    <?php if ($notification['type'] === 'message'): ?>
                                        <a href="message.php?contact_id=<?php echo $notification['sender_id']; ?>" class="notification-link">
                                            <i class='bx bx-message-square-detail'></i>
                                            Voir le message
                                        </a>
                                    <?php else: ?>
                                        <a href="main.php?post_id=<?php echo $notification['post_id']; ?>" class="notification-link">
                                            <i class='bx bx-show'></i>
                                            Voir la publication
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <form action="notifications.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="mark-read-btn">
                                                <i class='bx bx-check'></i>
                                                Marquer comme lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($groupedNotifications['older'])): ?>
                <div class="notification-group">
                    <div class="notification-group-header">Plus ancien</div>
                    <?php foreach ($groupedNotifications['older'] as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-icon <?php echo $notification['type']; ?>">
                                <?php if ($notification['type'] === 'like'): ?>
                                    <i class='bx bxs-heart'></i>
                                <?php elseif ($notification['type'] === 'comment'): ?>
                                    <i class='bx bxs-comment-detail'></i>
                                <?php else: ?>
                                    <i class='bx bxs-message-detail'></i>
                                <?php endif; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-type <?php echo $notification['type']; ?>">
                                        <?php 
                                        switch($notification['type']) {
                                            case 'like':
                                                echo 'Like';
                                                break;
                                            case 'comment':
                                                echo 'Commentaire';
                                                break;
                                            case 'message':
                                                echo 'Message';
                                                break;
                                        }
                                        ?>
                                    </span>
                                    <span class="notification-time">
                                        <?php echo formatTimeAgo($notification['created_at']); ?>
                                    </span>
                                </div>
                                <div class="notification-text">
                                    <strong><?php echo htmlspecialchars($notification['sender_username']); ?></strong>
                                    <?php 
                                    switch($notification['type']) {
                                        case 'like':
                                            echo ' a aimé votre publication';
                                            break;
                                        case 'comment':
                                            echo ' a commenté votre publication';
                                            break;
                                        case 'message':
                                            echo ' vous a envoyé un message';
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php if ($notification['post_content']): ?>
                                    <div class="notification-post">
                                        <p class="notification-post-text"><?php echo htmlspecialchars(substr($notification['post_content'], 0, 100)) . (strlen($notification['post_content']) > 100 ? '...' : ''); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="notification-actions">
                                    <?php if ($notification['type'] === 'message'): ?>
                                        <a href="message.php?contact_id=<?php echo $notification['sender_id']; ?>" class="notification-link">
                                            <i class='bx bx-message-square-detail'></i>
                                            Voir le message
                                        </a>
                                    <?php else: ?>
                                        <a href="main.php?post_id=<?php echo $notification['post_id']; ?>" class="notification-link">
                                            <i class='bx bx-show'></i>
                                            Voir la publication
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <form action="notifications.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="mark-read-btn">
                                                <i class='bx bx-check'></i>
                                                Marquer comme lu
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
    </div>
    <?php else: ?>
        <div class="container">
            <div class="no-notifications">
                <i class='bx bx-bell-off'></i>
                <p>Aucune notification</p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function handleNotificationClick(notification) {
            if (notification.type === 'message') {
                window.location.href = `message.php?to=${notification.sender_id}`;
            } else if (notification.type === 'like' || notification.type === 'comment') {
                window.location.href = `main.php?post_id=${notification.post_id}`;
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

        document.getElementById('popupOverlay').addEventListener('click', closePopup);

        function updateNotificationCount() {
            fetch('get_notification_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    }
                })
                .catch(error => console.error('Error updating notification count:', error));
        }

        function highlightPost(postId) {
            // Remove any existing highlights
            document.querySelectorAll('.highlighted-post').forEach(el => {
                el.classList.remove('highlighted-post');
            });

            // Find the post and highlight it
            const post = document.querySelector(`[data-post-id="${postId}"]`);
            if (post) {
                post.classList.add('highlighted-post');
                post.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        // Check if we have a post_id in the URL
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('post_id');
        if (postId) {
            // Wait for the page to load
            window.addEventListener('load', () => {
                highlightPost(postId);
            });
        }
    </script>
</body>
</html> 