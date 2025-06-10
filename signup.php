<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session at the very top
session_start();

require_once 'includes/translations.php';
require_once 'database/db_connect.php';

$translations = new Translations($db);
?>
<!DOCTYPE html>
<html lang="<?php echo $translations->getCurrentLanguage(); ?>">
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
      opacity: 0;
      animation: fadeInBody 0.80s ease-in-out forwards;
    }

    @keyframes fadeInBody {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
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

    .phone-input-group {
      position: relative;
      width: 100%;
      height: 45px;
      margin: 15px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .phone-prefix {
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid rgba(255, 255, 255, .1);
      border-radius: 40px;
      color: #fff;
      padding: 0 15px;
      height: 100%;
      display: flex;
      align-items: center;
      font-size: 15px;
      user-select: none;
      min-width: 65px;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .phone-input {
      flex: 1;
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

    .phone-input:focus {
      border-color: #4CAF50;
      box-shadow: 0 0 10px rgba(76, 175, 80, 0.2);
      background: rgba(255, 255, 255, 0.08);
    }

    .phone-input-group i {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: rgba(255, 255, 255, 0.5);
      transition: all 0.3s ease;
    }

    .phone-input:focus + i {
      color: #4CAF50;
    }

    .user-type-selection {
      margin: 12px 0;
      color: #fff;
    }

    .user-type-selection.optional-field {
      margin: 8px 0;
    }

    .user-type-label {
      display: block;
      font-size: 14px;
      margin-bottom: 8px;
      color: rgba(255, 255, 255, 0.9);
    }

    .radio-group {
      display: flex;
      gap: 10px;
      justify-content: space-between;
      width: 100%;
    }

    .radio-group input[type="radio"] {
      display: none;
    }

    .radio-group label {
      display: flex;
      align-items: center;
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid rgba(255, 255, 255, .1);
      border-radius: 40px;
      padding: 8px 15px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
      flex-grow: 1;
      justify-content: center;
      color: rgba(255, 255, 255, 0.9);
    }

    .radio-group label:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.2);
    }

    .radio-group input[type="radio"]:checked + label {
      background: rgba(76, 175, 80, 0.2);
      border-color: #4CAF50;
      color: #4CAF50;
      font-weight: 600;
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
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .wrapper .btn:active {
      transform: translateY(0);
    }

    .wrapper .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        120deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
      );
      transition: 0.5s;
    }

    .wrapper .btn:hover::before {
      left: 100%;
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

    .password-strength {
      height: 4px;
      background: rgba(255, 255, 255, 0.1);
      margin-top: 5px;
      border-radius: 2px;
      overflow: hidden;
    }

    .password-strength-bar {
      height: 100%;
      width: 0;
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    .password-strength-bar.weak {
      background: #ff4444;
    }

    .password-strength-bar.medium {
      background: #ffbb33;
    }

    .password-strength-bar.strong {
      background: #4CAF50;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
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
      background: linear-gradient(90deg, #4CAF50, #45a049);
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
      background: linear-gradient(45deg, #4CAF50, #45a049);
      color: #fff;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 15px;
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

    /* Optional field styles */
    .optional-field {
      opacity: 0.8;
    }

    /* Language Switcher Styles */
    .language-switcher {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 10px;
      z-index: 1000;
    }

    .lang-btn {
      padding: 8px 15px;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      color: #fff;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
      backdrop-filter: blur(5px);
    }

    .lang-btn:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }

    .lang-btn.active {
      background: #4CAF50;
      border-color: #4CAF50;
    }
  </style>
</head>
<body>
  <!-- Language Switcher -->
  <div class="language-switcher">
    <button class="lang-btn active" data-lang="en">EN</button>
    <button class="lang-btn" data-lang="fr">FR</button>
  </div>

  <div class="wrapper">
    <form id="signupForm" action="signup.php" method="POST">
      <h1 data-translate="create_account">Create Account</h1>
      <div class="input-box">
        <input type="text" name="username" id="username" data-translate="username_placeholder" placeholder="Username" required>
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="email" name="email" id="email" data-translate="email_placeholder" placeholder="E-mail" required
               pattern="^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|icloud\.com)$"
               title="Please enter a valid email address with a supported domain (gmail.com, yahoo.com, hotmail.com, outlook.com, or icloud.com)">
        <i class='bx bxs-envelope'></i>
      </div>
      <div class="phone-input-group">
        <span class="phone-prefix">+212</span>
        <input type="tel" id="phone" name="phone" class="phone-input" data-translate="phone_placeholder" placeholder="6XXXXXXXX or 7XXXXXXXX" 
               pattern="^[67][0-9]{8}$"
               title="Please enter a valid Moroccan phone number starting with 6 or 7"
               required>
        <i class='bx bxs-phone'></i>
      </div>
      <div class="input-box">
        <input type="password" name="password" id="password" data-translate="password_placeholder" placeholder="Password" required>
        <i class='bx bxs-lock-alt'></i>
      </div>
      <div class="input-box">
        <input type="password" name="confirm_password" id="confirm_password" data-translate="confirm_password_placeholder" placeholder="Confirm password" required>
        <i class='bx bxs-lock-alt'></i>
      </div>

      <!-- User type selection -->
      <div class="user-type-selection">
        <label class="user-type-label" data-translate="account_type_label">Account Type:</label>
        <div class="radio-group">
          <input type="radio" id="farmer" name="user_type" value="farmer" required>
          <label for="farmer" data-translate="farmer_label"> Farmer</label>

          <input type="radio" id="user" name="user_type" value="user" required>
          <label for="user" data-translate="consumer_label"> Consumer</label>
        </div>
      </div>

      <!-- Gender selection -->
      <div class="user-type-selection optional-field">
        <label class="user-type-label" data-translate="gender_label">Gender (Optional):</label>
        <div class="radio-group">
          <input type="radio" id="male" name="gender" value="male">
          <label for="male"><i class='bx bxs-male-sign'></i> Male</label>

          <input type="radio" id="female" name="gender" value="female">
          <label for="female"><i class='bx bxs-female-sign'></i> Female</label>
        </div>
      </div>

      <button type="submit" class="btn" data-translate="register">Register</button>
      <div class="register-link">
        <p><span data-translate="have_account">Already have an account?</span> <a href="index.php" data-translate="login">Login</a></p>
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
    // Translations from PHP
    const translations = {
      en: <?php echo json_encode($translations->getAllTranslations('en')); ?>,
      fr: <?php echo json_encode($translations->getAllTranslations('fr')); ?>
    };

    // Language Switcher
    const langButtons = document.querySelectorAll('.lang-btn');
    let currentLang = localStorage.getItem('language') || '<?php echo $translations->getCurrentLanguage(); ?>';

    function setLanguage(lang) {
      currentLang = lang;
      localStorage.setItem('language', lang);
      
      // Update active button
      langButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
      });

      // Update all translatable elements
      document.querySelectorAll('[data-translate]').forEach(element => {
        const key = element.dataset.translate;
        if (translations[lang][key]) {
          if (element.tagName === 'INPUT') {
            element.placeholder = translations[lang][key];
          } else {
            element.textContent = translations[lang][key];
          }
        }
      });

      // Send AJAX request to update session
      fetch('update_language.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'language=' + lang
      });
    }

    // Initialize language
    setLanguage(currentLang);

    // Add click handlers to language buttons
    langButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        setLanguage(btn.dataset.lang);
      });
    });

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

      // Email validation
      const emailPattern = /^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|icloud\.com)$/;
      if (!emailPattern.test(email)) {
        showAlert("Please enter a valid email address with a supported domain (gmail.com, yahoo.com, hotmail.com, outlook.com, or icloud.com)", 'error');
        return;
      }

      // Phone validation
      const phonePattern = /^[67][0-9]{8}$/;
      if (!phonePattern.test(phone)) {
        showAlert("Please enter a valid Moroccan phone number starting with 6 or 7 followed by 8 digits", 'error');
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

      // Add +212 prefix to phone number before submitting
      document.getElementById('phone').value = '+212' + phone;

      // If all validations pass, submit the form
      this.submit();
    });

    document.getElementById('password').addEventListener('input', function(e) {
      const password = e.target.value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const errorMessage = document.getElementById('password-error');
      
      if (confirmPassword && password !== confirmPassword) {
        errorMessage.textContent = 'Passwords do not match';
      } else {
        errorMessage.textContent = '';
      }
    });

    document.getElementById('confirm_password').addEventListener('input', function(e) {
      const password = document.getElementById('password').value;
      const confirmPassword = e.target.value;
      const errorMessage = document.getElementById('password-error');
      
      if (password !== confirmPassword) {
        errorMessage.textContent = 'Passwords do not match';
      } else {
        errorMessage.textContent = '';
      }
    });
  </script>

  <?php
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
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;

    // Get user type from radio button
    $user_type = $_POST['user_type'] ?? '';

    // Basic server-side validation for user type
    if (!in_array($user_type, ['farmer', 'user'])) {
      echo "<script>showAlert('Invalid account type selected', 'error');</script>";
      exit();
    }

    // Set user tag based on user type
    $user_tag = ($user_type === 'farmer') ? 'FarmX Producer' : 'FarmX Member';

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
    
    // Modified INSERT statement to include user_tag and gender
    $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password, user_type, user_tag, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $username, $email, $phone, $hashed_password, $user_type, $user_tag, $gender);

    if ($stmt->execute()) {
      session_start();
      $_SESSION['registration_success'] = true;
      $_SESSION['registered_username'] = $username;
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