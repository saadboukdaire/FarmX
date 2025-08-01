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
        $message = "Product added successfully!";
    } else {
        $error = "Failed to add product";
    }
}

// Fetch all marketplace items with search and filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT m.*, u.username 
          FROM marketplace_items m
          JOIN users u ON m.seller_id = u.id
          WHERE m.status = 'available'";

$params = [];

if ($search) {
    $query .= " AND (m.title LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    if ($category === 'my_products' && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'farmer') {
        $query .= " AND m.seller_id = ?";
        $params[] = $_SESSION['user_id'];
    } else {
        $query .= " AND m.category = ?";
        $params[] = $category;
    }
}

$query .= " ORDER BY m.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch current user's username for the seller info
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$current_username = $user['username'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Marketplace</title>
    <link rel="icon" href="Images/logo.jpg">   
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            overflow-y: scroll; /* Always show vertical scrollbar */
            margin-right: calc(100vw - 100%); /* Compensate for scrollbar width */
        }

        /* Header */
        header {
            background-color: #3e8e41;
            color: white;
            padding: 4px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            box-sizing: border-box;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            width: 100%;
        }

        .logo {
            margin-left: -140px;
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 65px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
        }

        .nav-links a i {
            font-size: 24px;
        }

        .nav-links a:hover {
            color: #3e8e41;
            background-color: white;
            transform: translateY(-2px);
        }

        .nav-links a.activated {
            color: #3e8e41;
            background-color: white;
        }

        .tooltip {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .nav-links a:hover .tooltip {
            opacity: 1;
            visibility: visible;
            bottom: -35px;
        }

        .right-nav {
            display: flex;
            gap: 12px;
            margin-right: -70px;
            position: absolute;
            right: 0;
        }

        .right-nav a {
            color: white;
            text-decoration: none;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .right-nav a i {
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .right-nav a:hover {
            color: #3e8e41;
            background-color: white;
            transform: translateY(-2px);
        }

        .right-nav a:hover .tooltip {
            opacity: 1;
            visibility: visible;
            bottom: -35px;
        }

        .notification-container {
            position: relative;
            margin-left: 20px;
        }

        .notification-icon {
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .notification-icon:hover {
            color: #3e8e41;
            background-color: white;
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            display: none;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        /* Marketplace Styles */
        .marketplace-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .marketplace-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }

        .marketplace-title {
            font-size: 28px;
            color: #2d682f;
            font-weight: 600;
        }

        .add-product-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #3e8e41;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(62, 142, 65, 0.2);
        }

        .add-product-btn:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(62, 142, 65, 0.3);
        }

        .add-product-btn i {
            font-size: 20px;
        }

        .search-section {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            position: relative;
        }

        .search-form input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .search-form input:focus {
            outline: none;
            border-color: #3e8e41;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(62, 142, 65, 0.1);
        }

        .search-form button {
            background-color: #3e8e41;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-form button:hover {
            background-color: #2d682f;
            transform: translateY(-1px);
        }

        .search-form button i {
            font-size: 20px;
        }

        .category-filter {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
            padding: 5px;
        }

        .category-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            background-color: #f0f7f0;
            color: #3e8e41;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .category-btn:hover {
            background-color: #3e8e41;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(62, 142, 65, 0.2);
        }

        .category-btn.active {
            background-color: #3e8e41;
            color: white;
            box-shadow: 0 4px 8px rgba(62, 142, 65, 0.2);
        }

        .my-products-btn {
            background-color: #f0f7f0;
            color: #3e8e41;
            font-weight: 500;
        }

        .my-products-btn:hover {
            background-color: #3e8e41;
            color: white;
        }

        .my-products-btn.active {
            background-color: #2d682f;
            color: white;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .search-form button {
                width: 100%;
                justify-content: center;
            }

            .category-filter {
                justify-content: center;
            }

            .category-btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .marketplace-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .add-product-btn {
                width: 100%;
                justify-content: center;
            }
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .product-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #e0e0e0;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 1px solid #e0e0e0;
            transition: transform 0.3s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            gap: 10px;
            position: relative;
        }

        .product-info h3 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
            line-height: 1.4;
        }

        .product-info .price {
            font-size: 24px;
            font-weight: bold;
            color: #3e8e41;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .product-info .category {
            font-size: 14px;
            color: #555;
            margin: 5px 0;
            font-weight: 500;
            display: inline-block;
            padding: 4px 12px;
            background-color: #f0f7f0;
            border-radius: 20px;
            color: #3e8e41;
        }

        .product-info .description {
            font-size: 15px;
            color: #666;
            line-height: 1.5;
            margin: 0;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-info .seller {
            font-size: 14px;
            color: #888;
            margin: 0;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .product-info .seller i {
            color: #3e8e41;
        }

        .product-info .posted-time {
            font-size: 13px;
            color: #999;
            margin: 0;
            margin-top: auto;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 5px;
        }

        .product-info .posted-time i {
            font-size: 16px;
        }

        .product-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .edit-btn, .delete-btn, .view-btn, .contact-btn {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .edit-btn {
            background-color: #3e8e41;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(62, 142, 65, 0.2);
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        .view-btn {
            background-color: #3e8e41;
            color: white;
        }

        .view-btn:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(62, 142, 65, 0.2);
        }

        .contact-btn {
            background-color: #17a2b8;
            color: white;
        }

        .contact-btn:hover {
            background-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.2);
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message::before {
            content: '✓';
            font-size: 18px;
            font-weight: bold;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message::before {
            content: '!';
            font-size: 18px;
            font-weight: bold;
        }

        /* Delete Confirmation Modal */
        .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .delete-modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .delete-modal-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .delete-modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .delete-modal-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .delete-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .delete-modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .delete-modal-cancel {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
        }

        .delete-modal-cancel:hover {
            background-color: #e9ecef;
        }

        .delete-modal-confirm {
            background-color: #dc3545;
            color: white;
        }

        .delete-modal-confirm:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        /* Custom Popup Styles */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .custom-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            text-align: center;
            min-width: 300px;
            animation: popupSlideIn 0.3s ease;
        }

        @keyframes popupSlideIn {
            from {
                transform: translate(-50%, -60%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        .popup-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .popup-message {
            font-size: 16px;
            color: #333;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .popup-button {
            padding: 12px 30px;
            background-color: #3e8e41;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .popup-button:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(62, 142, 65, 0.2);
        }

        .search-button {
            padding: 12px 25px;
            background-color: #17a2b8;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .search-button:hover {
            background-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.2);
        }

        .search-button i {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <a href="main.php">
                    <img src="Images/logoinv.png" alt="FarmX Logo">
                </a>
            </div>
            <div class="nav-links">
                <a href="message.php" title="Messages">
                    <i class='bx bxs-message-dots'></i>
                </a>
                <a href="main.php" title="Home">
                    <i class='bx bxs-home'></i>
                </a>
                <a href="market.php" class="activated" title="Marketplace">
                    <i class='bx bxs-store'></i>
                </a>
            </div>
            <div class="right-nav">
                <div class="notification-container">
                    <a href="notifications.php" title="Notifications">
                        <i class='bx bx-bell notification-icon'></i>
                        <span class="notification-badge">0</span>
                    </a>
                </div>
                <a href="profile.php" title="Profile">
                    <i class='bx bxs-user'></i>
                </a>
                <a href="logout.php" title="Logout">
                    <i class='bx bx-log-out'></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Add this right after the opening body tag -->
    <div class="popup-overlay" id="popupOverlay"></div>
    <div class="custom-popup" id="customPopup">
        <div class="popup-icon">
            <i class='bx bx-check-circle'></i>
        </div>
        <div class="popup-message" id="popupMessage"></div>
        <button class="popup-button" onclick="closePopup()">OK</button>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">
                <i class='bx bx-trash'></i>
            </div>
            <h3 class="delete-modal-title">Delete Product</h3>
            <p class="delete-modal-message">Are you sure you want to delete this product? This action cannot be undone.</p>
            <div class="delete-modal-buttons">
                <button class="delete-modal-btn delete-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="delete-modal-btn delete-modal-confirm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <div class="marketplace-container">
        <div class="marketplace-header">
            <h1 class="marketplace-title">
                <i class="fas fa-seedling" style="color:#4CAF50;margin-right:8px;"></i>
                Marketplace
            </h1>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'farmer'): ?>
                <a href="add_product.php" class="add-product-btn">
                    <i class='bx bx-plus-circle'></i>
                    Ajouter un Produit
                </a>
            <?php endif; ?>
        </div>

        <div class="search-section">
            <form action="market.php" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Rechercher des produits..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">
                    <i class='bx bx-search'></i>
                    Rechercher
                </button>
            </form>

            <div class="category-filter">
                <a href="market.php" class="category-btn <?php echo empty($category) ? 'active' : ''; ?>">
                    <i class='bx bx-grid-alt'></i>
                    Tous
                </a>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'farmer'): ?>
                    <a href="market.php?category=my_products" class="category-btn my-products-btn <?php echo $category === 'my_products' ? 'active' : ''; ?>">
                        <i class='bx bx-package'></i>
                        Mes Produits
                    </a>
                <?php endif; ?>
                <a href="market.php?category=legumes" class="category-btn <?php echo $category === 'legumes' ? 'active' : ''; ?>">
                    <i class='bx bx-leaf'></i>
                    Légumes
                </a>
                <a href="market.php?category=fruits" class="category-btn <?php echo $category === 'fruits' ? 'active' : ''; ?>">
                    <i class='bx bx-food-menu'></i>
                    Fruits
                </a>
                <a href="market.php?category=produits_laitiers" class="category-btn <?php echo $category === 'produits_laitiers' ? 'active' : ''; ?>">
                    <i class='bx bx-droplet'></i>
                    Produits Laitiers
                </a>
                <a href="market.php?category=viande" class="category-btn <?php echo $category === 'viande' ? 'active' : ''; ?>">
                    <i class='bx bx-meat'></i>
                    Viande
                </a>
                <a href="market.php?category=miel" class="category-btn <?php echo $category === 'miel' ? 'active' : ''; ?>">
                    <i class='bx bx-hive'></i>
                    Miel & Produits Apicoles
                </a>
                <a href="market.php?category=cereales" class="category-btn <?php echo $category === 'cereales' ? 'active' : ''; ?>">
                    <i class='bx bx-wheat'></i>
                    Céréales
                </a>
                <a href="market.php?category=materiel_agricole" class="category-btn <?php echo $category === 'materiel_agricole' ? 'active' : ''; ?>">
                    <i class='bx bx-tractor'></i>
                    Matériel Agricole
                </a>
                <a href="market.php?category=produits_transformes" class="category-btn <?php echo $category === 'produits_transformes' ? 'active' : ''; ?>">
                    <i class='bx bx-factory'></i>
                    Produits Transformés
                </a>
                <a href="market.php?category=animaux" class="category-btn <?php echo $category === 'animaux' ? 'active' : ''; ?>">
                    <i class='bx bx-cow'></i>
                    Animaux d'Élevage
                </a>
                <a href="market.php?category=terrain" class="category-btn <?php echo $category === 'terrain' ? 'active' : ''; ?>">
                    <i class='bx bx-map'></i>
                    Terrains Agricoles
                </a>
                <a href="market.php?category=autre" class="category-btn <?php echo $category === 'autre' ? 'active' : ''; ?>">
                    <i class='bx bx-dots-horizontal-rounded'></i>
                    Autre
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['add_success'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['add_success']) ?></div>
            <?php unset($_SESSION['add_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['edit_success'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['edit_success']) ?></div>
            <?php unset($_SESSION['edit_success']); ?>
        <?php endif; ?>

        <!-- Product List -->
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <p>Aucun produit disponible pour le moment. Soyez le premier à ajouter un produit !</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-product-id="<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="price"><?php echo number_format($product['price'], 2); ?> MAD</p>
                            <p class="category">Catégorie: <?php echo htmlspecialchars(ucfirst($product['category'])); ?></p>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="seller">Vendeur: <?php echo htmlspecialchars($product['username']); ?></p>
                            <p class="posted-time">Publié: <?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></p>
                            <?php if ($product['seller_id'] == $_SESSION['user_id']): ?>
                                <div class="product-actions">
                                    <button onclick="editProduct(<?php echo $product['id']; ?>)" class="edit-btn">
                                        <i class='bx bx-edit'></i> Modifier Produit
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="delete-btn">
                                        <i class='bx bx-trash'></i> Supprimer Produit
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="product-actions">
                                    <a href="view_item.php?id=<?php echo $product['id']; ?>" class="view-btn">
                                        <i class='bx bx-show'></i> Voir Détails
                                    </a>
                                    <button onclick="contactSeller(<?php echo $product['seller_id']; ?>, <?php echo $product['id']; ?>)" class="contact-btn">
                                        <i class='bx bx-message-square-dots'></i> Contacter
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

   <script>
    // Function to update notification count
    function updateNotificationCount() {
        fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update notification count when page loads
    document.addEventListener('DOMContentLoaded', updateNotificationCount);

    // Update notification count every 30 seconds
    setInterval(updateNotificationCount, 30000);

    function showPopup(message, isSuccess = true) {
        const popup = document.getElementById('customPopup');
        const overlay = document.getElementById('popupOverlay');
        const popupMessage = document.getElementById('popupMessage');
        const popupIcon = popup.querySelector('.popup-icon i');

        popupMessage.textContent = message;
        popupIcon.className = isSuccess ? 'bx bx-check-circle' : 'bx bx-x-circle';
        popupIcon.style.color = isSuccess ? '#3e8e41' : '#dc3545';
        
        popup.style.display = 'block';
        overlay.style.display = 'block';
    }

    function closePopup() {
        const popup = document.getElementById('customPopup');
        const overlay = document.getElementById('popupOverlay');
        popup.style.display = 'none';
        overlay.style.display = 'none';
    }

    function contactSeller(sellerId, itemId) {
        const formData = new FormData();
        formData.append('seller_id', sellerId);
        formData.append('item_id', itemId);
        
        fetch('send_message_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                if (response.message === 'Message already sent') {
                    showPopup('Vendeur déjà informé de votre intérêt.');
                } else {
                    showPopup('Le vendeur a été informé de votre intérêt.');
                }
            } else {
                showPopup('Échec de l\'information du vendeur: ' + (response.error || 'Erreur inconnue'), false);
            }
        })
        .catch(error => {
            showPopup('Une erreur s\'est produite lors de l\'information du vendeur: ' + error.message, false);
        });
    }
    
    function editProduct(productId) {
        window.location.href = `edit_product.php?id=${productId}`;
    }

    function deleteProduct(productId) {
        const modal = document.getElementById('deleteModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        modal.style.display = 'flex';
        
        confirmBtn.onclick = function() {
            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('delete_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPopup('Article supprimé avec succès !');
                    // Remove the product card from the DOM
                    const productCard = document.querySelector(`.product-card[data-category][data-product-id="${productId}"]`);
                    if (productCard) {
                        productCard.remove();
                    }
                } else {
                    showPopup(data.message || 'Échec de la suppression du produit', false);
                }
                closeDeleteModal();
            })
            .catch(error => {
                showPopup('Une erreur s\'est produite lors de la suppression du produit', false);
                closeDeleteModal();
            });
        };
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Update search and filter functionality
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput').value;
        const categoryFilter = document.getElementById('categoryFilter').value;
        
        // Update URL with search parameters
        const url = new URL(window.location.href);
        url.searchParams.set('search', searchInput);
        url.searchParams.set('category', categoryFilter);
        
        // Navigate to the new URL
        window.location.href = url.toString();
    });

    // Allow Enter key to submit the form
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchForm').dispatchEvent(new Event('submit'));
        }
    });

    document.getElementById('popupOverlay').addEventListener('click', closePopup);
</script>
</body>
</html>