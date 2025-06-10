<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if language parameter is provided
if (!isset($_POST['language']) || !in_array($_POST['language'], ['en', 'fr'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid language']);
    exit;
}

// Update session language
$_SESSION['language'] = $_POST['language'];

// Return success response
echo json_encode(['success' => true]);
?> 