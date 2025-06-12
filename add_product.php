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
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .add-form h2 {
            text-align: center;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 20px 0;
            background: linear-gradient(to right, #3e8e41, #2d682f);
            border-radius: 12px;
            display: inline-block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
        }

        .add-form input,
        .add-form textarea,
        .add-form select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .add-form input:focus,
        .add-form textarea:focus,
        .add-form select:focus {
            border-color: #3e8e41;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(62, 142, 65, 0.1);
        }

        .add-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload-container {
            position: relative;
            margin-bottom: 20px;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border: 2px dashed #3e8e41;
            border-radius: 8px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            background-color: #f0f7f0;
            border-color: #2d682f;
        }

        .file-upload-icon {
            font-size: 40px;
            color: #3e8e41;
            margin-bottom: 10px;
        }

        .file-upload-text {
            font-size: 16px;
            color: #666;
            text-align: center;
        }

        .file-upload-text span {
            color: #3e8e41;
            font-weight: 500;
        }

        .preview-container {
            margin-top: 15px;
            display: none;
        }

        .preview-container img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .add-form button {
            width: 100%;
            padding: 15px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .add-form button:hover {
            background-color: #2d682f;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            color: #666;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #f0f0f0;
            color: #333;
        }

        .back-button i {
            margin-right: 8px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="add-container">
        <a href="market.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Retour au Marché
        </a>

        <div class="add-form">
            <h2>Ajouter un nouveau produit</h2>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="product_name">Nom du produit</label>
                    <input type="text" id="product_name" name="product_name" placeholder="Entrez le nom du produit" required>
                </div>

                <div class="form-group">
                    <label for="product_description">Description</label>
                    <textarea id="product_description" name="product_description" placeholder="Entrez la description du produit" required></textarea>
                </div>

                <div class="form-group">
                    <label for="product_price">Prix (MAD)</label>
                    <input type="number" id="product_price" name="product_price" placeholder="Entrez le prix" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="product_category">Catégorie</label>
                    <select id="product_category" name="product_category" required>
                        <option value="">Sélectionner une catégorie</option>
                        <option value="vegetables">Légumes</option>
                        <option value="fruits">Fruits</option>
                        <option value="dairy">Produits Laitiers</option>
                        <option value="meat">Viande & Volaille</option>
                        <option value="honey">Miel & Produits Apicoles</option>
                        <option value="grains">Céréales</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Image du produit</label>
                    <div class="file-upload-container">
                        <input type="file" id="product_image" name="product_image" accept="image/*" class="file-upload-input" required>
                        <label for="product_image" class="file-upload-label">
                            <i class='bx bx-cloud-upload file-upload-icon'></i>
                            <div class="file-upload-text">
                                <span>Cliquer pour télécharger</span> ou glisser-déposer<br>
                                PNG, JPG ou JPEG (max. 5MB)
                            </div>
                        </label>
                    </div>
                    <div class="preview-container" id="previewContainer">
                        <img id="imagePreview" src="#" alt="Aperçu de l'image">
                    </div>
                </div>

                <button type="submit">Ajouter Produit</button>
            </form>
        </div>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('product_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                const previewContainer = document.getElementById('previewContainer');
                const imagePreview = document.getElementById('imagePreview');

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }

                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        const dropZone = document.querySelector('.file-upload-label');
        const fileInput = document.getElementById('product_image');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('highlight');
        }

        function unhighlight(e) {
            dropZone.classList.remove('highlight');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            
            // Trigger change event to show preview
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
    </script>
</body>
</html> 