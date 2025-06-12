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
    <title>FarmX - Modifier le Profil</title>
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
            min-height: 90vh;
            padding: 10px;
            font-size: 15px;
            position: relative;
        }

        /* Edit Profile Container */
        .profile-container {
            max-width: 950px;
            width: 100%;
            padding: 20px 40px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .profile-container h2 {
            grid-column: 1 / -1;
            margin-bottom: 0px;
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
            display: block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-container form {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .profile-container textarea {
            min-height: 60px;
        }

        .profile-container label {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            margin-bottom: 0px;
        }

        .profile-container input[type="email"],
        .profile-container input[type="tel"],
        .profile-container input[type="text"],
        .profile-container input[type="file"] {
            width: 100%;
            padding: 7px 10px;
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
            padding: 12px 25px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .profile-container button[type="submit"]:hover {
            background-color: #2d682f;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }

        /* New: Form Actions Layout */
        .form-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
            grid-column: 1 / -1;
        }

        .form-actions button {
            flex: 1;
            min-width: 150px;
            padding: 12px 25px;
            border-radius: 12px;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.18);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .form-actions button[type="submit"] {
            background: linear-gradient(45deg, #4CAF50, #3e8e41);
            border: none;
        }

        .form-actions button[type="submit"]:hover {
            background: linear-gradient(45deg, #3e8e41, #2d682f);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* New: Back to Profile Link */
        .back-to-profile-link {
            position: absolute;
            top: 30px;
            left: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(45deg, #4CAF50, #3e8e41);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .back-to-profile-link:hover {
            background: linear-gradient(45deg, #3e8e41, #2d682f);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
        }

        .back-to-profile-link i {
            font-size: 18px;
        }

        .form-actions .delete-account-btn {
            background: linear-gradient(45deg, #FF6B6B, #FF4D4D);
            border: none;
        }

        .form-actions .delete-account-btn:hover {
            background: linear-gradient(45deg, #FF4D4D, #E60000);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* File Input Customization */
        .profile-pic-upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        .custom-file-upload {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: linear-gradient(45deg, #4CAF50, #3e8e41);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .custom-file-upload i {
            margin-right: 6px;
            font-size: 16px;
        }

        .custom-file-upload:hover {
            background: linear-gradient(45deg, #3e8e41, #2d682f);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
        }

        .file-name {
            font-size: 14px;
            color: #666;
            flex-grow: 1;
        }

        .profile-pic-preview-wrapper {
            position: relative;
            width: 120px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #3e8e41;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0;
            margin-top: 10px;
        }

        .profile-pic-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .remove-profile-pic-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .remove-profile-pic-btn:hover {
            background-color: #cc0000;
            transform: scale(1.15);
        }

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
            font-size: 11px;
            color: #666;
            text-align: right;
            margin-top: 2px;
        }

        @media (max-width: 768px) {
            body {
                font-size: 13px;
                padding: 5px;
            }
            .profile-container {
                grid-template-columns: 1fr;
                max-width: 550px;
                padding: 15px;
                gap: 10px;
            }

            .profile-container form {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions button,
            .form-actions .back-button-link {
                min-width: unset;
                width: 100%;
                padding: 10px 20px;
                font-size: 14px;
            }

            .back-to-profile-link {
                top: 25px;
                left: 10px;
                padding: 10px 15px;
                font-size: 14px;
            }

            .back-to-profile-link i {
                font-size: 17px;
            }

            .profile-container h2 {
                padding: 8px 0;
                font-size: 20px;
            }

            .custom-file-upload {
                padding: 8px 15px;
                font-size: 13px;
            }

            .custom-file-upload i {
                font-size: 15px;
            }

            .profile-pic-preview-wrapper {
                width: 120px;
                height: 80px;
            }

            .remove-profile-pic-btn {
                width: 25px;
                height: 25px;
                font-size: 16px;
            }

            .phone-input-group {
                gap: 5px;
            }

            .phone-prefix {
                padding: 8px;
                min-width: 50px;
                font-size: 13px;
            }

            .phone-input {
                padding: 8px 10px;
                font-size: 13px;
            }

            .alert-content {
                padding: 25px;
            }

            .alert-content p {
                font-size: 15px;
            }

            .alert-content button {
                padding: 10px 25px;
                font-size: 15px;
            }

            .modal-content {
                padding: 25px;
            }

            .modal-content h3 {
                font-size: 18px;
            }

            .modal-content p {
                font-size: 15px;
            }

            .modal-buttons button {
                padding: 10px 25px;
                font-size: 15px;
            }

            .password-modal input[type="password"] {
                padding: 8px;
                font-size: 15px;
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
            background: rgba(255, 255, 255, 0.98);
            padding: 35px;
            border-radius: 25px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
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
            height: 6px;
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
            padding: 14px 35px;
            background: linear-gradient(45deg, #ff4d4d, #cc0000);
            color: #fff;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 18px rgba(255, 77, 77, 0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .alert-content button:hover {
            background: linear-gradient(45deg, #cc0000, #ff4d4d);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 77, 77, 0.5);
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

        /* Custom Alert Modals */
        .confirm-modal, .password-modal {
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
            z-index: 1001;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.98);
            padding: 35px;
            border-radius: 25px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
            text-align: center;
            max-width: 450px;
            width: 90%;
            transform: scale(0.9);
            animation: scaleIn 0.3s ease forwards;
            position: relative;
            overflow: hidden;
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #ff4d4d, #cc0000);
        }

        .modal-content h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-content p {
            font-size: 16px;
            margin: 20px 0;
            color: #333;
            line-height: 1.5;
            font-weight: 500;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 14px 30px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        .modal-buttons .confirm-btn {
            background: linear-gradient(45deg, #FF6B6B, #FF4D4D);
            color: #fff;
            box-shadow: 0 6px 18px rgba(255, 77, 77, 0.4);
        }

        .modal-buttons .confirm-btn:hover {
            background: linear-gradient(45deg, #FF4D4D, #E60000);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 77, 77, 0.5);
        }

        .modal-buttons .cancel-btn {
            background: linear-gradient(45deg, #b0b0b0, #9e9e9e);
            color: #fff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-buttons .cancel-btn:hover {
            background: linear-gradient(45deg, #9e9e9e, #8a8a8a);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .password-modal input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-top: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .password-modal input[type="password"]:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 5px rgba(62, 142, 65, 0.3);
        }

        .password-modal .error-message {
            color: #ff4d4d;
            font-size: 13px;
            margin-top: 10px;
            display: none;
        }

        /* Overrides for custom alert to ensure it appears above modals */
        .custom-alert {
            z-index: 1002;
        }
    </style>
</head>
<body>
    <!-- Back to Profile Link -->
    <a href="profile.php" class="back-to-profile-link">
        <i class='bx bx-arrow-back'></i>
        Retourner au profil
    </a>

    <!-- Edit Profile Form -->
    <div class="profile-container">
        <h2>Modifier le Profil</h2>
        <?php if (isset($_SESSION['profile_errors']) && !empty($_SESSION['profile_errors'])): ?>
            <div class="server-error">
                <strong>Veuillez corriger les erreurs suivantes:</strong>
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
                <label for="username">Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required 
                       pattern="^[a-zA-Z0-9_]{3,20}$" 
                       title="Le nom d'utilisateur doit comporter entre 3 et 20 caractères et ne peut contenir que des lettres, des chiffres et des underscores">
                <div class="error-message" id="username-error">Le nom d'utilisateur doit comporter entre 3 et 20 caractères et ne peut contenir que des lettres, des chiffres et des underscores</div>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                       pattern="^[a-zA-Z0-9._%+-]+@(gmail\.com|yahoo\.com|hotmail\.com|outlook\.com|icloud\.com)$"
                       title="Veuillez entrer une adresse email valide avec un domaine pris en charge (gmail.com, yahoo.com, hotmail.com, outlook.com, ou icloud.com)">
                <div class="error-message" id="email-error">Veuillez entrer une adresse email valide avec un domaine pris en charge (gmail.com, yahoo.com, hotmail.com, outlook.com, ou icloud.com)</div>
            </div>

            <div class="form-group">
                <label for="phone">Numéro de téléphone:</label>
                <div class="phone-input-group">
                    <span class="phone-prefix">+212</span>
                    <input type="tel" id="phone" name="phone" class="phone-input" 
                           placeholder="6XXXXXXXX ou 7XXXXXXXX" 
                           value="<?php echo substr(htmlspecialchars($phone), -9); ?>" 
                           maxlength="9" 
                           pattern="^[67][0-9]{8}$"
                           title="Veuillez entrer un numéro de téléphone marocain valide commençant par 6 ou 7"
                           oninput="validatePhoneInput(this)">
            </div>
                <div class="error-message" id="phone-error">Veuillez entrer un numéro de téléphone marocain valide commençant par 6 ou 7</div>
            </div>

            <div class="form-group">
                <label for="gender">Sexe:</label>
                <?php if (!empty($gender)): ?>
                    <input type="text" id="gender" name="gender" value="<?php echo htmlspecialchars($gender); ?>" readonly>
                <?php else: ?>
                    <select id="gender" name="gender">
                        <option value="">Sélectionnez le sexe</option>
                        <option value="Male">Masculin</option>
                        <option value="Female">Féminin</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="profile-pic-upload">Photo de profil:</label>
                <div class="profile-pic-upload-container">
                    <input type="file" id="profile-pic-upload" name="profile-pic-upload" accept="image/*" style="display: none;">
                    <label for="profile-pic-upload" class="custom-file-upload">
                        <i class='bx bx-upload'></i> Choisir un fichier
                    </label>
                    <span id="file-name" class="file-name">Aucun fichier choisi</span>
                    <div class="profile-pic-preview-wrapper">
                        <img id="profile-pic-preview" src="" alt="Aperçu de la photo de profil" class="profile-pic-preview" style="display: none;">
                        <button type="button" id="remove-profile-pic" class="remove-profile-pic-btn" title="Supprimer la photo de profil" style="display: none;">
                            <i class='bx bx-x'></i>
                        </button>
                    </div>
                    <input type="hidden" id="remove-profile-pic-hidden" name="remove_profile_pic" value="0">
                </div>
            </div>

            <div class="form-group full-width">
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" maxlength="500" placeholder="Parlez-nous de vous..."><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
                <div class="char-count"><span id="bio-char-count">0</span>/500 caractères</div>
            </div>

            <div class="form-actions">
                <button type="submit">Enregistrer les modifications</button>
                <button type="button" id="delete-account-btn" class="delete-account-btn">Supprimer le compte</button>
            </div>
        </form>
    </div>

    <!-- Custom Alert Modal -->
    <div id="customAlert" class="custom-alert">
        <div class="alert-content">
            <i class='bx bxs-error-circle'></i>
            <p id="alertMessage"></p>
            <button id="alertCloseButton">OK</button>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmDeleteModal" class="confirm-modal">
        <div class="modal-content">
            <h3>Confirmer la suppression du compte</h3>
            <p>Êtes-vous sûr de vouloir supprimer votre compte? Cette action est irréversible.</p>
            <div class="modal-buttons">
                <button type="button" class="confirm-btn" id="confirm-delete-btn">Supprimer</button>
                <button type="button" class="cancel-btn" id="cancel-delete-btn">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="password-modal">
        <div class="modal-content">
            <h3>Veuillez entrer votre mot de passe</h3>
            <input type="password" id="delete-password" placeholder="Votre mot de passe">
            <div class="error-message" id="password-error"></div>
            <div class="modal-buttons">
                <button type="button" class="confirm-btn" id="submit-password-btn">Confirmer</button>
                <button type="button" class="cancel-btn" id="cancel-password-btn">Annuler</button>
            </div>
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
                showAlert("Veuillez corriger les erreurs dans le formulaire avant de soumettre.");
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
                input.value.slice(0, 9);
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

            // Profile Picture Upload UI Logic
            const profilePicUpload = document.getElementById('profile-pic-upload');
            const fileNameSpan = document.getElementById('file-name');
            const profilePicPreview = document.getElementById('profile-pic-preview');
            const removeProfilePicBtn = document.getElementById('remove-profile-pic');
            const removeProfilePicHidden = document.getElementById('remove-profile-pic-hidden');
            const defaultProfilePicPath = 'Images/profile.jpg'; // Define default path

            // Set initial preview if profilePic exists from PHP
            const initialProfilePic = "<?php echo htmlspecialchars($profilePic); ?>";
            if (initialProfilePic && initialProfilePic !== defaultProfilePicPath) { // Check if it's not the default path
                profilePicPreview.src = initialProfilePic + '?t=' + new Date().getTime(); // Add timestamp to bypass cache
                profilePicPreview.style.display = 'block';
                removeProfilePicBtn.style.display = 'flex'; // Use flex for center alignment
                fileNameSpan.textContent = 'Fichier actuel';
            } else {
                // If no initial profile pic or it's the default, show default
                profilePicPreview.src = defaultProfilePicPath;
                profilePicPreview.style.display = 'block';
                removeProfilePicBtn.style.display = 'none'; // Hide remove button for default
            }

            profilePicUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePicPreview.src = e.target.result;
                        profilePicPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);

                    fileNameSpan.textContent = this.files[0].name;
                    removeProfilePicBtn.style.display = 'flex'; // Show remove button
                    removeProfilePicHidden.value = '0'; // Indicate not removing current pic
                } else {
                    // If file input is cleared without selecting a new file
                    profilePicPreview.src = defaultProfilePicPath;
                    profilePicPreview.style.display = 'block';
                    fileNameSpan.textContent = 'Aucun fichier choisi';
                    removeProfilePicBtn.style.display = 'none'; // Hide remove button
                    removeProfilePicHidden.value = '1'; // Indicate removing current pic
                }
            });

            removeProfilePicBtn.addEventListener('click', function() {
                profilePicUpload.value = ''; // Clear the file input
                profilePicPreview.src = defaultProfilePicPath;
                profilePicPreview.style.display = 'block';
                fileNameSpan.textContent = 'Aucun fichier choisi';
                removeProfilePicBtn.style.display = 'none';
                removeProfilePicHidden.value = '1'; // Signal to PHP to remove the picture
            });

            // Account Deletion Modals Logic
            const deleteAccountBtn = document.getElementById('delete-account-btn');
            const confirmDeleteModal = document.getElementById('confirmDeleteModal');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            const passwordModal = document.getElementById('passwordModal');
            const deletePasswordInput = document.getElementById('delete-password');
            const passwordError = document.getElementById('password-error');
            const submitPasswordBtn = document.getElementById('submit-password-btn');
            const cancelPasswordBtn = document.getElementById('cancel-password-btn');

            deleteAccountBtn.addEventListener('click', function() {
                confirmDeleteModal.style.display = 'flex';
            });

            cancelDeleteBtn.addEventListener('click', function() {
                confirmDeleteModal.style.display = 'none';
            });

            confirmDeleteBtn.addEventListener('click', function() {
                confirmDeleteModal.style.display = 'none';
                passwordModal.style.display = 'flex';
                deletePasswordInput.value = ''; // Clear password input
                passwordError.style.display = 'none'; // Hide any previous errors
            });

            cancelPasswordBtn.addEventListener('click', function() {
                passwordModal.style.display = 'none';
            });

            submitPasswordBtn.addEventListener('click', function() {
                const password = deletePasswordInput.value;
                if (password.length === 0) {
                    passwordError.textContent = 'Veuillez entrer votre mot de passe.';
                    passwordError.style.display = 'block';
                    return;
                }

                // Send password to server for verification and account deletion
                fetch('delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `password=${encodeURIComponent(password)}`,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Account deleted successfully
                        passwordModal.style.display = 'none';
                        // Use localStorage to pass success message to index.php
                        localStorage.setItem('accountDeleted', 'Votre compte a été supprimé avec succès.');
                        window.location.href = 'index.php';
                    } else {
                        // Password incorrect or other error
                        passwordError.textContent = data.message || 'Erreur lors de la suppression du compte.';
                        passwordError.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    passwordError.textContent = 'Une erreur est survenue lors de la communication avec le serveur.';
                    passwordError.style.display = 'block';
                });
            });
        });
    </script>
</body>
</html>