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
            $content = "Hello, I'm interested in your item: {$item['title']} ({$item['price']} MAD). [item_id=$item_id]";
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$buyer_id, $seller_id, $content]);
        }
    }
}


// Handle fetch users request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_users'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    $logged_in_user_id = $_SESSION['user_id'];
    
    // Get users with unread message count
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.profile_pic,
            COUNT(CASE WHEN m.is_read = FALSE AND m.receiver_id = :user_id THEN 1 END) as unread_count,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = :user_id) as messages_received,
            (SELECT content FROM messages 
             WHERE (sender_id = u.id AND receiver_id = :user_id) 
             OR (sender_id = :user_id AND receiver_id = u.id)
             ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages 
             WHERE (sender_id = u.id AND receiver_id = :user_id) 
             OR (sender_id = :user_id AND receiver_id = u.id)
             ORDER BY created_at DESC LIMIT 1) as last_message_time,
            (SELECT sender_id FROM messages 
             WHERE (sender_id = u.id AND receiver_id = :user_id) 
             OR (sender_id = :user_id AND receiver_id = u.id)
             ORDER BY created_at DESC LIMIT 1) as last_message_sender
        FROM users u
        LEFT JOIN messages m ON (m.sender_id = u.id AND m.receiver_id = :user_id)
        WHERE u.id != :user_id
        GROUP BY u.id
    ");
    $stmt->execute(['user_id' => $logged_in_user_id]);
    $users = $stmt->fetchAll();
    
    if (!$users) {
        echo json_encode([]);
        exit;
    }
    echo json_encode($users);
    exit;
}

// Handle fetch messages request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_messages'])) {
    $logged_in_user_id = $_SESSION['user_id'];
    $contact_id = $_GET['contact_id'] ?? null;

    if (!$contact_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing contact id']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get messages
        $stmt = $pdo->prepare("SELECT * FROM messages 
            WHERE (sender_id = :user AND receiver_id = :contact) 
            OR (sender_id = :contact AND receiver_id = :user)
            ORDER BY created_at ASC");
        $stmt->execute([
            'user' => $logged_in_user_id,
            'contact' => $contact_id,
        ]);
        $messages = $stmt->fetchAll();

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
        echo json_encode($messages);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch messages']);
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
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Insert message
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, ?, FALSE)");
        $stmt->execute([$logged_in_user_id, $receiver_id, $content]);
        $message_id = $pdo->lastInsertId();

        // Create notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, content, message_id) VALUES (?, ?, 'message', 'sent you a message', ?)");
        $stmt->execute([$receiver_id, $logged_in_user_id, $message_id]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message: ' . $e->getMessage()]);
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
            padding: 10px;
            border-bottom: 1px solid #e0e0e0; /* Light gray border */
            background-color: #f9f9f9; /* Light gray */
            border-radius: 8px 8px 0 0;
        }

        .chat-header h2 {
            font-size: 20px;
            margin: 0;
            color: #333; /* Dark text */
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
                <a href="message.php" class="activated" title="Messages">
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

    <div class="layout-container">
        <!-- Left Section (Contacts) -->
        <div class="left-section">
            <h1>Community </h1>
            <div class="contact-list" id="contactList">
                <!-- Contacts will be loaded dynamically -->
            </div>
        </div>

        <!-- Middle Section (Chat Area) -->
        <div class="middle-section">
            <div class="chat-header" id="chatHeader">
                <h2>Select a contact to chat</h2>
            </div>
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will appear here -->
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                <button id="sendButton" disabled>Send</button>
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
    const preFilledMessage = `Hello, I'm interested in your item: ${<?php echo json_encode($item_details['title']); ?>} (${<?php echo json_encode(number_format($item_details['price'], 2)); ?>} MAD). Could you please provide more details?`;
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
                contactListEl.innerHTML = '<div style="padding:10px;">No users found.</div>';
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
                        <p>${user.messages_received === 0 ? 'Start a new chat!' : 
                            (user.last_message_sender == loggedInUserId ? 
                                `You: ${escapeHtml(user.last_message)}` : 
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
        currentContactId = user.id;
        document.querySelectorAll('.contact').forEach(c => c.classList.remove('active'));
        const selectedContact = document.querySelector(`.contact[data-user-id="${user.id}"]`);
        selectedContact.classList.add('active');
        
        // Remove unread badge when chat is selected
        const unreadBadge = selectedContact.querySelector('.unread-badge');
        if (unreadBadge) {
            unreadBadge.remove();
        }
        
        chatHeaderEl.innerHTML = `<h2>${escapeHtml(user.username)}</h2>`;
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
                        Start a new conversation with <strong>${escapeHtml(username)}</strong>!
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
        const timeStr = date.toLocaleTimeString('en-US', { 
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
            return `Yesterday at ${timeStr}`;
        }
        
        // For older messages, show full date and time
        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }) + ` at ${timeStr}`;
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
        .then(response => {
            if (response.success) {
                inputEl.value = '';
                loadMessages();
            } else {
                alert('Failed to send message');
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
        const messageContent = `Hello,\n\nI'm interested in your item "${<?php echo json_encode($item_details['title']); ?>"}.\n\nItem Details:\n- Price: ${<?php echo json_encode(number_format($item_details['price'], 2)); ?>} MAD\n- Description: ${<?php echo json_encode($item_details['description']); ?>}\n\nCould you please provide more information about this item?`;
        
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
    </script>
</body>
</html>