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

    $profilePicUrl = null; // Default to null, will be updated if a new pic is uploaded or removed
    $shouldUpdateProfilePic = false; // Flag to indicate if profile_pic column needs updating

    // Handle file upload or removal
    if (isset($_POST['remove_profile_pic']) && $_POST['remove_profile_pic'] === '1') {
        // User explicitly wants to remove the profile picture or reset to default
        $profilePicUrl = 'Images/profile.jpg'; // Set to default path
        $shouldUpdateProfilePic = true;

        // Optionally, delete the old profile picture file from the server if it's not the default
        // First, fetch the current profile_pic path from the database
        $currentProfilePicSql = "SELECT profile_pic FROM users WHERE id = ?";
        $currentProfilePicStmt = $conn->prepare($currentProfilePicSql);
        $currentProfilePicStmt->bind_param("i", $userId);
        $currentProfilePicStmt->execute();
        $currentProfilePicStmt->bind_result($oldProfilePic);
        $currentProfilePicStmt->fetch();
        $currentProfilePicStmt->close();

        if ($oldProfilePic && $oldProfilePic !== 'Images/profile.jpg' && file_exists($oldProfilePic)) {
            unlink($oldProfilePic); // Delete the old file
        }

    } else if (isset($_FILES['profile-pic-upload']) && $_FILES['profile-pic-upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_pictures/'; // Updated path to profile pictures subfolder
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create the uploads directory if it doesn't exist
        }

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        $fileType = $_FILES['profile-pic-upload']['type'];
        $fileSize = $_FILES['profile-pic-upload']['size'];

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
            $uploadFile = $uploadDir . uniqid() . '_' . basename($_FILES['profile-pic-upload']['name']); // Unique filename
            if (move_uploaded_file($_FILES['profile-pic-upload']['tmp_name'], $uploadFile)) {
                // Store the relative path in the database
                $profilePicUrl = $uploadFile; // Relative path for web access
                $shouldUpdateProfilePic = true;

                // Optionally, delete the old profile picture file from the server if it's not the default
                $currentProfilePicSql = "SELECT profile_pic FROM users WHERE id = ?";
                $currentProfilePicStmt = $conn->prepare($currentProfilePicSql);
                $currentProfilePicStmt->bind_param("i", $userId);
                $currentProfilePicStmt->execute();
                $currentProfilePicStmt->bind_result($oldProfilePic);
                $currentProfilePicStmt->fetch();
                $currentProfilePicStmt->close();

                if ($oldProfilePic && $oldProfilePic !== 'Images/profile.jpg' && file_exists($oldProfilePic)) {
                    unlink($oldProfilePic); // Delete the old file
                }
            } else {
                $errors[] = "File upload failed";
                // Handle the error, perhaps redirect with an error message
            }
        } else {
            $errors[] = "Invalid file type or size";
            // Handle the error
        }

        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            header("Location: edit_profile.php");
            exit();
        }
    }

    // Fetch current user data for comparison
    $currentDataStmt = $conn->prepare("SELECT username, email, phone, bio, gender, profile_pic FROM users WHERE id = ?");
    $currentDataStmt->bind_param("i", $userId);
    $currentDataStmt->execute();
    $currentData = $currentDataStmt->get_result()->fetch_assoc();
    $currentDataStmt->close();

    // Check if any changes were made
    $changesMade = false;
    if ($currentData['username'] !== $username ||
        $currentData['email'] !== $email ||
        $currentData['phone'] !== $phone ||
        $currentData['bio'] !== $bio ||
        $currentData['gender'] !== $gender ||
        $shouldUpdateProfilePic) {
        $changesMade = true;
    }

    // Construct the SQL update query dynamically
    $sqlFields = ['username = ?', 'email = ?', 'phone = ?', 'bio = ?', 'gender = ?'];
    $bindTypes = "sssss";
    $bindParams = [&$username, &$email, &$phone, &$bio, &$gender];

    if ($shouldUpdateProfilePic) {
        $sqlFields[] = 'profile_pic = ?';
        $bindTypes .= "s";
        $bindParams[] = &$profilePicUrl;
    }

    $sql = "UPDATE users SET " . implode(", ", $sqlFields) . " WHERE id = ?";
    $bindTypes .= "i";
    $bindParams[] = &$userId;

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    call_user_func_array([$stmt, 'bind_param'], array_merge([$bindTypes], $bindParams));

    if ($stmt->execute()) {
        // Update the session variable with the new profile picture URL if it was changed
        if ($shouldUpdateProfilePic) {
            $_SESSION['profile_pic'] = $profilePicUrl;

            // Update the profile picture in all the user's posts only if profile_pic was changed
            $updatePostsStmt = $conn->prepare("UPDATE posts SET profile_pic = ? WHERE user_id = ?");
            $updatePostsStmt->bind_param("si", $profilePicUrl, $userId);
            $updatePostsStmt->execute();
            $updatePostsStmt->close();
        }
        
        // Only set success message if changes were made
        if ($changesMade) {
            $_SESSION['profile_update_success'] = "Votre profil a été mis à jour avec succès !";
        }
        header("Location: profile.php");
        exit();
    } else {
        $errors[] = "Database update failed: " . $stmt->error; // Add more specific error
        $_SESSION['profile_errors'] = $errors;
        header("Location: edit_profile.php");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>