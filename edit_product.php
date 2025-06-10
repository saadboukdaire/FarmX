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
        // Set success message in session
        $_SESSION['edit_success'] = "Product updated successfully!";
        // Redirect back to marketplace
        header("Location: market.php");
        exit();
    } else {
        $error = "Failed to update product";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Edit Product</title>
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

        header {
            background-color: #3e8e41;
            color: white;
            padding: 8px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .search-bar {
            flex-grow: 1;
            margin: 0 20px;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-bar input {
            width: 100%;
            padding: 8px 15px 8px 35px;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-bar i {
            position: absolute;
            left: 12px;
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
        }

        .nav-links {
            display: flex;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            padding: 10px 15px;
            display: inline-block;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-links a:hover {
            color: #3e8e41;
            background-color: white;
        }

        .edit-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .edit-form h2 {
            text-align: center;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 15px 0;
            background: linear-gradient(to right, #3e8e41, #2d682f);
            border-radius: 8px;
            display: inline-block;
            width: 100%;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .edit-form input,
        .edit-form textarea,
        .edit-form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            outline: none;
        }

        .edit-form input:focus,
        .edit-form textarea:focus,
        .edit-form select:focus {
            border-color: #3e8e41;
        }

        .edit-form button {
            padding: 10px 20px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .edit-form button:hover {
            background-color: #2d682f;
        }

        .current-image {
            max-width: 200px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .image-upload-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px dashed #e0e0e0;
            border-radius: 8px;
            background-color: #f9f9f9;
            text-align: center;
        }

        .image-upload-section label {
            display: block;
            background-color: #3e8e41;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s ease;
            margin-top: 10px;
            max-width: 200px; /* Limit label width */
            margin-left: auto;
            margin-right: auto;
        }

        .image-upload-section label:hover {
            background-color: #2d682f;
        }

        .image-upload-section input[type="file"] {
            display: none; /* Hide the default file input */
        }

        .image-upload-section .image-preview {
            max-width: 150px;
            max-height: 150px;
            margin: 0 auto 10px auto;
            border-radius: 5px;
            object-fit: cover;
            border: 1px solid #e0e0e0;
        }

        .image-upload-section .upload-text {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .back-button {
            display: inline-block;
            color: #666;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .back-button i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="Images/logoinv.png" height="60px" title="Cultivez l'avenir, récoltez le succès">    
            </div>
            <div class="search-bar">
                <input type="text" placeholder="Search FarmX...">
                <i class='bx bx-search-alt-2'></i>
            </div>
            <div class="right-nav">
                <a href="main.php" title="Home">
                    <i class='bx bxs-home'></i>
                    <span class="tooltip">Home</span>
                </a>
                <a href="message.php" title="Messages">
                    <i class='bx bxs-message-dots'></i>
                    <span class="tooltip">Messages</span>
                </a>
                <a href="#" id="language-switch" title="Switch Language">
                    <i class='bx bx-globe'></i>
                    <span class="tooltip"><?php echo $_SESSION['language'] === 'en' ? 'Français' : 'English'; ?></span>
                </a>
                <a href="logout.php" title="Logout">
                    <i class='bx bxs-log-out'></i>
                    <span class="tooltip">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="edit-container">
        <a href="market.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Back to Marketplace
        </a>

        <div class="edit-form">
            <h2>Edit Product</h2>
            <?php if (isset($message)): ?>
                <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="edit-form" method="POST" enctype="multipart/form-data">
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['title']); ?>" required>

                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" rows="6" required><?php echo htmlspecialchars($product['description']); ?></textarea>

                <label for="product_price">Price (MAD):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>

                <label for="product_category">Category:</label>
                <select id="product_category" name="product_category" required>
                    <option value="vegetables" <?php echo $product['category'] === 'vegetables' ? 'selected' : ''; ?>>Vegetables</option>
                    <option value="fruits" <?php echo $product['category'] === 'fruits' ? 'selected' : ''; ?>>Fruits</option>
                    <option value="dairy" <?php echo $product['category'] === 'dairy' ? 'selected' : ''; ?>>Dairy Products</option>
                    <option value="meat" <?php echo $product['category'] === 'meat' ? 'selected' : ''; ?>>Meat & Poultry</option>
                    <option value="honey" <?php echo $product['category'] === 'honey' ? 'selected' : ''; ?>>Honey & Bee Products</option>
                    <option value="grains" <?php echo $product['category'] === 'grains' ? 'selected' : ''; ?>>Grains & Cereals</option>
                    <option value="other" <?php echo $product['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>

                <label>Current Image:</label>
                <div class="image-upload-section">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current Product Image" class="image-preview" id="imagePreview">
                    <?php else: ?>
                        <img src="Images/default-product.jpg" alt="Default Product Image" class="image-preview" id="imagePreview">
                    <?php endif; ?>
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <label for="product_image">Choose New Image</label>
                    <p class="upload-text">Upload a new image to replace the current one, or leave blank to keep it.</p>
                </div>

                <button type="submit">Update Product</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('language-switch').addEventListener('click', function(e) {
        e.preventDefault();
        const currentLang = '<?php echo $_SESSION['language'] ?? 'en'; ?>';
        const newLang = currentLang === 'en' ? 'fr' : 'en';
        
        fetch('update_language.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'language=' + newLang
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    });
    </script>
</body>
</html> 