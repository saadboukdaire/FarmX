<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (Exception $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

// Handle automatic message sending when coming from marketplace
if (isset($_GET['to']) && isset($_GET['item_id'])) {
    $buyer_id = $_SESSION['user_id'];
    $seller_id = $_GET['to'];
    $item_id = $_GET['item_id'];
    
    // Check if this is the first message about this item
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages 
                          WHERE sender_id = ? AND receiver_id = ? 
                          AND content LIKE ?");
    $stmt->execute([$buyer_id, $seller_id, "%item_id=$item_id%"]);
    $message_exists = $stmt->fetchColumn();
    
    if (!$message_exists) {
        // Get item details
        $stmt = $pdo->prepare("SELECT title, price FROM marketplace_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if ($item) {
            $content = "Bonjour, je suis intéressé par votre article : {$item['title']} ({$item['price']} MAD). [item_id=$item_id]";
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$buyer_id, $seller_id, $content]);
        }
    }
}


// Handle fetch users request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_users'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Non connecté']);
        exit;
    }
    $logged_in_user_id = $_SESSION['user_id'];
    $filter = $_GET['filter'] ?? 'all';
    
    // Base query to get users with their message counts and last message info
    $sql = "SELECT 
            u.id, 
            u.username, 
            u.profile_pic,
        u.user_type,
        u.user_tag,
        u.bio,
        u.gender,
        u.created_at,
        COUNT(DISTINCT m.id) as messages_received,
        (
            SELECT COUNT(*) 
            FROM messages 
            WHERE receiver_id = :user_id 
            AND sender_id = u.id 
            AND is_read = 0
        ) as unread_count,
        (
            SELECT content 
            FROM messages 
             WHERE (sender_id = u.id AND receiver_id = :user_id) 
             OR (sender_id = :user_id AND receiver_id = u.id)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT sender_id 
            FROM messages 
             WHERE (sender_id = u.id AND receiver_id = :user_id) 
             OR (sender_id = :user_id AND receiver_id = u.id)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message_sender,
        (
            SELECT MAX(created_at)
            FROM messages 
             WHERE (sender_id = u.id AND receiver_id = :user_id) 
             OR (sender_id = :user_id AND receiver_id = u.id)
        ) as last_message_time
        FROM users u
    LEFT JOIN messages m ON (m.sender_id = :user_id AND m.receiver_id = u.id) OR (m.sender_id = u.id AND m.receiver_id = :user_id)
        WHERE u.id != :user_id
    GROUP BY u.id";

    // Add filter conditions
    switch ($filter) {
        case 'newest':
            $sql .= " ORDER BY u.created_at DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY u.created_at ASC";
            break;
        case 'latest_messages':
            $sql .= " ORDER BY CASE WHEN last_message_time IS NULL THEN 0 ELSE 1 END DESC, last_message_time DESC, u.username ASC";
            break;
        default: // 'all'
            $sql .= " ORDER BY u.username ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($users);
    exit;
}

// Handle fetch messages request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_messages'])) {
    $logged_in_user_id = $_SESSION['user_id'];
    $contact_id = $_GET['contact_id'] ?? null;

    if (!$contact_id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID du contact manquant']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get messages with sender's username
        $stmt = $pdo->prepare("SELECT m.*, u.username as sender_username 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = :user AND m.receiver_id = :contact) 
            OR (m.sender_id = :contact AND m.receiver_id = :user)
            ORDER BY m.created_at ASC");
        $stmt->execute([
            'user' => $logged_in_user_id,
            'contact' => $contact_id,
        ]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mark messages as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE 
            WHERE sender_id = :contact AND receiver_id = :user AND is_read = FALSE");
        $stmt->execute([
            'contact' => $contact_id,
            'user' => $logged_in_user_id,
        ]);

        // Mark notifications as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE 
            WHERE user_id = :user AND sender_id = :contact AND is_read = FALSE");
        $stmt->execute([
            'user' => $logged_in_user_id,
            'contact' => $contact_id,
        ]);

        $pdo->commit();
        
        // Ensure proper JSON response
        header('Content-Type: application/json');
        echo json_encode($messages, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Échec de la récupération des messages: ' . $e->getMessage()]);
    }
    exit;
}

// Handle send message request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $logged_in_user_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'] ?? null;
    $content = $_POST['content'] ?? '';

    if (!$receiver_id || trim($content) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Entrée invalide']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert message
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, ?, FALSE)");
        $stmt->execute([$logged_in_user_id, $receiver_id, $content]);
        $message_id = $pdo->lastInsertId();

        // Create notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, content, message_id) VALUES (?, ?, 'message', 'vous a envoyé un message', ?)");
        $stmt->execute([$receiver_id, $logged_in_user_id, $message_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Échec de l\'envoi du message : ' . $e->getMessage()]);
    }
    exit;
}

// Get item details if item_id is provided
$item_details = null;
if (isset($_GET['item_id'])) {
    $stmt = $pdo->prepare("SELECT m.*, u.username 
                          FROM marketplace_items m
                          JOIN users u ON m.seller_id = u.id
                          WHERE m.id = ? AND m.status = 'available'");
    $stmt->execute([$_GET['item_id']]);
    $item_details = $stmt->fetch();
}

// Get recipient details if 'to' parameter is provided
$recipient = null;
if (isset($_GET['to'])) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$_GET['to']]);
    $recipient = $stmt->fetch();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Messages</title>
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
            display: flex;
            align-items: center;
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
            padding: 10px;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: #f5f5f5;
        }

        .notification-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .notification-content {
            flex: 1;
        }

        .notification-content p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }

        .notification-time {
            font-size: 12px;
            color: #666;
        }

        .notification-item.unread {
            background-color: #f0f7f0;
        }

        /* General styles */
        body {
            background-color: #f5f5f5; /* Soft white background */
            color: #333; /* Dark text */
        }

        /* Layout container */
        .layout-container {
            display: flex;
            padding: 0 5px;
            margin-top: 4px;
            height: calc(100vh - 80px);
            width: 100%;
            box-sizing: border-box; /* Ensure padding is included in width calculation */
        }

        /* Left section (Contacts) */
        .left-section {
            position: fixed;
            width: 290px; /* Initial width */
            height: calc(100vh - 80px);
            top: 80px; /* Keeps it from touching the top of the screen */
            left: 5px;
            border-radius: 8px;
            padding: 10px;
            overflow-y: auto;
            background-color: white; /* White background */
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .left-section h1 {
            text-align: center;
            color: white; /* White text for better contrast */
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 15px 0;
            background: linear-gradient(to right, #3e8e41, #2d682f); /* FarmX green gradient */
            border-radius: 8px;
            display: inline-block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .contact-list {
            margin-top: 20px;
        }

        .contact {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            background-color: #f9f9f9; /* Light gray */
            margin-bottom: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .contact:hover {
            background-color: #f0f0f0; /* Slightly darker gray on hover */
        }

        .contact img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .contact-info h3 {
            font-size: 16px;
            margin: 0;
            color: #333; /* Dark text */
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .contact-info h3 .unread-badge {
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            min-width: 20px;
            text-align: center;
        }

        .contact-info p {
            font-size: 14px;
            margin: 0;
            color: #666; /* Light gray text */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        .contact.active {
    background-color: #e0f7e0; /* Light green background */
    border-left: 4px solid #3e8e41; /* FarmX green border */
}

        /* Middle section (Chat Area) */
        .middle-section {
            flex: 4;
            margin-left: 300px; /* Adjust to prevent overlap */
            padding: 10px;
            height: calc(100vh - 80px); /* Restrict height */
            overflow-y: auto; /* Enable internal scrolling */
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            background-color: white; /* White background */
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 10px 15px;
            border-bottom: 1px solid #e0e0e0;
            background-color: #f9f9f9;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h2 {
            font-size: 20px;
            margin: 0;
            color: #333;
        }

        .profile-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #3e8e41;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            margin-left: 15px;
        }

        .profile-link:hover {
            background-color: #2d682f;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .profile-link i {
            font-size: 16px;
        }

        .chat-messages {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
        }

        .welcome-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 16px;
            margin-top: 20px;
        }

        .welcome-message strong {
            color: #3e8e41;
            font-weight: 600;
        }

        .message {
            display: flex;
            margin-bottom: 10px;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 70%;
            padding: 10px;
            border-radius: 8px;
            background-color: #f0f0f0; /* Light gray */
            color: #333; /* Dark text */
            position: relative;
        }

        .message.sent .message-content {
            background-color: #3e8e41; /* FarmX green */
            color: white;
        }

        .message.received .message-content {
            background-color: #f0f0f0; /* Light gray */
            color: #333; /* Dark text */
        }

        .message-time {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            text-align: right;
        }

        .message.sent .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .message-sender {
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .message.sent .message-sender {
            color: rgba(255, 255, 255, 0.9);
        }

        .message.received .message-sender {
            color: #3e8e41;
        }

        .username-link {
            color: inherit;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .username-link:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .chat-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #e0e0e0;
            background-color: #f9f9f9;
            border-radius: 0 0 8px 8px;
            position: relative;
            z-index: 10;
        }

        .chat-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            outline: none;
            background-color: white;
            color: #333;
            font-size: 14px;
            margin-right: 10px;
        }

        .chat-input input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .chat-input button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            background-color: #3e8e41;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }

        .chat-input button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .chat-input button:hover:not(:disabled) {
            background-color: #2d682f;
        }

        .user-item {
            position: relative;
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .user-item:hover {
            background-color: #f5f5f5;
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .unread-badge {
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 8px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .username {
            font-weight: 500;
            color: #333;
        }

        .user-filter {
            margin: 15px 0;
            padding: 0 10px;
        }

        .filter-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: white;
            color: #333;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 0 2px rgba(62, 142, 65, 0.1);
            outline: none;
        }

        .filter-select:hover {
            border-color: #3e8e41;
        }

        /* Add custom alert styles */
        .custom-alert {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
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
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 350px;
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
            height: 5px;
            background: linear-gradient(90deg, #ff4d4d, #cc0000);
        }

        .success-alert .alert-content::before {
            background: linear-gradient(90deg, #4CAF50, #45a049);
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
            padding: 12px 30px;
            background: linear-gradient(45deg, #ff4d4d, #cc0000);
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 77, 77, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .success-alert .alert-content button {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .alert-content button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 77, 77, 0.4);
        }

        .success-alert .alert-content button:hover {
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
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

        .success-alert .alert-content i {
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Add custom alert modal -->
    <div id="customAlert" class="custom-alert">
        <div class="alert-content">
            <i class='bx bxs-error-circle'></i>
            <p id="alertMessage"></p>
            <button id="alertCloseButton">OK</button>
        </div>
    </div>

    <header>
        <div class="header-content">
            <div class="logo">
                <a href="main.php">
                    <img src="Images/logoinv.png" alt="FarmX Logo">
                </a>
            </div>
            <div class="nav-links">
                <a href="message.php" class="activated" title="Messages">
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
                <a href="profile.php" title="Profil">
                    <i class='bx bxs-user'></i>
                </a>
                <a href="logout.php" title="Déconnexion">
                    <i class='bx bx-log-out'></i>
                </a>
            </div>
        </div>
    </header>

    <div class="layout-container">
        <!-- Left Section (Contacts) -->
        <div class="left-section">
            <h1>
                <i class="fas fa-seedling" style="color:#4CAF50;margin-right:8px;"></i>
                Communauté
            </h1>
            <div class="user-filter">
                <select id="userFilter" class="filter-select">
                    <option value="all">Tous les utilisateurs</option>
                    <option value="newest">Nouveaux utilisateurs</option>
                    <option value="oldest">Anciens utilisateurs</option>
                    <option value="latest_messages">Derniers messages</option>
                </select>
            </div>
            <div class="contact-list" id="contactList">
                <!-- Contacts will be loaded dynamically -->
            </div>
        </div>

        <!-- Middle Section (Chat Area) -->
        <div class="middle-section">
            <div class="chat-header" id="chatHeader">
                <h2>Sélectionnez un contact pour discuter</h2>
            </div>
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will appear here -->
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Écrire un message..." disabled>
                <button id="sendButton" disabled>Envoyer</button>
            </div>
        </div>
    </div>

    <script>
    const loggedInUserId = <?php echo json_encode($_SESSION['user_id'] ?? 0); ?>;
    let currentContactId = null;
    const contactListEl = document.getElementById('contactList');
    const chatMessagesEl = document.getElementById('chatMessages');
    const chatHeaderEl = document.getElementById('chatHeader');
    const inputEl = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendButton');
    const notificationIcon = document.querySelector('.notification-icon');
    const notificationDropdown = document.querySelector('.notification-dropdown');
    const notificationBadge = document.querySelector('.notification-badge');
    
    <?php if ($item_details && $recipient): ?>
    // Pre-fill message with item details
    const preFilledMessage = `Bonjour, je suis intéressé par votre article : ${<?php echo json_encode($item_details['title']); ?>} (${<?php echo json_encode(number_format($item_details['price'], 2)); ?>} MAD). Pourriez-vous me fournir plus d'informations sur cet article ?`;
    <?php endif; ?>

    let lastMessageCount = 0; // Add this variable to track message count

    // Load all users for the sidebar
    function loadContacts() {
        fetch('message.php?fetch_users=1')
        .then(res => res.json())
        .then(users => {
            contactListEl.innerHTML = '';
            if (users.error) {
                contactListEl.innerHTML = '<div style="color:red; padding:10px;">' + users.error + '</div>';
                return;
            }
            if (!users.length) {
                contactListEl.innerHTML = '<div style="padding:10px;">Aucun utilisateur trouvé.</div>';
                return;
            }
            users.forEach(user => {
                const contact = document.createElement('div');
                contact.classList.add('contact');
                contact.dataset.userId = user.id;
                contact.innerHTML = `
                    <img src="${user.profile_pic ? user.profile_pic + '?t=' + Date.now() : 'Images/profile.jpg'}" alt="Profile">
                    <div class="contact-info">
                        <h3>${escapeHtml(user.username)}${user.unread_count > 0 ? ` <span class="unread-badge">${user.unread_count}</span>` : ''}</h3>
                        <p>${user.messages_received === 0 ? 'Démarrer une nouvelle discussion !':
                            (user.last_message_sender == loggedInUserId ? 
                                `Vous: ${escapeHtml(user.last_message)}` : 
                                escapeHtml(user.last_message))}</p>
                    </div>
                `;
                contact.addEventListener('click', () => selectContact(user));
                contactListEl.appendChild(contact);
                
                <?php if ($recipient && $item_details): ?>
                // If this is the recipient from the marketplace, select them automatically
                if (user.id == <?php echo json_encode($recipient['id']); ?>) {
                    setTimeout(() => {
                        selectContact(user);
                        inputEl.value = preFilledMessage;
                        inputEl.disabled = false;
                        sendBtn.disabled = false;
                    }, 500);
                }
                <?php endif; ?>
            });
        });
    }

    // Select a contact to chat with
    function selectContact(user) {
        if (!user || !user.id) return;
        
        currentContactId = user.id;
        document.querySelectorAll('.contact').forEach(c => c.classList.remove('active'));
        const selectedContact = document.querySelector(`.contact[data-user-id="${user.id}"]`);
        if (selectedContact) {
            selectedContact.classList.add('active');
            
            // Remove unread badge when chat is selected
            const unreadBadge = selectedContact.querySelector('.unread-badge');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        }
        
        chatHeaderEl.innerHTML = `
            <h2>${escapeHtml(user.username)}</h2>
            <a href="profile.php?id=${user.id}" class="profile-link" title="Voir le profil">
                <i class='bx bx-user'></i>
                Profil
            </a>`;
        inputEl.disabled = false;
        sendBtn.disabled = false;
        inputEl.focus();
        
        loadMessages();
    }

    // Load messages for the selected contact
    function loadMessages() {
        if (!currentContactId) return;
        
        fetch(`message.php?fetch_messages=1&contact_id=${currentContactId}`)
        .then(res => res.json())
        .then(messages => {
            const currentScrollPosition = chatMessagesEl.scrollTop;
            const wasAtBottom = chatMessagesEl.scrollHeight - chatMessagesEl.scrollTop === chatMessagesEl.clientHeight;
            
            chatMessagesEl.innerHTML = '';
            
            if (messages.length === 0) {
                // Get the username from the chat header
                const username = chatHeaderEl.querySelector('h2').textContent;
                chatMessagesEl.innerHTML = `
                    <div class="welcome-message">
                        Démarrez une nouvelle conversation avec <strong>${escapeHtml(username)}</strong> !
                    </div>`;
            } else {
                messages.forEach(msg => {
                    const div = document.createElement('div');
                    div.classList.add('message');
                    div.classList.add(msg.sender_id == loggedInUserId ? 'sent' : 'received');
                    div.innerHTML = `
                        <div class="message-content">
                            ${escapeHtml(msg.content)}
                            <div class="message-time">${formatMessageTime(msg.created_at)}</div>
                        </div>`;
                    chatMessagesEl.appendChild(div);
                });
            }

            // Only auto-scroll if:
            // 1. User was already at the bottom, or
            // 2. New messages were added
            if (wasAtBottom || messages.length > lastMessageCount) {
                chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
            } else {
                chatMessagesEl.scrollTop = currentScrollPosition;
            }
            
            lastMessageCount = messages.length;
        });
    }

    // Format message timestamp
    function formatMessageTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        
        // Format time as HH:MM AM/PM
        const timeStr = date.toLocaleTimeString('fr-FR', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });

        // Check if message is from today
        if (date.toDateString() === now.toDateString()) {
            return timeStr;
        }
        
        // Check if message is from yesterday
        if (date.toDateString() === yesterday.toDateString()) {
            return `Hier à ${timeStr}`;
        }
        
        // For older messages, show full date and time
        return date.toLocaleDateString('fr-FR', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }) + ` à ${timeStr}`;
    }

    // Send a message
    function sendMessage() {
        const content = inputEl.value.trim();
        if (!content || !currentContactId) return;
        
        const formData = new FormData();
        formData.append('send_message', '1');
        formData.append('receiver_id', currentContactId);
        formData.append('content', content);
        
        fetch('message.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            console.log('Server response:', data); // Log the server response
            if (data.success === true) { // Changed condition to check for 'success' key
                inputEl.value = '';
                loadMessages();
                updateNotificationCount(); // Call to update notification count
            } else {
                showAlert(data.error || 'Échec de l\'envoi du message', 'error'); // Display specific error if available
            }
        });
    }

    // Event listeners
    sendBtn.addEventListener('click', sendMessage);
    inputEl.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Initialize
    loadContacts();
    
    // Refresh messages every 3 seconds
    setInterval(() => {
        if (currentContactId) loadMessages();
    }, 3000);

    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($item_details): ?>
        // Pre-fill message with item details
        const messageContent = `Bonjour,\n\nJe suis intéressé par votre article "${<?php echo json_encode($item_details['title']); ?>"}.\n\nDétails de l'article:\n- Prix: ${<?php echo json_encode(number_format($item_details['price'], 2)); ?>} MAD\n- Description: ${<?php echo json_encode($item_details['description']); ?>}\n\nPourriez-vous me fournir plus d'informations sur cet article ?`;
        
        // Set the message content
        const messageInput = document.querySelector('.message-input');
        if (messageInput) {
            messageInput.value = messageContent;
        }
        
        // Select the recipient if not already selected
        const recipientId = <?php echo json_encode($recipient['id'] ?? null); ?>;
        if (recipientId) {
            const userList = document.querySelector('.user-list');
            const recipientElement = userList.querySelector(`[data-user-id="${recipientId}"]`);
            if (recipientElement) {
                recipientElement.click();
            }
        }
        <?php endif; ?>
    });

    // Handle notification icon click
    notificationIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
        loadNotifications();
    });

    // Close notification dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!notificationDropdown.contains(e.target) && e.target !== notificationIcon) {
            notificationDropdown.style.display = 'none';
        }
    });

    // Load notifications
    function loadNotifications() {
        fetch('get_notifications.php')
        .then(res => res.json())
        .then(notifications => {
            notificationDropdown.innerHTML = '';
            if (!notifications.length) {
                notificationDropdown.innerHTML = '<div style="padding: 10px; text-align: center; color: #666;">No notifications</div>';
                return;
            }
            
            notifications.forEach(notification => {
                const item = document.createElement('div');
                item.classList.add('notification-item');
                if (!notification.is_read) {
                    item.classList.add('unread');
                }
                
                item.innerHTML = `
                    <img src="${notification.sender_profile_pic || 'Images/profile.jpg'}" alt="Profile">
                    <div class="notification-content">
                        <p>${escapeHtml(notification.sender_username)} sent you a message</p>
                        <span class="notification-time">${formatTime(notification.created_at)}</span>
                    </div>
                `;
                
                item.addEventListener('click', () => {
                    // Mark as read and navigate to chat
                    fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ notification_id: notification.id })
                    });
                    
                    // Find and select the contact
                    const contact = document.querySelector(`.contact[data-user-id="${notification.sender_id}"]`);
                    if (contact) {
                        contact.click();
                    }
                    
                    notificationDropdown.style.display = 'none';
                });
                
                notificationDropdown.appendChild(item);
            });
        });
    }

    // Format time for notifications
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) { // Less than 1 minute
            return 'Just now';
        } else if (diff < 3600000) { // Less than 1 hour
            const minutes = Math.floor(diff / 60000);
            return `${minutes}m ago`;
        } else if (diff < 86400000) { // Less than 1 day
            const hours = Math.floor(diff / 3600000);
            return `${hours}h ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

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

    // Add this to your existing JavaScript
    document.getElementById('userFilter').addEventListener('change', function() {
        const filter = this.value;
        fetchUsers(filter);
    });

    function fetchUsers(filter = 'all') {
        fetch(`message.php?fetch_users=1&filter=${filter}`)
            .then(response => response.json())
            .then(users => {
                const contactList = document.querySelector('.contact-list');
                contactList.innerHTML = '';
                
                if (users.length === 0) {
                    contactList.innerHTML = '<p class="no-contacts">No contacts found</p>';
                    return;
                }
                
                users.forEach(user => {
                    const contact = document.createElement('div');
                    contact.className = 'contact';
                    contact.dataset.userId = user.id;
                    
                    const lastMessage = user.last_message || 'Démarrer une nouvelle discussion !';
                    const unreadCount = user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : '';
                    
                    contact.innerHTML = `
                        <img src="${user.profile_pic || 'Images/profile.jpg'}" alt="${user.username}">
                        <div class="contact-info">
                            <h3>${escapeHtml(user.username)} ${unreadCount}</h3>
                            <p>${escapeHtml(lastMessage)}</p>
                        </div>
                    `;
                    
                    contact.addEventListener('click', () => selectContact(user));
                    contactList.appendChild(contact);
                });
            })
            .catch(error => console.error('Error fetching users:', error));
    }

    // Call fetchUsers when the page loads
    document.addEventListener('DOMContentLoaded', () => {
        fetchUsers();
    });

    // Add showAlert function
    function showAlert(message, type = 'error') {
        const alertModal = document.getElementById("customAlert");
        const alertMessage = document.getElementById("alertMessage");
        const alertIcon = alertModal.querySelector('i');
        
        // Remove existing classes
        alertModal.classList.remove('success-alert', 'error-alert');
        // Add appropriate class
        alertModal.classList.add(type + '-alert');
        
        // Update icon based on type
        alertIcon.className = type === 'success' ? 'bx bxs-check-circle' : 'bx bxs-error-circle';
        
        alertMessage.textContent = message;
        alertModal.style.display = "flex";

        const closeButton = document.getElementById("alertCloseButton");
        closeButton.onclick = function() {
            alertModal.style.display = "none";
        };

        alertModal.onclick = function(event) {
            if (event.target === alertModal) {
                alertModal.style.display = "none";
            }
        };
    }

    document.getElementById('popupOverlay').addEventListener('click', closePopup);
    </script>
</body>
</html>