<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is a FarmX Producer
if (!isset($_SESSION['user_tag']) || $_SESSION['user_tag'] !== 'FarmX Producer') {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'farmer') {
        header("Location: market.php");
        exit();
    }
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (Exception $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seller_id = $_SESSION['user_id'];
    $title = $_POST['product_name'] ?? '';
    $description = $_POST['product_description'] ?? '';
    $price = $_POST['product_price'] ?? 0;
    $category = $_POST['product_category'] ?? 'other';
    
    // Handle file upload
    $image_url = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/marketplace/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
            $image_url = $file_path;
        }
    }
    
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO marketplace_items 
                          (seller_id, title, description, price, image_url, category, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'available')");
    $success = $stmt->execute([$seller_id, $title, $description, $price, $image_url, $category]);
    
    if ($success) {
        $_SESSION['add_success'] = "Produit ajouté avec succès !";
        header("Location: market.php");
        exit();
    } else {
        $error = "Échec de l'ajout du produit";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Ajouter Produit</title>
    <link rel="icon" href="Images/logo.jpg">   
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        .add-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .back-button {
            grid-column: 1 / -1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: #f5f5f5;
            width: fit-content;
        }

        .back-button:hover {
            background-color: #e0e0e0;
            color: #333;
        }

        .back-button i {
            font-size: 20px;
        }

        .add-form {
            grid-column: 2;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .add-form h2 {
            color: #2d682f;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: #444;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3e8e41;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(62, 142, 65, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .image-upload-section {
            grid-column: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            height: 100%;
        }

        .image-preview {
            width: 100%;
            height: 100%;
            min-height: 500px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #e0e0e0;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: none;
        }

        .image-preview .upload-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            color: #666;
            text-align: center;
            padding: 20px;
        }

        .image-preview .upload-placeholder i {
            font-size: 48px;
            color: #3e8e41;
        }

        .image-preview .upload-placeholder p {
            font-size: 16px;
            max-width: 250px;
        }

        .image-upload {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            border: 2px dashed #e0e0e0;
            text-align: center;
        }

        .image-upload .file-input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }

        .image-upload input[type="file"] {
            position: relative;
            z-index: 1;
            opacity: 0;
            cursor: pointer;
            height: 50px;
            margin: 0;
            padding: 0;
        }

        .image-upload .file-input-button {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 0;
        }

        .image-upload .file-input-button:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(62, 142, 65, 0.2);
        }

        .image-upload .file-input-button i {
            font-size: 20px;
        }

        .image-upload .upload-text {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
            line-height: 1.5;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        button[type="submit"] {
            background-color: #3e8e41;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: auto;
        }

        button[type="submit"]:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(62, 142, 65, 0.2);
        }

        @media (max-width: 1024px) {
            .add-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .image-preview {
                height: 400px;
                min-height: unset;
            }
        }

        @media (max-width: 768px) {
            .add-container {
                padding: 20px;
                margin: 10px;
            }

            .image-preview {
                height: 300px;
            }
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

        .success-alert .alert-content::before {
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

        .success-alert .alert-content button {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .alert-content button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 77, 77, 0.4);
        }

        .success-alert .alert-content button:hover {
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
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

        .success-alert .alert-content i {
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Custom Alert Modal -->
    <div id="customAlert" class="custom-alert">
        <div class="alert-content">
            <i class='bx bxs-error-circle'></i>
            <p id="alertMessage"></p>
            <button id="alertCloseButton">OK</button>
        </div>
    </div>

    <div class="add-container">
        <a href="market.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Retour au Marché
        </a>

        <div class="image-upload-section">
            <div class="image-preview" id="imagePreview">
                <div class="upload-placeholder">
                    <i class='bx bx-image-add'></i>
                    <p>Glissez et déposez une image ici ou cliquez pour sélectionner</p>
                </div>
                <img src="" alt="Aperçu de l'image" id="previewImage">
            </div>
            <div class="image-upload">
                <div class="file-input-wrapper">
                    <input type="file" id="product_image" name="product_image" accept="image/*" required>
                    <div class="file-input-button">
                        <i class='bx bx-upload'></i>
                        Choisir une image
                    </div>
                </div>
                <p class="upload-text">Formats acceptés : JPG, PNG, GIF, WebP. Taille maximale : 2MB</p>
            </div>
        </div>

        <form class="add-form" method="POST" enctype="multipart/form-data">
            <h2>Ajouter un Nouveau Produit</h2>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Champ caché pour l'image -->
            <input type="hidden" name="product_image" id="hidden_image_input">

            <div class="form-group">
                <label for="product_name">Nom du produit:</label>
                <input type="text" id="product_name" name="product_name" required>
            </div>

            <div class="form-group">
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" required></textarea>
            </div>

            <div class="form-group">
                <label for="product_price">Prix (MAD):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="product_category">Catégorie:</label>
                <select id="product_category" name="product_category" required>
                    <option value="">Sélectionnez une catégorie</option>
                    <option value="legumes">Légumes</option>
                    <option value="fruits">Fruits</option>
                    <option value="produits_laitiers">Produits Laitiers</option>
                    <option value="viande">Viande</option>
                    <option value="miel">Miel & Produits Apicoles</option>
                    <option value="cereales">Céréales</option>
                    <option value="materiel_agricole">Matériel Agricole</option>
                    <option value="produits_transformes">Produits Transformés</option>
                    <option value="animaux">Animaux d'Élevage</option>
                    <option value="terrain">Terrains Agricoles</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <button type="submit">
                <i class='bx bx-plus-circle'></i>
                Ajouter le Produit
            </button>
        </form>
    </div>

    <script>
        // Preview image before upload
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        const uploadPlaceholder = document.querySelector('.upload-placeholder');
        const fileInput = document.getElementById('product_image');
        const hiddenImageInput = document.getElementById('hidden_image_input');
        const form = document.querySelector('.add-form');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    uploadPlaceholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        imagePreview.addEventListener('dragover', function(e) {
            e.preventDefault();
            imagePreview.style.borderColor = '#3e8e41';
        });

        imagePreview.addEventListener('dragleave', function(e) {
            e.preventDefault();
            imagePreview.style.borderColor = '#e0e0e0';
        });

        imagePreview.addEventListener('drop', function(e) {
            e.preventDefault();
            imagePreview.style.borderColor = '#e0e0e0';
            
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                fileInput.files = e.dataTransfer.files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
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
                // If it's a success message, redirect to market
                if (type === 'success') {
                    window.location.href = 'market.php';
                }
            };

            alertModal.onclick = function(event) {
                if (event.target === alertModal) {
                    alertModal.style.display = "none";
                    // If it's a success message, redirect to market
                    if (type === 'success') {
                        window.location.href = 'market.php';
                    }
                }
            };
        }

        // Gestion de la soumission du formulaire
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vérifier si une image a été sélectionnée
            if (!fileInput.files[0]) {
                showAlert('Veuillez sélectionner une image pour votre produit.', 'error');
                return;
            }
            
            // Créer un FormData pour l'upload
            const formData = new FormData();
            formData.append('product_name', document.getElementById('product_name').value);
            formData.append('product_description', document.getElementById('product_description').value);
            formData.append('product_price', document.getElementById('product_price').value);
            formData.append('product_category', document.getElementById('product_category').value);
            formData.append('product_image', fileInput.files[0]);
            
            // Envoyer le formulaire
            fetch('add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('success') || data.includes('Produit ajouté')) {
                    showAlert('Produit ajouté avec succès !', 'success');
                } else {
                    showAlert('Erreur lors de l\'ajout du produit. Veuillez réessayer.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Erreur de connexion. Veuillez réessayer.', 'error');
            });
        });
    </script>
</body>
</html> 