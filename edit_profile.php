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
            overflow: hidden;
        }

        /* Edit Profile Container */
        .profile-container {
            max-width: 800px;
            width: 100%;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .profile-container h2 {
            grid-column: 1 / -1;
            margin-bottom: 10px;
            border-bottom: 2px solid #3e8e41;
            text-align: center;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 12px 0;
            background: linear-gradient(to right, #3e8e41, #2d682f);
            border-radius: 8px;
            display: inline-block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-container form {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .profile-container textarea {
            min-height: 80px;
        }

        .profile-container button[type="submit"],
        .back-button {
            grid-column: 1 / -1;
            margin-top: 5px;
        }

        .back-button {
            margin-top: 0;
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
        }

        .profile-container button[type="submit"]:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
        }

        /* Back Button Styles */
        .back-button {
            width: 100%;
            text-align: center;
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

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
                max-width: 450px;
            }

            .profile-container form {
                grid-template-columns: 1fr;
            }
        }

        /* Add validation styles */
        .error-message {
            color: #ff4d4d;
            font-size: 12px;
            margin-top: 2px;
            display: none;
        }

        .input-error {
            border-color: #ff4d4d !important;
            box-shadow: 0 0 5px rgba(255, 77, 77, 0.3) !important;
        }

        .server-error {
            background-color: #fff2f2;
            border: 1px solid #ff4d4d;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #ff4d4d;
        }

        .server-error ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }

        .server-error li {
            margin: 5px 0;
        }

        /* Phone input group styles */
        .phone-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .phone-prefix {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            color: #333;
            font-size: 14px;
            user-select: none;
            min-width: 60px;
            text-align: center;
        }

        .phone-input {
            flex: 1;
            height: 38px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
                font-size: 14px;
            color: #333;
            padding: 10px;
            transition: all 0.3s ease;
        }

        .phone-input:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        .phone-input::placeholder {
            color: #999;
        }

        /* Custom Alert Styles */
        .custom-alert {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .alert-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 350px;
            width: 90%;
            transform: scale(0.9);
            animation: scaleIn 0.3s ease forwards;
            position: relative;
            overflow: hidden;
        }

        .alert-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #ff4d4d, #cc0000);
        }

        @keyframes scaleIn {
            from { transform: scale(0.9); }
            to { transform: scale(1); }
        }

        .alert-content p {
            font-size: 16px;
            margin: 20px 0;
            color: #333;
            line-height: 1.5;
            font-weight: 500;
        }

        .alert-content button {
            padding: 12px 30px;
            background: linear-gradient(45deg, #ff4d4d, #cc0000);
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 77, 77, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .alert-content button:hover {
            background: linear-gradient(45deg, #cc0000, #ff4d4d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 77, 77, 0.4);
        }

        .alert-content button:active {
            transform: translateY(0);
        }

        .alert-content i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            color: #ff4d4d;
        }

        .error-alert .alert-content i {
            color: #ff4d4d;
        }
    </style>
</head>
<body>
    <!-- Edit Profile Form -->
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <?php if (isset($_SESSION['profile_errors']) && !empty($_SESSION['profile_errors'])): ?>
            <div class="server-error">
                <strong>Please fix the following errors:</strong>
                <ul>
                    <?php foreach ($_SESSION['profile_errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['profile_errors']); ?>
        <?php endif; ?>
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required 
                       pattern="^[a-zA-Z0-9_]{3,20}$" 
                       title="Username must be 3-20 characters long and can only contain letters, numbers, and underscores">
                <div class="error-message" id="username-error">Username must be 3-20 characters long and can only contain letters, numbers, and underscores</div>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                       pattern="^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|icloud\.com)$"
                       title="Please enter a valid email address with a supported domain (gmail.com, yahoo.com, hotmail.com, outlook.com, or icloud.com)">
                <div class="error-message" id="email-error">Please enter a valid email address with a supported domain (gmail.com, yahoo.com, hotmail.com, outlook.com, or icloud.com)</div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <div class="phone-input-group">
                    <span class="phone-prefix">+212</span>
                    <input type="tel" id="phone" name="phone" class="phone-input" 
                           placeholder="6XXXXXXXX or 7XXXXXXXX" 
                           value="<?php echo substr(htmlspecialchars($phone), -9); ?>" 
                           maxlength="9" 
                           pattern="^[67][0-9]{8}$"
                           title="Please enter a valid Moroccan phone number starting with 6 or 7"
                           oninput="validatePhoneInput(this)">
            </div>
                <div class="error-message" id="phone-error">Please enter a valid Moroccan phone number starting with 6 or 7</div>
            </div>

            <div class="form-group">
                <label for="gender">Gender:</label>
                <?php if (!empty($gender)): ?>
                    <input type="text" id="gender" name="gender" value="<?php echo htmlspecialchars($gender); ?>" readonly>
                <?php else: ?>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="profile-pic-upload">Profile Picture:</label>
                <input type="file" id="profile-pic-upload" name="profile-pic-upload">
            </div>

            <div class="form-group full-width">
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

    <!-- Custom Alert Modal -->
    <div id="customAlert" class="custom-alert">
        <div class="alert-content">
            <i class='bx bxs-error-circle'></i>
            <p id="alertMessage"></p>
            <button id="alertCloseButton">OK</button>
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

        // Form validation
        const form = document.querySelector('form');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const usernameError = document.getElementById('username-error');
        const emailError = document.getElementById('email-error');
        const phoneError = document.getElementById('phone-error');

        // Validation patterns
        const patterns = {
            username: /^[a-zA-Z0-9_]{3,20}$/,
            email: /^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|icloud\.com)$/,
            phone: /^[67][0-9]{8}$/
        };

        // Validation functions
        function validateUsername() {
            const value = usernameInput.value.trim();
            if (!patterns.username.test(value)) {
                usernameInput.classList.add('input-error');
                usernameError.style.display = 'block';
                return false;
            }
            usernameInput.classList.remove('input-error');
            usernameError.style.display = 'none';
            return true;
        }

        function validateEmail() {
            const value = emailInput.value.trim();
            if (!patterns.email.test(value)) {
                emailInput.classList.add('input-error');
                emailError.style.display = 'block';
                return false;
            }
            emailInput.classList.remove('input-error');
            emailError.style.display = 'none';
            return true;
        }

        function validatePhone() {
            const value = phoneInput.value.trim();
            if (!patterns.phone.test(value)) {
                phoneInput.classList.add('input-error');
                phoneError.style.display = 'block';
                return false;
            }
            phoneInput.classList.remove('input-error');
            phoneError.style.display = 'none';
            return true;
        }

        // Add event listeners for real-time validation
        usernameInput.addEventListener('input', validateUsername);
        emailInput.addEventListener('input', validateEmail);
        phoneInput.addEventListener('input', validatePhone);

        // Function to show custom alert
        function showAlert(message, type = 'error') {
            const alertModal = document.getElementById("customAlert");
            const alertMessage = document.getElementById("alertMessage");
            const alertIcon = alertModal.querySelector('i');
            
            // Remove existing classes
            alertModal.classList.remove('success-alert', 'error-alert');
            // Add appropriate class
            alertModal.classList.add(type + '-alert');
            
            // Update icon based on type
            alertIcon.className = type === 'success' ? 'bx bxs-check-circle' : 'bx bxs-error-circle';
            
            alertMessage.textContent = message;
            alertModal.style.display = "flex";

            const closeButton = document.getElementById("alertCloseButton");
            closeButton.onclick = function() {
                alertModal.style.display = "none";
            };

            alertModal.onclick = function(event) {
                if (event.target === alertModal) {
                    alertModal.style.display = "none";
                }
            };
        }

        // Form validation
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all fields
            const isUsernameValid = validateUsername();
            const isEmailValid = validateEmail();
            const isPhoneValid = validatePhone();
            
            if (!isUsernameValid || !isEmailValid || !isPhoneValid) {
                showAlert("Please fix the errors in the form before submitting.");
                return;
            }
            // Prepend '+212' to phone input value before submitting
            let phoneInputValue = phoneInput.value.trim();
            if (!phoneInputValue.startsWith('+212')) {
                phoneInput.value = '+212' + phoneInputValue;
            }
            // If all validations pass, submit the form
            this.submit();
        });

        // Function to validate phone number input
        function validatePhoneInput(input) {
            // Remove any non-digit characters
            input.value = input.value.replace(/\D/g, '');
            
            // Ensure it starts with 6 or 7
            if (input.value.length > 0 && !/^[67]/.test(input.value)) {
                input.value = input.value.substring(1);
            }
            
            // Limit to 9 digits (6/7 + 8 digits)
            if (input.value.length > 9) {
                input.value = input.value.slice(0, 9);
            }

            // Validate the input
            validatePhone();
        }

        // Update phone validation pattern
        patterns.phone = /^[67][0-9]{8}$/;

        function validatePhone() {
            const value = phoneInput.value.trim();
            if (!patterns.phone.test(value)) {
                phoneInput.classList.add('input-error');
                phoneError.style.display = 'block';
                return false;
            }
            phoneInput.classList.remove('input-error');
            phoneError.style.display = 'none';
            return true;
        }

        // Add event listener to phone input
        document.getElementById('phone').addEventListener('input', function(e) {
            validatePhoneInput(this);
        });

        // Initialize counter on page load
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.querySelector('.phone-input');
            if (phoneInput) {
                validatePhoneInput(phoneInput);
            }
        });
    </script>
</body>
</html>