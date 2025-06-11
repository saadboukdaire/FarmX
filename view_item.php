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
<html>
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

        .item-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 20px;
        }

        .item-title {
            font-size: 32px;
            color: #2d682f;
            font-weight: 600;
            line-height: 1.3;
            flex: 1;
        }

        .item-price {
            font-size: 36px;
            font-weight: bold;
            color: #3e8e41;
            background-color: #f0f7f0;
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px 0;
        }

        .item-price::before {
            content: 'MAD';
            font-size: 18px;
            color: #666;
            font-weight: normal;
        }

        .item-category {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
            font-weight: 500;
            display: inline-block;
            padding: 8px 16px;
            background-color: #f0f7f0;
            border-radius: 20px;
            color: #3e8e41;
        }

        .item-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .item-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .item-description {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #3e8e41;
        }

        .item-posted-time {
            font-size: 14px;
            color: #888;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            flex: 1;
        }

        .item-posted-time i {
            font-size: 18px;
            color: #3e8e41;
        }

        .action-row {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .seller-info {
            background-color: #f9f9f9;
            padding: 35px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            min-height: 250px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .seller-info h3 {
            color: #2d682f;
            margin-bottom: 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .seller-info h3 i {
            color: #3e8e41;
        }

        .seller-info p {
            margin: 15px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
        }

        .seller-info p i {
            color: #3e8e41;
            font-size: 20px;
        }

        .contact-seller {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #3e8e41;
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            flex: 1;
            justify-content: center;
        }

        .contact-seller:hover {
            background-color: #2d682f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(62, 142, 65, 0.2);
        }

        .contact-seller i {
            font-size: 20px;
        }

        .back-button {
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
        }

        .back-button:hover {
            background-color: #e0e0e0;
            color: #333;
        }

        .back-button i {
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .item-details {
                grid-template-columns: 1fr;
            }

            .item-header {
                flex-direction: column;
            }

            .item-price {
                width: 100%;
                justify-content: center;
            }

            .item-title {
                font-size: 24px;
            }
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

    <div class="item-container">
        <a href="market.php" class="back-button">
            <i class='bx bx-arrow-back'></i> Back to Marketplace
        </a>

        <div class="item-header">
            <h1 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h1>
        </div>

        <p class="item-category"><?php echo htmlspecialchars(ucfirst($item['category'])); ?></p>

        <?php if ($item['image_url']): ?>
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-image">
        <?php endif; ?>

        <div class="item-details">
            <div class="main-content">
                <div class="item-description">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </div>
                <div class="item-price"><?php echo number_format($item['price'], 2); ?></div>
                <div class="action-row">
                    <p class="item-posted-time">
                        <i class='bx bx-time'></i>
                        Posted: <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?>
                    </p>

                    <button onclick="contactSeller(<?php echo $item['seller_id']; ?>, <?php echo $item['id']; ?>)" class="contact-seller">
                        <i class='bx bx-message-square-dots'></i> Contact Seller
                    </button>
                </div>
            </div>

            <div class="sidebar">
                <div class="seller-info">
                    <h3><i class='bx bx-user'></i> Seller Information</h3>
                    <p><i class='bx bx-user-circle'></i> <strong>Name:</strong> <?php echo htmlspecialchars($item['username']); ?></p>
                    <?php if ($item['email']): ?>
                        <p><i class='bx bx-envelope'></i> <strong>Email:</strong> <?php echo htmlspecialchars($item['email']); ?></p>
                    <?php endif; ?>
                    <?php if ($item['phone']): ?>
                        <p><i class='bx bx-phone'></i> <strong>Phone:</strong> <?php echo htmlspecialchars($item['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
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