<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the very top
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

// Fetch user data
$userId = $_SESSION['user_id']; // Use 'user_id' instead of 'id'
$sql = "SELECT username, email, phone, profile_pic, bio FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $profilePic, $bio);
$stmt->fetch();
$stmt->close();

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Edit Profile</title>
    <link rel="icon" href="Images/logo.jpg">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Verdana, sans-serif;
        }
        html, body {
    overflow: hidden; /* Prevent scrolling */
    height: 100%; /* Ensure the body takes up the full height of the viewport */
    margin: 0; /* Remove default margin */
    padding: 0; /* Remove default padding */
}
        body {
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Edit Profile Container */
        .profile-container {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-container h2 {
           
            margin-bottom: 25px;
            border-bottom: 2px solid #3e8e41;
            text-align: center;
            color: white; /* White text for better contrast */
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 27px 0;
            background: linear-gradient(to right, #3e8e41, #2d682f); /* FarmX green gradient */
            border-radius: 8px;
            display: inline-block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-container form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .profile-container label {
            font-size: 16px;
            color: #333;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .profile-container input[type="email"],
        .profile-container input[type="tel"],
        .profile-container input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .profile-container input[type="email"]:focus,
        .profile-container input[type="tel"]:focus,
        .profile-container input[type="file"]:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        .profile-container button[type="submit"] {
            padding: 12px 20px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .profile-container button[type="submit"]:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
        }

        /* Back Button Styles */
        .back-button {
            width: 100%;
            text-align: center;
            margin-top: 15px;
        }

        .back-button a {
            display: block;
            width: 100%;
            padding: 12px 20px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
        }

        .back-button a:hover {
            background-color: #cc0000;
            transform: translateY(-2px);
        }

        /* File Input Customization */
        .profile-container input[type="file"] {
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .profile-container input[type="file"]::file-selector-button {
            padding: 8px 12px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .profile-container input[type="file"]::file-selector-button:hover {
            background-color: #2d682f;
        }
        /* Style for the username input field */
.profile-container input[type="text"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.profile-container input[type="text"]:focus {
    border-color: #3e8e41;
    box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
}

/* Optional: Add a subtle placeholder style */
.profile-container input[type="text"]::placeholder {
    color: #999;
    font-style: italic;
}

        /* Bio Textarea Styles */
        .profile-container textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .profile-container textarea:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        /* Character Count Styles */
        .char-count {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }

        /* Form Group Styles */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
    </style>
</head>
<body>
    <!-- Edit Profile Form -->
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="profile-pic-upload">Profile Picture:</label>
                <input type="file" id="profile-pic-upload" name="profile-pic-upload">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
            </div>

            <div class="form-group">
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" maxlength="500" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
                <div class="char-count"><span id="bio-char-count">0</span>/500 characters</div>
            </div>

            <button type="submit">Save Changes</button>
        </form>
        <!-- Back Button -->
        <div class="back-button">
            <a href="profile.php">Back to Profile</a>
        </div>
    </div>

    <script>
        // Character count for bio
        const bioTextarea = document.getElementById('bio');
        const charCount = document.getElementById('bio-char-count');
        
        // Set initial character count
        charCount.textContent = bioTextarea.value.length;
        
        bioTextarea.addEventListener('input', function() {
            const remaining = this.value.length;
            charCount.textContent = remaining;
        });
    </script>
</body>
</html>