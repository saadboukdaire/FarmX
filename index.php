<?php
session_start();

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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Authentification FarmX</title>
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
      width: 420px;
      background-color: rgba(14, 14, 14, 0.699);
      border: 2px solid rgba(255, 255, 255, .2);
      color: #fff;
      border-radius: 12px;
      padding: 30px 40px;
      animation: fadeIn 0.8s ease-in-out;
    }
    .wrapper h1 {
      font-size: 36px;
      text-align: center;
    }
    .wrapper .input-box {
      position: relative;
      width: 100%;
      height: 50px;
      margin: 30px 0;
    }
    .input-box input {
      width: 100%;
      height: 100%;
      background: transparent;
      border: none;
      outline: none;
      border: 2px solid rgba(255, 255, 255, .2);
      border-radius: 40px;
      font-size: 16px;
      color: #fff;
      padding: 20px 45px 20px 20px;
    }
    .input-box input::placeholder {
      color: #fff;
    }
    .input-box i {
      position: absolute;
      right: 20px;
      top: 30%;
      transform: translate(-50%);
      font-size: 20px;
    }
    .wrapper .remember-forgot {
      display: flex;
      justify-content: space-between;
      font-size: 14.5px;
      margin: -15px 0 15px;
    }
  
    .wrapper .btn {
      width: 100%;
      height: 45px;
      background: #fff;
      border: none;
      outline: none;
      border-radius: 40px;
      box-shadow: 0 0 10px rgba(0, 0, 0, .1);
      cursor: pointer;
      font-size: 16px;
      color: #333;
      font-weight: 600;
    }
    .wrapper .btn:hover {
      width: 100%;
      height: 45px;
      background: #d1d1d1;
      border: none;
      outline: none;
      border-radius: 40px;
      box-shadow: 0 0 10px rgba(0, 0, 0, .1);
      cursor: pointer;
      font-size: 16px;
      color: #333;
      font-weight: 600;
    }
    .wrapper .register-link {
      font-size: 14.5px;
      text-align: center;
      margin: 20px 0 15px;
    }
    .register-link p a {
      color: #fff;
      text-decoration: none;
      font-weight: 600;
    }
    .register-link p a:hover {
      text-decoration: underline;
    }
    img {
      margin-left: 110px;
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

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
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
      margin-bottom: 25px;
      color: #333;
      line-height: 1.6;
      font-weight: 500;
    }

    .alert-content button {
      padding: 12px 35px;
      background: linear-gradient(135deg, #4CAF50, #45a049);
      color: #fff;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .alert-content button:hover {
      background: linear-gradient(135deg, #45a049, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .alert-content button:active {
      transform: translateY(0);
    }

    /* Success Alert Specific Styles */
    .success-alert .alert-content {
      background-color: #f8f9fa;
      border: 2px solid #4CAF50;
    }

    .success-alert .alert-content::before {
      background: linear-gradient(90deg, #4CAF50, #45a049);
    }

    .success-alert .alert-content p {
      color: #2e7d32;
      font-weight: 500;
    }

    .success-alert .alert-content button {
      background: linear-gradient(135deg, #4CAF50, #45a049);
    }

    /* Error Alert Specific Styles */
    .error-alert .alert-content {
      background-color: #f8f9fa;
      border: 2px solid #f44336;
    }

    .error-alert .alert-content::before {
      background: linear-gradient(90deg, #f44336, #d32f2f);
    }

    .error-alert .alert-content p {
      color: #d32f2f;
      font-weight: 500;
    }

    .error-alert .alert-content button {
      background: linear-gradient(135deg, #f44336, #d32f2f);
    }

    /* Alert Icon Styles */
    .alert-content i {
      font-size: 48px;
      margin-bottom: 20px;
      display: block;
    }

    .success-alert .alert-content i {
      color: #4CAF50;
    }

    .error-alert .alert-content i {
      color: #f44336;
    }
  </style>
</head>
<body>
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
        <p>Don't have an account? <a href="signup.php">Register</a></p>
      </div>
    </form>
  </div>

  <!-- Custom Alert Modal -->
  <div id="customAlert" class="custom-alert">
    <div class="alert-content">
      <i class='bx bxs-check-circle'></i>
      <p id="alertMessage"></p>
      <button id="alertCloseButton">OK</button>
    </div>
  </div>

  <script>
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

    // Show alerts on page load
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (!empty($success_message)): ?>
        showAlert(<?php echo json_encode($success_message); ?>, 'success');
      <?php endif; ?>
      
      <?php if (isset($_SESSION['login_error'])): ?>
        showAlert(<?php echo json_encode($_SESSION['login_error']); ?>, 'error');
        <?php unset($_SESSION['login_error']); ?>
      <?php endif; ?>
    });
  </script>
</body>
</html>