<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the very top
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    die("User is not logged in. Session ID: " . session_id());
}

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "farmx"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id']; // Use the correct session variable
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = $_POST['bio'] ?? ''; // Get bio from POST data, default to empty string if not set
    $gender = $_POST['gender'] ?? ''; // Get gender from POST data, default to empty string if not set

    // Validation patterns
    $usernamePattern = '/^[a-zA-Z0-9_]{3,20}$/';
    $emailPattern = '/^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|icloud\.com)$/';
    $phonePattern = '/^\+212[67][0-9]{8}$/';

    // Validate input
    $errors = [];

    if (!preg_match($usernamePattern, $username)) {
        $errors[] = "Username must be 3-20 characters long and can only contain letters, numbers, and underscores";
    }

    if (!preg_match($emailPattern, $email)) {
        $errors[] = "Please enter a valid email address with a supported domain (gmail.com, yahoo.com, hotmail.com, outlook.com, or icloud.com)";
    }

    if (!preg_match($phonePattern, $phone)) {
        $errors[] = "Please enter a valid Moroccan phone number starting with +212 followed by 6 or 7 and 8 digits";
    }

    // Check if username or email already exists (excluding current user)
    $checkStmt = $conn->prepare("SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $checkStmt->bind_param("ssi", $username, $email, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['username'] === $username) {
                $errors[] = "Username already exists";
            }
            if ($row['email'] === $email) {
                $errors[] = "Email already exists";
            }
        }
    }
    $checkStmt->close();

    // If there are validation errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['profile_errors'] = $errors;
        header("Location: edit_profile.php");
        exit();
    }

    // Handle file upload
    if (isset($_FILES['profile-pic-upload']) && $_FILES['profile-pic-upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_pictures/'; // Updated path to profile pictures subfolder
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create the uploads directory if it doesn't exist
        }

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        $fileType = $_FILES['profile-pic-upload']['type'];
        $fileSize = $_FILES['profile-pic-upload']['size'];

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
            $uploadFile = $uploadDir . uniqid() . '_' . basename($_FILES['profile-pic-upload']['name']); // Unique filename
            if (move_uploaded_file($_FILES['profile-pic-upload']['tmp_name'], $uploadFile)) {
                // Store the relative path in the database
                $profilePicUrl = $uploadFile; // Relative path for web access

                // Update the profile picture in the users table
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, profile_pic = ?, bio = ?, gender = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $username, $email, $phone, $profilePicUrl, $bio, $gender, $userId);

                if ($stmt->execute()) {
                    // Update the profile picture in all the user's posts
                    $updatePostsStmt = $conn->prepare("UPDATE posts SET profile_pic = ? WHERE user_id = ?");
                    $updatePostsStmt->bind_param("si", $profilePicUrl, $userId);
                    $updatePostsStmt->execute();
                    $updatePostsStmt->close();

                    // Update the session variable with the new profile picture URL
                    $_SESSION['profile_pic'] = $profilePicUrl;

                    // Redirect to profile.php after successful update
                    header("Location: profile.php");
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                }

                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type or size']);
            exit;
        }
    } else {
        // No new file uploaded, update other fields
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, bio = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $username, $email, $phone, $bio, $gender, $userId);

        if ($stmt->execute()) {
            // Redirect to profile.php after successful update
            header("Location: profile.php");
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }

        $stmt->close();
    }
}

$conn->close();
?>