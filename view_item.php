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

// Get item ID from URL
$item_id = $_GET['id'] ?? null;
if (!$item_id) {
    header("Location: market.php");
    exit();
}

// Fetch item details
$stmt = $pdo->prepare("SELECT m.*, u.username, u.email, u.phone 
                      FROM marketplace_items m
                      JOIN users u ON m.seller_id = u.id
                      WHERE m.id = ? AND m.status = 'available'");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: market.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - <?php echo htmlspecialchars($item['title']); ?></title>
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

        .item-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .item-title {
            font-size: 24px;
            color: #3e8e41;
        }

        .item-price {
            font-size: 24px;
            font-weight: bold;
            color: #2d682f;
        }

        .item-category {
            font-size: 18px;
            color: #555;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .item-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .item-details {
            margin-bottom: 30px;
        }

        .item-description {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 20px;
        }

        .item-posted-time {
            font-size: 14px;
            color: #888;
            margin-top: 10px;
            text-align: right;
        }

        .seller-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .seller-info h3 {
            color: #3e8e41;
            margin-bottom: 10px;
        }

        .seller-info p {
            margin: 5px 0;
            color: #666;
        }

        .contact-seller {
            display: inline-block;
            background-color: #3e8e41;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .contact-seller:hover {
            background-color: #2d682f;
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

        /* Custom Popup Styles */
        .custom-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            min-width: 300px;
            text-align: center;
            border-top: 4px solid #3e8e41;
        }

        .custom-popup .popup-icon {
            font-size: 48px;
            color: #3e8e41;
            margin-bottom: 15px;
        }

        .custom-popup .popup-message {
            color: #333;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .custom-popup .popup-button {
            background-color: #3e8e41;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .custom-popup .popup-button:hover {
            background-color: #2d682f;
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
            <div class="nav-links">
                <a href="main.php">Home</a>
                <a href="message.php">Messages</a>
                <a href="market.php" class="activated">Marketplace</a>
                <a href="profile.php">Profile</a>
            </div>
        </div>
    </header>

    <div class="item-container">
        <a href="market.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Back to Marketplace
        </a>

        <div class="item-header">
            <h1 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h1>
            <div class="item-price"><?php echo number_format($item['price'], 2); ?> MAD</div>
            <p class="item-category">Category: <?php echo htmlspecialchars(ucfirst($item['category'])); ?></p>
        </div>

        <?php if ($item['image_url']): ?>
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-image">
        <?php endif; ?>

        <div class="item-details">
            <div class="item-description">
                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
            </div>
            <p class="item-posted-time">Posted: <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?></p>

            <div class="seller-info">
                <h3>Seller Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($item['username']); ?></p>
                <?php if ($item['email']): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($item['email']); ?></p>
                <?php endif; ?>
                <?php if ($item['phone']): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($item['phone']); ?></p>
                <?php endif; ?>
            </div>

            <button onclick="contactSeller(<?php echo $item['seller_id']; ?>, <?php echo $item['id']; ?>)" class="contact-seller">
                Contact Seller
            </button>

        </div>
    </div>

    <!-- Add this right after the opening body tag -->
    <div class="popup-overlay" id="popupOverlay"></div>
    <div class="custom-popup" id="customPopup">
        <div class="popup-icon">
            <i class='bx bx-check-circle'></i>
        </div>
        <div class="popup-message" id="popupMessage"></div>
        <button class="popup-button" onclick="closePopup()">OK</button>
    </div>

    <script>
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
                        showPopup('Seller already notified about your interest in this item.');
                    } else {
                        showPopup('Seller has been notified about your interest in this item.');
                    }
                } else {
                    showPopup('Failed to notify seller: ' + (response.error || 'Unknown error'), false);
                }
            })
            .catch(error => {
                showPopup('Error notifying seller: ' + error.message, false);
            });
        }

        // Close popup when clicking outside
        document.getElementById('popupOverlay').addEventListener('click', closePopup);
    </script>
</body>
</html> 