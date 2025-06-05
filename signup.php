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
      opacity: 0;
      animation: fadeInBody 0.80s ease-in-out forwards;
    }

    @keyframes fadeInBody {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    .wrapper {
      width: 420px;
      background-color: rgba(14, 14, 14, 0.699);
      border: 2px solid rgba(255, 255, 255, .2);
      color: #fff;
      border-radius: 12px;
      padding: 30px 40px;
      position: relative;
      margin: 20px auto;
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
    .input-box select {
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
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
    }
    .input-box select option {
      background-color: #1a1a1a;
      color: #fff;
      padding: 10px;
    }
    .input-box select:focus {
      border-color: rgba(255, 255, 255, .5);
    }
    .input-box select::-ms-expand {
      display: none;
    }
    .wrapper .remember-forgot {
      display: flex;
      justify-content: space-between;
      font-size: 14.5px;
      margin: 0 0 15px;
      padding: 0 20px;
    }
    .remember-forgot label input {
      accent-color: #fff;
      margin-right: 3px;
    }
    .remember-forgot a {
      color: #fff;
      text-decoration: none;
    }
    .remember-forgot a:hover {
      text-decoration: underline;
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

    /* User type selection styles */
    .user-type-selection {
      margin: 25px 0;
      color: #fff;
      padding: 0 20px;
    }

    .user-type-label {
      display: block;
      font-size: 1rem;
      margin-bottom: 12px;
      font-weight: 500;
      color: #fff;
    }

    .radio-group {
      display: flex;
      gap: 15px;
      justify-content: space-around;
      width: 100%;
    }

    .radio-group input[type="radio"] {
      display: none;
    }

    .radio-group label {
      display: flex;
      align-items: center;
      background: rgba(255, 255, 255, 0.08);
      border: 2px solid rgba(255, 255, 255, .1);
      border-radius: 30px;
      padding: 12px 25px;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      flex-grow: 1;
      justify-content: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      color: #fff;
    }

    .radio-group input[type="radio"]:checked + label {
      background-color: rgba(76, 175, 80, 0.3);
      border-color: #4CAF50;
      font-weight: 600;
      color: #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .radio-group label:hover {
      background-color: rgba(255, 255, 255, .15);
      color: #fff;
    }

    .radio-group input[type="radio"]:checked + label:hover {
      background-color: rgba(76, 175, 80, 0.45);
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <form id="signupForm" action="signup.php" method="POST">
      <h1>Create an account</h1>
      <div class="input-box">
        <input type="text" name="username" id="username" placeholder="Username" required>
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="email" name="email" id="email" placeholder="E-mail" required>
        <i class='bx bxs-envelope'></i>
      </div>
      <div class="input-box">
        <input type="tel" id="phone" name="phone" placeholder="Phone Number" maxlength="10" required>
        <i class='bx bxs-phone'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" id="password" placeholder="Password" required>
        <i class='bx bxs-lock-alt'></i>
      </div>
      <div class="input-box">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
        <i class='bx bxs-lock-alt'></i>
      </div>

      <!-- User type selection -->
      <div class="user-type-selection">
        <label class="user-type-label">Account Type:</label>
        <div class="radio-group">
          <input type="radio" id="farmer" name="user_type" value="farmer" required>
          <label for="farmer"> Farmer</label>

          <input type="radio" id="user" name="user_type" value="user" required>
          <label for="user"> Consumer</label>
        </div>
      </div>

      <button type="submit" class="btn">Register</button>
      <div class="register-link">
        <p>Have an account? <a href="index.php">Login</a></p>
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

    // Function to validate phone number input
    function validatePhoneInput(input) {
      input.value = input.value.replace(/\D/g, '');
    }

    // Add event listener to phone input
    document.getElementById('phone').addEventListener('input', function(e) {
      validatePhoneInput(this);
    });

    // Form submission handler
    document.getElementById('signupForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const username = document.getElementById('username').value.trim();
      const email = document.getElementById('email').value.trim();
      const phone = document.getElementById('phone').value.trim();
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;

      // Get selected user type
      const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
      let userType = '';
      for (const radio of userTypeRadios) {
        if (radio.checked) {
          userType = radio.value;
          break;
        }
      }

      // Validate user type selection
      if (!userType) {
        showAlert("Please select an account type (Farmer or Regular User)", 'error');
        return;
      }

      // Validate username (only letters, numbers, and common characters)
      const usernameRegex = /^[a-zA-Z0-9._-]+$/;
      if (!usernameRegex.test(username)) {
        showAlert("Username can only contain:\n• Letters (a-z, A-Z)\n• Numbers (0-9)\n• Dots (.)\n• Underscores (_)\n• Hyphens (-)", 'error');
        return;
      }

      // Validate email domain
      const validDomains = ["gmail.com", "yahoo.com", "outlook.com", "hotmail.com", "icloud.com"];
      const emailDomain = email.split("@")[1];
      if (!validDomains.includes(emailDomain)) {
        showAlert("Please use a valid email address from: gmail.com, yahoo.com, outlook.com, hotmail.com, or icloud.com", 'error');
        return;
      }

      // Validate phone number
      const phoneRegex = /^0\d{9}$/;
      if (!phoneRegex.test(phone)) {
        showAlert("Phone number must be 10 digits and start with 0", 'error');
        return;
      }

      // Validate password length
      if (password.length < 8) {
        showAlert("Password must be at least 8 characters long", 'error');
        return;
      }

      // Validate password match
      if (password !== confirmPassword) {
        showAlert("Passwords do not match", 'error');
        return;
      }

      // If all validations pass, submit the form
      this.submit();
    });
  </script>

  <?php
  // Enable error reporting
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  // Start the session at the very top
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

  // Handle form submission
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Get user type from radio button
    $user_type = $_POST['user_type'] ?? '';

    // Basic server-side validation for user type
    if (!in_array($user_type, ['farmer', 'user'])) {
      // Handle invalid user type - this shouldn't happen with client-side validation,
      // but is good practice for security.
      // You might log this error or show a generic error message.
      echo "<script>showAlert('Invalid account type selected', 'error');</script>";
      exit();
    }

    // Determine user tag based on user type
    $user_tag = '';
    if ($user_type === 'farmer') {
      $user_tag = 'FarmX Producer';
    } else { // Assuming 'user' is the other type
      $user_tag = 'FarmX Member';
    }

    // Check for duplicate username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      echo "<script>showAlert('Username already exists', 'error');</script>";
      exit();
    }
    $stmt->close();

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      echo "<script>showAlert('Email already exists', 'error');</script>";
      exit();
    }
    $stmt->close();

    // Check for duplicate phone
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      echo "<script>showAlert('Phone number already exists', 'error');</script>";
      exit();
    }
    $stmt->close();

    // If no duplicates found, proceed with registration
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, user_type, user_tag) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $email, $phone, $hashed_password, $user_type, $user_tag);

    if ($stmt->execute()) {
      session_start();
      $_SESSION['registration_success'] = true;
      $_SESSION['registered_username'] = $username;
      // Store user_type and user_tag in session upon successful registration
      $_SESSION['user_type'] = $user_type;
      $_SESSION['user_tag'] = $user_tag;
      echo "<script>window.location.href = 'index.php';</script>";
    } else {
      echo "<script>showAlert('Error creating account: " . $stmt->error . "', 'error');</script>";
    }

    $stmt->close();
    $conn->close();
  }
  ?>
</body>
</html>