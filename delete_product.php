<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get product ID from POST data
$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit();
}

try {
    // First, get the product details to delete the image
    $stmt = $pdo->prepare("SELECT image_url FROM marketplace_items WHERE id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product not found or unauthorized']);
        exit();
    }

    // Delete the product from database
    $stmt = $pdo->prepare("DELETE FROM marketplace_items WHERE id = ? AND seller_id = ?");
    $success = $stmt->execute([$product_id, $_SESSION['user_id']]);

    if ($success) {
        // Delete the product image if it exists
        if ($product['image_url'] && file_exists($product['image_url'])) {
            unlink($product['image_url']);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the product']);
}
?> 