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
    $stmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE id != ?");
    $stmt->execute([$logged_in_user_id]);
    $users = $stmt->fetchAll();
    if (!$users) {
        echo json_encode([]);
        exit;
    }
    echo json_encode($users);
    exit;
}

// Handle fetch messages request (same as before)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_messages'])) {
    $logged_in_user_id = $_SESSION['user_id'];
    $contact_id = $_GET['contact_id'] ?? null;

    if (!$contact_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing contact id']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM messages 
        WHERE (sender_id = :user AND receiver_id = :contact) 
        OR (sender_id = :contact AND receiver_id = :user)
        ORDER BY created_at ASC");
    $stmt->execute([
        'user' => $logged_in_user_id,
        'contact' => $contact_id,
    ]);

    $messages = $stmt->fetchAll();
    echo json_encode($messages);
    exit;
}

// Handle send message request (same as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $logged_in_user_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'] ?? null;
    $content = $_POST['content'] ?? '';

    if (!$receiver_id || trim($content) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $success = $stmt->execute([$logged_in_user_id, $receiver_id, $content]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
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
    <title>FarmX - Réseau Social Agricole</title>
    <link rel="icon" href="Images/logo.jpg">  
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Verdana, sans-serif;
        }

        /* General styles */
        body {
            background-color: #f5f5f5; /* Soft white background */
            color: #333; /* Dark text */
        }

        /* Header */
        header {
          background-color: #3e8e41; /* FarmX green */
          color: white;
          padding: 8px 0;
          box-shadow: 0 2px 5px rgba(0,0,0,0.1);
          position: sticky; /* Makes the header sticky */
         top: 0; /* Sticks it to the top of the page */
          z-index: 1000; /* Ensures the header stays above other content */
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
            background-color: rgba(255, 255, 255, 0.2); /* Semi-transparent white */
            color: white;
        }

        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7); /* Light placeholder text */
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7); /* Light gray icon */
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
            color: #3e8e41; /* FarmX green */
            background-color: white;
        }
       
        .nav-links a.activated {
             color: #3e8e41 !important; /* FarmX green */
             background-color: white !important;
        }

        /* Layout container */
        .layout-container {
            display: flex;
            padding: 0 5px;
            margin-top: 4px;
            height: calc(100vh - 80px); /* Adjust to fit within the viewport */
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
        }

        .contact-info p {
            font-size: 14px;
            margin: 0;
            color: #666; /* Light gray text */
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
        }

        .message.sent .message-content {
            background-color: #3e8e41; /* FarmX green */
            color: white;
        }

        .message.received .message-content {
            background-color: #f0f0f0; /* Light gray */
            color: #333; /* Dark text */
        }

        .chat-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #e0e0e0; /* Light gray border */
            background-color: #f9f9f9; /* Light gray */
            border-radius: 0 0 8px 8px;
        }

        .chat-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #e0e0e0; /* Light gray border */
            border-radius: 20px;
            outline: none;
            background-color: white; /* White background */
            color: #333; /* Dark text */
        }

        .chat-input button {
            margin-left: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            background-color: #3e8e41; /* FarmX green */
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .chat-input button:hover {
            background-color: #2d682f; /* Darker green */
        }
    </style>

    <header>
        <div class="container header-content">
            <div class="logo">
                <img src="Images/logoinv.png"  height="60px" title="Cultivez l'avenir, récoltez le succès">    
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search FarmX...">
                <i class='bx bx-search-alt-2'></i>
            </div>
            <div class="nav-links">
                <a href="main.php" >Home</a>
                <a href="message.php" class="activated">Messages</a>
                <a href="market.php">Marketplace</a>
                <a href="profile.php" >Profile</a>
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
    
    <?php if ($item_details && $recipient): ?>
    // Pre-fill message with item details
    const preFilledMessage = `Hello, I'm interested in your item: ${<?php echo json_encode($item_details['title']); ?>} (${<?php echo json_encode(number_format($item_details['price'], 2)); ?>} MAD). Could you please provide more details?`;
    <?php endif; ?>

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
                        <h3>${escapeHtml(user.username)}</h3>
                        <p>Click to chat</p>
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
        document.querySelector(`.contact[data-user-id="${user.id}"]`).classList.add('active');
        
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
            chatMessagesEl.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.classList.add('message');
                div.classList.add(msg.sender_id == loggedInUserId ? 'sent' : 'received');
                div.innerHTML = `<div class="message-content">${escapeHtml(msg.content)}</div>`;
                chatMessagesEl.appendChild(div);
            });
            chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
        });
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
    </script>
</body>
</html>