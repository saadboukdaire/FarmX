<?php
session_start();
require_once 'database/db_connect.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    // Query the database to find the user
    // Select user_type along with other user data
    $sql = "SELECT id, username, email, password, profile_pic, user_type, user_tag FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct, log the user in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_tag'] = $user['user_tag']; // Store user_tag in session

            // Redirect based on user type
            if ($user['user_type'] === 'farmer') {
                header("Location: main.php");
            } else { // Assuming 'user' is the other type
                header("Location: main.php");
            }
            exit();
        } else {
            // Password is incorrect
            $_SESSION['login_error'] = "Incorrect password.";
        }
    } else {
        // User not found
        $_SESSION['login_error'] = "Username or email not found.";
    }

    $stmt->close();
}

$conn->close();

// Store registration success message if it exists
$success_message = '';
if (isset($_SESSION['registration_success'])) {
    $username = isset($_SESSION['registered_username']) ? $_SESSION['registered_username'] : '';
    $success_message = "Welcome " . htmlspecialchars($username) . "! Your account has been created successfully. You can now log in with your credentials.";
    unset($_SESSION['registration_success']);
    unset($_SESSION['registered_username']);
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FarmX - Authentification</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="icon" href="Images/logo.jpg" class="icon1">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: url(Images/bg.jpg) no-repeat;
      background-size: cover;
      background-position: center;
    }
    .wrapper {
      width: 480px;
      background: rgba(14, 14, 14, 0.85);
      border: 2px solid rgba(255, 255, 255, .1);
      color: #fff;
      border-radius: 16px;
      padding: 35px 45px;
      animation: fadeIn 0.8s ease-in-out;
    }
    .wrapper h1 {
      font-size: 28px;
      text-align: center;
      margin-bottom: 15px;
      background: linear-gradient(45deg, #4CAF50, #45a049);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .wrapper .input-box {
      position: relative;
      width: 100%;
      height: 45px;
      margin: 12px 0;
    }
    .input-box input {
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid rgba(255, 255, 255, .1);
      outline: none;
      border-radius: 40px;
      font-size: 15px;
      color: #fff;
      padding: 15px 45px 15px 20px;
      transition: all 0.3s ease;
    }
    .input-box input:focus {
      border-color: #4CAF50;
      box-shadow: 0 0 10px rgba(76, 175, 80, 0.2);
      background: rgba(255, 255, 255, 0.08);
    }
    .input-box input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }
    .input-box i {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: rgba(255, 255, 255, 0.5);
      transition: all 0.3s ease;
    }
    .input-box input:focus + i {
      color: #4CAF50;
    }
    .wrapper .btn {
      width: 100%;
      height: 45px;
      background: #4CAF50;
      border: none;
      outline: none;
      border-radius: 40px;
      box-shadow: 0 0 10px rgba(76, 175, 80, 0.3);
      cursor: pointer;
      font-size: 16px;
      color: #fff;
      font-weight: 600;
      margin: 15px 0;
      transition: all 0.3s ease;
    }
    .wrapper .btn:hover {
      background: #45a049;
      box-shadow: 0 0 20px rgba(76, 175, 80, 0.6);
      transform: translateY(-2px);
    }
    .wrapper .btn:active {
      transform: translateY(0);
    }
    .wrapper .register-link {
      font-size: 14px;
      text-align: center;
      margin: 12px 0 5px;
    }
    .register-link p a {
      color: #4CAF50;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .register-link p a:hover {
      color: #45a049;
      text-decoration: underline;
    }
    img {
      display: block;
      margin: 0 auto 20px;
      height: 100px;
    }
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Enhanced Alert Modal Styles */
    .custom-alert {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      justify-content: center;
      align-items: center;
      z-index: 1000;
      animation: fadeIn 0.3s ease;
    }

    .alert-content {
      background-color: #fff;
      padding: 35px;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
      text-align: center;
      max-width: 400px;
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
      background: linear-gradient(90deg, #4CAF50, #45a049);
    }

    @keyframes scaleIn {
      from { transform: scale(0.9); }
      to { transform: scale(1); }
    }

    .alert-content p {
      font-size: 18px;
      color: #333;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    .alert-content .icon {
      font-size: 50px;
      margin-bottom: 15px;
    }

    .alert-content .bx-check-circle {
      color: #4CAF50;
    }

    .alert-content .bx-error-circle {
      color: #f44336;
    }

    .alert-content button {
      background-color: #4CAF50;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      font-size: 16px;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .alert-content button:hover {
      background-color: #45a049;
      transform: translateY(-2px);
    }

    .alert-content button:active {
      transform: translateY(0);
    }

    .custom-alert.show {
      display: flex;
    }

    .custom-alert.success .alert-content::before {
      background: linear-gradient(90deg, #4CAF50, #45a049);
    }

    .custom-alert.error .alert-content::before {
      background: linear-gradient(90deg, #f44336, #d32f2f);
    }

    .custom-alert.success .icon {
      color: #4CAF50;
    }

    .custom-alert.error .icon {
      color: #f44336;
    }

    /* Login Error Message Styles */
    .error-message {
      color: #ff6347; /* Tomato */
      background-color: rgba(255, 99, 71, 0.1);
      border: 1px solid #ff6347;
      padding: 10px;
      border-radius: 8px;
      margin-top: 10px;
      font-size: 14px;
      text-align: center;
      animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

  </style>
</head>
<body>
  <div class="custom-alert" id="customAlertBox">
    <div class="alert-content">
      <i class="icon bx" id="alertIcon"></i>
      <p id="customAlertMessage"></p>
      <button id="alertCloseButton">OK</button>
    </div>
  </div>

  <div class="wrapper">
    <form action="" method="POST">
      <img src="Images/logoinv.png" height="100px">
      <h1>Welcome back!</h1>
      <div class="input-box">
        <input type="text" name="username_or_email" placeholder="Username or Email" required>
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" placeholder="Password" required>
        <i class='bx bxs-lock-alt'></i>
      </div>
      <button type="submit" class="btn">Login</button>
      <div class="register-link">
        <p><span>Don't have an account?</span> <a href="signup.php">Register</a></p>
      </div>
    </form>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($success_message)): ?>
            showCustomAlert("<?php echo $success_message; ?>", true);
        <?php endif; ?>

        // Automatically hide success message after 5 seconds
        if (<?php echo json_encode(!empty($success_message)); ?>) {
            setTimeout(function() {
                hideCustomAlert();
            }, 5000);
        }
    });

    function showCustomAlert(message, isSuccess) {
        var alertBox = document.getElementById("customAlertBox");
        var alertMessage = document.getElementById("customAlertMessage");
        alertMessage.innerHTML = message;
        if (isSuccess) {
            alertBox.classList.remove('error');
            alertBox.classList.add('success');
        } else {
            alertBox.classList.remove('success');
            alertBox.classList.add('error');
        }
        alertBox.style.display = "flex";
        setTimeout(function() {
            alertBox.classList.add('show');
        }, 10);
    }

    function hideCustomAlert() {
        var alertBox = document.getElementById("customAlertBox");
        alertBox.classList.remove('show');
        setTimeout(function() {
            alertBox.style.display = "none";
        }, 300); // Match CSS transition duration
    }

    // Hide error message after 5 seconds if it exists
    <?php if (isset($_SESSION['login_error'])): ?>
        document.addEventListener("DOMContentLoaded", function() {
            showCustomAlert("<?php echo htmlspecialchars($_SESSION['login_error']); ?>", false);
            setTimeout(hideCustomAlert, 5000);
        });
        <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>
  </script>
</body>
</html>