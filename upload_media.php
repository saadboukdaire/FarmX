<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["status" => "error", "message" => "User not logged in."]));
}

// Check if a file was uploaded
if (!isset($_FILES['file'])) {
    die(json_encode(["status" => "error", "message" => "No file uploaded."]));
}

$file = $_FILES['file'];

// Check for errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(["status" => "error", "message" => "File upload error: " . $file['error']]));
}

// Define the upload directory
$uploadDir = 'uploads/post_media/';

// Create the directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate a unique filename to avoid conflicts
$fileName = uniqid() . '_' . basename($file['name']);
$filePath = $uploadDir . $fileName;

// Move the uploaded file to the desired directory
if (move_uploaded_file($file['tmp_name'], $filePath)) {
    // Return the file URL
    echo json_encode([
        "status" => "success",
        "url" => $filePath // Return the full path or relative path as needed
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
}
?>