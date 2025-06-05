<?php
session_start();
require_once 'db_connection.php'; // Your database connection file

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'Product ID not provided']));
}

$productId = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT m.*, u.username 
                          FROM marketplace_items m
                          JOIN users u ON m.seller_id = u.id
                          WHERE m.id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        die(json_encode(['error' => 'Product not found']));
    }
    
    header('Content-Type: application/json');
    echo json_encode($product);
} catch (Exception $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}