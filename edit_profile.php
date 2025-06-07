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
$sql = "SELECT username, email, phone, profile_pic, bio, gender FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $profilePic, $bio, $gender);
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
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 15px;
        }

        /* Edit Profile Container */
        .profile-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-container h2 {
            margin-bottom: 20px;
            border-bottom: 2px solid #3e8e41;
            text-align: center;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 0;
            background: linear-gradient(to right, #3e8e41, #2d682f);
            border-radius: 8px;
            display: inline-block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-container form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .profile-container label {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .profile-container input[type="email"],
        .profile-container input[type="tel"],
        .profile-container input[type="text"],
        .profile-container input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .profile-container input[type="email"]:focus,
        .profile-container input[type="tel"]:focus,
        .profile-container input[type="text"]:focus,
        .profile-container input[type="file"]:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        .profile-container button[type="submit"] {
            padding: 10px 20px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 5px;
        }

        .profile-container button[type="submit"]:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
        }

        /* Back Button Styles */
        .back-button {
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }

        .back-button a {
            display: block;
            width: 100%;
            padding: 10px 20px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
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
            padding: 8px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .profile-container input[type="file"]::file-selector-button {
            padding: 6px 12px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 13px;
        }

        .profile-container input[type="file"]::file-selector-button:hover {
            background-color: #2d682f;
        }

        /* Bio Textarea Styles */
        .profile-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
        }

        .profile-container textarea:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        /* Gender Select Styles */
        .profile-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-color: white;
            cursor: pointer;
        }

        .profile-container select:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        /* Character Count Styles */
        .char-count {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 3px;
        }

        /* Form Group Styles */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 15px;
            }

            .profile-container h2 {
                font-size: 20px;
                padding: 12px 0;
            }

            .profile-container input[type="email"],
            .profile-container input[type="tel"],
            .profile-container input[type="text"],
            .profile-container input[type="file"],
            .profile-container textarea {
                padding: 8px;
                font-size: 13px;
            }

            .profile-container label {
                font-size: 13px;
            }

            .profile-container button[type="submit"],
            .back-button a {
                padding: 8px 15px;
                font-size: 14px;
            }
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

            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                </select>
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