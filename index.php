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
            $_SESSION['login_error'] = "Nom d'utilisateur ou mot de passe incorrects.";
        }
    } else {
        // User not found
        $_SESSION['login_error'] = "Nom d'utilisateur ou mot de passe incorrects.";
    }

    $stmt->close();
}

$conn->close();

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
      transform: translateX(5px);
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
      text-shadow: 0 0 8px rgba(76, 175, 80, 0.8);
    }
    img {
      display: block;
      margin: 0 auto 20px;
      height: 100px;
      transform: translateX(10px);
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
      backdrop-filter: blur(5px);
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
      margin: 20px 0;
      color: #333;
      line-height: 1.5;
    }

    .alert-content button {
      padding: 12px 30px;
      background: linear-gradient(45deg, #4CAF50, #45a049);
      color: #fff;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .alert-content button:hover {
      background: linear-gradient(45deg, #45a049, #4CAF50);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .alert-content button:active {
      transform: translateY(0);
    }

    .alert-content i {
      font-size: 50px;
      margin-bottom: 20px;
      display: block;
    }

    /* New: Success Alert Style */
    .success-alert .alert-content::before {
      background: linear-gradient(90deg, #4CAF50, #45a049); /* Green gradient for success */
    }

    .success-alert .alert-content i {
      color: #4CAF50; /* Green icon */
    }

    .success-alert .alert-content button {
      background: linear-gradient(45deg, #4CAF50, #45a049);
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    }

    .success-alert .alert-content button:hover {
      background: linear-gradient(45deg, #45a049, #4CAF50);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

  </style>
</head>
<body>
  <div class="wrapper">
    <?php /* if (isset($_SESSION['login_error'])): ?>
      <div style="color: red; text-align: center; margin-bottom: 10px;"><?= htmlspecialchars($_SESSION['login_error']); ?></div>
      <?php unset($_SESSION['login_error']); ?>
    <?php endif; */ ?>
  
      <form action="index.php" method="POST">
        <img src="Images/logoinv.png" alt="Logo">
        <h1>Login</h1>
        <div class="input-box">
          <input type="text" name="username_or_email" placeholder="Nom d'utilisateur ou E-mail" required>
          <i class='bx bxs-user'></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Mot de passe" required>
          <i class='bx bxs-lock-alt'></i>
        </div>

        <button type="submit" class="btn">Login</button>
        <div class="register-link">
          <p>Vous n'avez pas de compte ? <a href="signup.php">S'inscrire</a></p>
        </div>
      </form>
    </div>

  <!-- Custom Alert Modal -->
  <div id="customAlert" class="custom-alert">
    <div class="alert-content">
      <i class='bx bxs-error-circle'></i> <!-- Default to error icon -->
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

    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOMContentLoaded fired in index.php');
      <?php
      if (isset($_SESSION['registration_success'])) {
          $username = isset($_SESSION['registered_username']) ? $_SESSION['registered_username'] : '';
          $success_message = "Bienvenue " . htmlspecialchars($username) . "! Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter avec vos identifiants.";
          echo "console.log('Attempting to show success alert with message: ' + " . json_encode($success_message) . ");";
          echo "showAlert(" . json_encode($success_message) . ", 'success');";
          unset($_SESSION['registration_success']);
          unset($_SESSION['registered_username']);
      }
      ?>

      <?php if (isset($_SESSION['login_error'])): ?>
        showAlert(<?= json_encode($_SESSION['login_error']); ?>, 'error');
        <?php unset($_SESSION['login_error']); ?>
      <?php endif; ?>

      // Check for account deletion success message from localStorage
      const accountDeletedMessage = localStorage.getItem('accountDeleted');
      if (accountDeletedMessage) {
        showAlert(accountDeletedMessage, 'success');
        localStorage.removeItem('accountDeleted'); // Clear the message after displaying
      }
    });
  </script>
</body>
</html>