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
    $query .= " AND m.category = ?";
    $params[] = $category;
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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Marketplace</title>
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

        .search-filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .search-filter-form {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .marketplace-filter-search-bar {
            flex: 2;
            position: relative;
        }

        .marketplace-filter-search-bar input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
            display: block;
        }

        .marketplace-filter-search-bar input:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 0 2px rgba(62, 142, 65, 0.1);
        }

        .marketplace-filter-search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 20px;
        }

        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex: 1;
        }

        .filter-section select {
            flex: 1;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-section select:focus {
            border-color: #3e8e41;
            box-shadow: 0 0 0 2px rgba(62, 142, 65, 0.1);
        }

        .add-product-btn {
            padding: 12px 25px;
            background-color: #3e8e41;
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

        .add-product-btn:hover {
            background-color: #2d682f;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .add-product-btn i {
            font-size: 20px;
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
        }

        .product-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            gap: 10px;
        }

        .product-info h3 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .product-info .price {
            font-size: 24px;
            font-weight: bold;
            color: #3e8e41;
            margin: 0;
        }

        .product-info .category {
            font-size: 14px;
            color: #555;
            margin: 5px 0;
            font-weight: 500;
        }

        .product-info .description {
            font-size: 15px;
            color: #666;
            line-height: 1.5;
            margin: 0;
            flex-grow: 1;
        }

        .product-info .seller {
            font-size: 14px;
            color: #888;
            margin: 0;
            font-style: italic;
        }

        .product-info .posted-time {
            font-size: 13px;
            color: #999;
            margin: 0;
            margin-top: auto; /* Push to the bottom */
            text-align: right;
        }

        .product-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn, .view-btn, .contact-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: #3e8e41;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2d682f;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .view-btn {
            background-color: #3e8e41;
            color: white;
            text-decoration: none;
            text-align: center;
        }

        .view-btn:hover {
            background-color: #2d682f;
        }

        .contact-btn {
            background-color: #17a2b8;
            color: white;
        }

        .contact-btn:hover {
            background-color: #138496;
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

        /* Custom Popup Styles */
        .custom-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            min-width: 350px;
            text-align: center;
            border-top: 4px solid #3e8e41;
        }

        .custom-popup .popup-icon {
            font-size: 50px;
            color: #3e8e41;
            margin-bottom: 20px;
        }

        .custom-popup .popup-message {
            color: #333;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .custom-popup .popup-button {
            background-color: #3e8e41;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .custom-popup .popup-button:hover {
            background-color: #2d682f;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            backdrop-filter: blur(3px);
        }

        .search-button {
            padding: 12px 25px;
            background-color: #17a2b8; /* A color for action buttons */
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
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
                    <span class="tooltip">Messages</span>
                </a>
                <a href="main.php" title="Home">
                    <i class='bx bxs-home'></i>
                    <span class="tooltip">Home</span>
                </a>
                <a href="market.php" class="activated" title="Marketplace">
                    <i class='bx bxs-store'></i>
                    <span class="tooltip">Marketplace</span>
                </a>
            </div>
            <div class="right-nav">
                <div class="notification-container">
                    <a href="notifications.php" title="Notifications">
                        <i class='bx bx-bell notification-icon'></i>
                        <span class="notification-badge">0</span>
                        <span class="tooltip">Notifications</span>
                    </a>
                </div>
                <a href="profile.php" title="Profile">
                    <i class='bx bxs-user'></i>
                    <span class="tooltip">Profile</span>
                </a>
                <a href="logout.php" title="Logout">
                    <i class='bx bx-log-out'></i>
                    <span class="tooltip">Logout</span>
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

    <div class="marketplace-container">
        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <form id="searchForm" method="GET" class="search-filter-form">
                <div class="marketplace-filter-search-bar">
                    <input type="text" id="searchInput" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <i class='bx bx-search-alt-2'></i>
                </div>
                <div class="filter-section">
                    <select id="categoryFilter" name="category">
                        <option value="">All Categories</option>
                        <option value="vegetables" <?php echo $category === 'vegetables' ? 'selected' : ''; ?>>Vegetables</option>
                        <option value="fruits" <?php echo $category === 'fruits' ? 'selected' : ''; ?>>Fruits</option>
                        <option value="dairy" <?php echo $category === 'dairy' ? 'selected' : ''; ?>>Dairy Products</option>
                        <option value="meat" <?php echo $category === 'meat' ? 'selected' : ''; ?>>Meat & Poultry</option>
                        <option value="honey" <?php echo $category === 'honey' ? 'selected' : ''; ?>>Honey & Bee Products</option>
                        <option value="grains" <?php echo $category === 'grains' ? 'selected' : ''; ?>>Grains & Cereals</option>
                        <option value="other" <?php echo $category === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <button type="submit" class="search-button">
                        <i class='bx bx-search'></i> Search
                    </button>
                    <?php if (isset($_SESSION['user_tag']) && $_SESSION['user_tag'] === 'FarmX Producer'): ?>
                    <button type="button" onclick="window.location.href='add_product.php'" class="add-product-btn">
                        <i class='bx bx-plus'></i> Add Product
                    </button>
                    <?php endif; ?>
                </div>
            </form>
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
                <p>No products available yet. Be the first to list something!</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?php echo htmlspecialchars($product['category']); ?>" data-product-id="<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                            <p class="category">Category: <?php echo htmlspecialchars(ucfirst($product['category'])); ?></p>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="seller">Seller: <?php echo htmlspecialchars($product['username']); ?></p>
                            <p class="posted-time">Posted: <?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></p>
                            <?php if ($product['seller_id'] == $_SESSION['user_id']): ?>
                                <div class="product-actions">
                                    <button onclick="editProduct(<?php echo $product['id']; ?>)" class="edit-btn">
                                        <i class='bx bx-edit'></i> Edit Product
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="delete-btn">
                                        <i class='bx bx-trash'></i> Delete Product
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="product-actions">
                                    <a href="view_item.php?id=<?php echo $product['id']; ?>" class="view-btn">
                                        <i class='bx bx-show'></i> View Details
                                    </a>
                                    <button onclick="contactSeller(<?php echo $product['seller_id']; ?>, <?php echo $product['id']; ?>)" class="contact-btn">
                                        <i class='bx bx-message-square-dots'></i> Contact
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
                showPopup('Message sent successfully! The seller will be notified.');
            } else {
                showPopup('Failed to send message: ' + (response.error || 'Unknown error'), false);
            }
        })
        .catch(error => {
            showPopup('Error sending message: ' + error.message, false);
        });
    }
    
    function editProduct(productId) {
        window.location.href = `edit_product.php?id=${productId}`;
    }

    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product?')) {
            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('delete_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPopup('Item removed successfully!');
                    // Remove the product card from the DOM
                    const productCard = document.querySelector(`.product-card[data-category][data-product-id="${productId}"]`);
                    if (productCard) {
                        productCard.remove();
                    }
                } else {
                    showPopup(data.message || 'Failed to delete product', false);
                }
            })
            .catch(error => {
                showPopup('An error occurred while deleting the product', false);
            });
        }
    }

    // Close popup when clicking outside
    document.getElementById('popupOverlay').addEventListener('click', closePopup);

    // Update search and filter functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        document.getElementById('searchForm').submit();
    });

    document.getElementById('categoryFilter').addEventListener('change', function() {
        document.getElementById('searchForm').submit();
    });
</script>
</body>
</html>