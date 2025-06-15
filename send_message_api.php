<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
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
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Handle direct message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buyer_id = $_SESSION['user_id'];
    $seller_id = $_POST['seller_id'] ?? null;
    $item_id = $_POST['item_id'] ?? null;
    
    if (!$seller_id || !$item_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit();
    }
    
    // Get item details
    $stmt = $pdo->prepare("SELECT title, price FROM marketplace_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found']);
        exit();
    }
    
    // Check if this is the first message about this specific item
    $content = "Bonjour, je suis intéressé(e) par votre article : {$item['title']} ({$item['price']} MAD).";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages 
                          WHERE sender_id = ? 
                          AND receiver_id = ? 
                          AND content = ?");
    $stmt->execute([$buyer_id, $seller_id, $content]);
    $message_exists = $stmt->fetchColumn();
    
    if (!$message_exists) {
        try {
            $pdo->beginTransaction();
            
            // Insert the message
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$buyer_id, $seller_id, $content]);
            
            // Create a notification for the seller
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, content) VALUES (?, ?, 'message', 'vous a envoyé un message concernant un article')");
            $stmt->execute([$seller_id, $buyer_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send message']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Message already sent']);
    }
    exit();
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']); 