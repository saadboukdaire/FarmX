<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

// Get product ID from URL
$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header("Location: market.php");
    exit();
}

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM marketplace_items WHERE id = ? AND seller_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: market.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['product_name'] ?? '';
    $description = $_POST['product_description'] ?? '';
    $price = $_POST['product_price'] ?? 0;
    $category = $_POST['product_category'] ?? 'other';
    
    // Handle file upload if a new image is provided
    $image_url = $product['image_url']; // Keep existing image by default
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/marketplace/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
            // Delete old image if it exists
            if ($product['image_url'] && file_exists($product['image_url'])) {
                unlink($product['image_url']);
            }
            $image_url = $file_path;
        }
    }
    
    // Update product in database
    $stmt = $pdo->prepare("UPDATE marketplace_items 
                          SET title = ?, description = ?, price = ?, image_url = ?, category = ? 
                          WHERE id = ? AND seller_id = ?");
    $success = $stmt->execute([$title, $description, $price, $image_url, $category, $product_id, $_SESSION['user_id']]);
    
    if ($success) {
        // Check if any changes were made
        $changes = false;
        if ($title !== $product['title'] ||
            $description !== $product['description'] ||
            $price != $product['price'] ||
            $category !== $product['category'] ||
            $image_url !== $product['image_url']) {
            $changes = true;
        }

        if ($changes) {
            // Set success message in session only if changes were made
            $_SESSION['edit_success'] = "Produit mis à jour avec succès !";
        }
        // Redirect back to marketplace
        header("Location: market.php");
        exit();
    } else {
        $error = "Échec de la mise à jour du produit";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Modifier Produit</title>
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

        .edit-container {
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

        .edit-form {
            grid-column: 2;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .edit-form h2 {
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

        .image-preview-section {
            grid-column: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            height: 100%;
        }

        .current-image {
            width: 100%;
            height: 100%;
            min-height: 500px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
        }

        .current-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .image-upload {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            border: 2px dashed #e0e0e0;
            text-align: center;
        }

        .image-upload label {
            display: block;
            margin-bottom: 10px;
            color: #666;
        }

        .image-upload input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: white;
        }

        /* Custom file input styling */
        .image-upload {
            position: relative;
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

        .image-upload .file-input-wrapper {
            position: relative;
            margin-bottom: 15px;
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
            .edit-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .current-image {
                height: 400px;
                min-height: unset;
            }
        }

        @media (max-width: 768px) {
            .edit-container {
                padding: 20px;
                margin: 10px;
            }

            .current-image {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <a href="market.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Retour au Marché
        </a>

        <div class="image-preview-section">
            <div class="current-image">
                <?php if ($product['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Image actuelle du produit" id="imagePreview">
                <?php else: ?>
                    <img src="Images/default-product.jpg" alt="Image par défaut du produit" id="imagePreview">
                <?php endif; ?>
            </div>
            <div class="image-upload">
                <label for="product_image">Choisir une nouvelle image</label>
                <div class="file-input-wrapper">
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <div class="file-input-button">
                        <i class='bx bx-upload'></i>
                        Parcourir les fichiers
                    </div>
                </div>
                <p class="upload-text">Téléchargez une nouvelle image pour remplacer l'image actuelle, ou laissez vide pour la conserver.</p>
            </div>
        </div>

        <form class="edit-form" method="POST" enctype="multipart/form-data">
            <h2>Modifier le Produit</h2>
            <?php if (isset($_SESSION['edit_success'])): ?>
                <div class="success-message"><?= htmlspecialchars($_SESSION['edit_success']) ?></div>
                <?php unset($_SESSION['edit_success']); ?>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="product_name">Nom du produit:</label>
                <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="product_price">Prix (MAD):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="product_category">Catégorie:</label>
                <select id="product_category" name="product_category" required>
                    <option value="legumes" <?php echo $product['category'] === 'legumes' ? 'selected' : ''; ?>>Légumes</option>
                    <option value="fruits" <?php echo $product['category'] === 'fruits' ? 'selected' : ''; ?>>Fruits</option>
                    <option value="produits_laitiers" <?php echo $product['category'] === 'produits_laitiers' ? 'selected' : ''; ?>>Produits Laitiers</option>
                    <option value="viande" <?php echo $product['category'] === 'viande' ? 'selected' : ''; ?>>Viande</option>
                    <option value="miel" <?php echo $product['category'] === 'miel' ? 'selected' : ''; ?>>Miel & Produits Apicoles</option>
                    <option value="cereales" <?php echo $product['category'] === 'cereales' ? 'selected' : ''; ?>>Céréales</option>
                    <option value="materiel_agricole" <?php echo $product['category'] === 'materiel_agricole' ? 'selected' : ''; ?>>Matériel Agricole</option>
                    <option value="produits_transformes" <?php echo $product['category'] === 'produits_transformes' ? 'selected' : ''; ?>>Produits Transformés</option>
                    <option value="animaux" <?php echo $product['category'] === 'animaux' ? 'selected' : ''; ?>>Animaux d'Élevage</option>
                    <option value="terrain" <?php echo $product['category'] === 'terrain' ? 'selected' : ''; ?>>Terrains Agricoles</option>
                    <option value="autre" <?php echo $product['category'] === 'autre' ? 'selected' : ''; ?>>Autre</option>
                </select>
            </div>

            <button type="submit">
                <i class='bx bx-save'></i>
                Mettre à jour le produit
            </button>
        </form>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('product_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 