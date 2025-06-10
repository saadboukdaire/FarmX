<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle post creation if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    // Get the active user's details
    if (!isset($_SESSION['user_id'])) {
        die(json_encode(["status" => "error", "message" => "User not logged in."]));
    }

    $userId = $_SESSION['user_id'];

    // Get user's profile picture from the database
    $userSql = "SELECT username, profile_pic FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
    $userStmt->close();

    // Get post data
    $content = $_POST['content'];
    $mediaUrl = isset($_POST['media_url']) ? $_POST['media_url'] : '';
    $username = $userData['username'];
    $profilePic = $userData['profile_pic'] ?: 'Images/profile.jpg';

    // Insert post into the database
    $sql = "INSERT INTO posts (user_id, username, content, media_url, profile_pic) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userId, $username, $content, $mediaUrl, $profilePic);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Post created successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error creating post: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit(); // Stop further execution after handling the POST request
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmX - Agricultural Social Network</title>
    <link rel="icon" href="Images/logo.jpg">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Verdana, sans-serif;
        }

        /* General styles */
        body {
            background-color: #e8efe4;
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
       /* Layout container */
.layout-container {
    display: flex;
    justify-content: space-between;
    padding: 0 5px;
    margin-top: 20px;
    align-items: stretch;
    height: calc(100vh - 100px);
    width: 100%;
    box-sizing: border-box; /* Ensure padding is included in width calculation */
}

.left-section, .right-section, .middle-section {
    border-radius: 8px;
    padding: 15px;
    overflow-y: auto; /* Allow scrolling if content overflows */
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
    background-color: white;
    height: 100%; /* Ensure all sections take full height of the container */
    display: flex;
    flex-direction: column; /* Ensure content inside sections is properly aligned */
}

.left-section, .right-section {
    flex: 1; /* Allow left and right sections to grow equally */
    margin: 0 5px;
}

.middle-section {
    flex: 4; /* Middle section takes more space */
    margin: 0 5px;
}

        /* Post Composer */
       /* Updated Middle Section Styles */
.post-creation {
    background-color: white;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.post-input {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #2d682f; /* Darker green */
}

.post-input input {
    flex-grow: 1;
    padding: 0.6rem 1rem;
    border-radius: 20px;
    border: 1px solid #ccc;
    background-color: #e8efe4; /* Light green */
    outline: none;
}

.post-actions {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #ccc;
    padding-top: 0.8rem;
}

.post-type {
    display: flex;
    gap: 1rem;
}

.action-btn {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    color: #4A7C59; /* Accent green */
}

.post-btn {
    background-color: #3e8e41; /* Primary green */
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.post-btn:hover {
    background-color: #2d682f; /* Darker green */
}

.post {
    background-color: white;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.post-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.post-info h3 {
    font-size: 1rem;
    margin: 0;
}

.post-info span {
    font-size: 0.8rem;
    color: #666;
}

.post-content {
    margin: 0.5rem 0;
}

.post-image {
    width: 100%;
    max-height: 300px;
    border-radius: 10px;
    overflow: hidden; /* Ensure the media stays within the container */
    margin: 0.5rem 0;
    display: flex;
    justify-content: center; /* Center the media horizontally */
    align-items: center; /* Center the media vertically */
    background-color: #f0f0f0; /* Add a background color for empty space */
}

.post-image img,
.post-image video {
    width: 100%;
    max-height: 300px;
    object-fit: contain; /* Show the entire image without cropping */
    border-radius: 10px;
}

.post-footer {
    display: flex;
    justify-content: space-between;
    padding-top: 0.5rem;
    border-top: 1px solid #ccc;
    margin-top: 0.5rem;
}

.post-reactions {
    display: flex;
    gap: 1rem;
}

.reaction {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    cursor: pointer;
}
/* Like button animation */
@keyframes likeAnimation {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.reaction.liked {
    color: #3e8e41; /* Green color for liked state */
}

.reaction.liked i {
    animation: likeAnimation 0.3s ease-in-out; /* Apply animation */
}
/* Comments Section */
.comments-section {
    margin-top: 10px;
    padding: 10px;
    border-top: 1px solid #ccc;
    background-color: #f9f9f9; /* Light background for the comments section */
    border-radius: 8px;
}

.comment-input {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.comment-input input {
    flex-grow: 1;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 20px;
    outline: none;
    font-size: 14px;
}

.comment-input button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    background-color: #3e8e41; /* FarmX green */
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.comment-input button:hover {
    background-color: #2d682f; /* Darker green on hover */
}

.comment-input button i {
    font-size: 16px;
}

.comments-list {
    max-height: 150px;
    overflow-y: auto;
    padding: 5px;
}

.comment {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    padding: 10px;
    background-color: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.comment-profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover; /* Ensure the image fits well */
}

.comment-content {
    flex-grow: 1;
}

.comment-content strong {
    color: #3e8e41; /* FarmX green for the username */
    font-weight: 600;
}

.comment-content p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #333;
}

/* Scrollbar styling for comments list */
.comments-list::-webkit-scrollbar {
    width: 8px;
}

.comments-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.comments-list::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}

.comments-list::-webkit-scrollbar-thumb:hover {
    background: #999;
}
/* Weather Widget */
.weather-widget {
    background-color: white;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
}

.weather-widget h3 {
    margin-bottom: 1rem;
    font-size: 1.2rem;
    color: #3e8e41; /* FarmX green */
}

#weather-info {
    font-size: 0.9rem;
}

#weather-info p {
    margin: 0.5rem 0;
}

.weather-icon {
    width: 50px;
    height: 50px;
    margin-bottom: 0.5rem;
}

.weather-status {
    font-weight: bold;
    color: #3e8e41; /* FarmX green */
}

.bad-weather {
    color: #ff4d4d; /* Red for bad weather */
}


/* Add styles for the mini calendar */
.mini-calendar {
            background-color: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .mini-calendar h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: #3e8e41; /* FarmX green */
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }

        .calendar-day {
            text-align: center;
            padding: 5px;
            border-radius: 5px;
            background-color: #e8efe4; /* Light green */
        }

        .calendar-day.today {
            background-color: #3e8e41; /* FarmX green */
            color: white;
        }

        .season-display {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #333;
        }

        .season-display strong {
            color: #3e8e41; /* FarmX green */
        }

        /* Notification styles */
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

        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: #f5f5f5;
        }

        .notification-item.unread {
            background-color: #f0f7ff;
        }

        .notification-item .sender {
            font-weight: bold;
            color: #3e8e41;
        }

        .notification-item .time {
            font-size: 12px;
            color: #666;
        }

        .notification-item .message {
            margin-top: 5px;
            color: #333;
        }

        .notification-wrapper {
            position: relative;
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
            margin-top: 10px;
        }

        .notification-wrapper:hover .notification-dropdown {
            display: block;
        }

        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h3 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .view-all {
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: #f5f5f5;
        }

        .notification-item.unread {
            background-color: #f0f7ff;
        }

        .notification-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .notification-content {
            flex: 1;
        }

        .notification-text {
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }

        .notification-time {
            font-size: 12px;
            color: #666;
        }

        .username-link {
            color: #3e8e41;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .username-link:hover {
            color: #2d682f;
            text-decoration: underline;
        }

        .user-tag {
            font-size: 0.9em;
            color: #666;
            margin-left: 8px;
            font-style: italic;
        }

        .post-time {
            display: block;
            font-size: 0.8em;
            color: #888;
            margin-top: 2px;
        }

        /* Add custom alert styles */
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
    <!-- Add custom alert modal -->
    <div id="customAlert" class="custom-alert">
        <div class="alert-content">
            <i class='bx bxs-error-circle'></i>
            <p id="alertMessage"></p>
            <button id="alertCloseButton">OK</button>
        </div>
    </div>

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
                <a href="main.php" class="activated" title="Home">
                    <i class='bx bxs-home'></i>
                    <span class="tooltip">Home</span>
                </a>
                <a href="market.php" title="Marketplace">
                    <i class='bx bxs-store'></i>
                    <span class="tooltip">Marketplace</span>
                </a>
            </div>
            <div class="right-nav">
                <a href="notifications.php" class="notification-container" title="Notifications">
                    <i class='bx bx-bell notification-icon'></i>
                    <span class="notification-badge">0</span>
                    <span class="tooltip">Notifications</span>
                </a>
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

    <div class="layout-container">
    <div class="left-section">
            <!-- Mini Calendar -->
            <div class="mini-calendar">
                <h3>Calendar</h3>
                <div id="calendar"></div>
                <div class="season-display">
                    <strong>Season:</strong> <span id="season"></span>
                </div>
            </div>
        </div>

        <div class="middle-section">
            <!-- Post Creation -->
            <div class="post-creation">
                <div class="post-input">
                    <!-- Display the active user's profile picture -->
                    <img id="profile-picture" src="<?php echo isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] . '?t=' . time() : 'Images/profile.jpg'; ?>" alt="Profile Picture" class="profile-pic">
                    <input type="text" id="post-content" placeholder="Share farming updates, questions, or tips...">
                </div>
                <div class="post-actions">
                    <div class="post-type">
                        <div class="action-btn" onclick="openFilePicker('image')">
                            <i class='bx bx-image'></i>
                            <span>Photo</span>
                        </div>
                        <div class="action-btn" onclick="openFilePicker('video')">
                            <i class='bx bx-video'></i>
                            <span>Video</span>
                        </div>
                    </div>
                    <button class="post-btn" onclick="createPost()">Post Update</button>
                </div>
                <!-- Hidden file input for media upload -->
                <input type="file" id="media-upload" style="display: none;" onchange="handleMediaUpload(event)">
            </div>

            <!-- Posts Container -->
            <div id="posts-container">
                <!-- Posts will be dynamically loaded here -->
            </div>
        </div>

        <div class="right-section">
    <!-- Weather Widget -->
    <div class="weather-widget">
        <h3>Weather</h3>
        <div id="weather-info">
            <p>Loading weather data...</p>
        </div>
    </div>
</div>
    </div>

    <script>
    // Function to open the file picker
    function openFilePicker(type) {
        const fileInput = document.getElementById('media-upload');
        fileInput.setAttribute('accept', type === 'image' ? 'image/*' : 'video/*');
        fileInput.click();
    }

    // Function to handle media upload
    function handleMediaUpload(event) {
        const file = event.target.files[0];
        if (file) {
            // Upload the file to the server and get the URL
            const formData = new FormData();
            formData.append('file', file);

            fetch('upload_media.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Store the media URL for the post
                    window.mediaUrl = data.url;
                } else {
                    showAlert('Error uploading media: ' + data.message, 'error');
                }
            });
        }
    }

   // Function to create a post
function createPost() {
    const content = document.getElementById('post-content').value.trim();
    const mediaUrl = window.mediaUrl || '';

    // Validate that the post has either content or media
    if (!content && !mediaUrl) {
        showAlert('Post cannot be blank. Please add content or attach a photo/video.', 'error');
        return; // Stop execution if both content and media are empty
    }

    const formData = new FormData();
    formData.append('content', content);
    formData.append('media_url', mediaUrl);

    fetch('main.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Clear the input fields
            document.getElementById('post-content').value = '';
            window.mediaUrl = ''; // Reset media URL
            document.getElementById('media-upload').value = ''; // Clear the file input

            // Reload posts
            loadPosts();
        } else {
            showAlert('Error creating post: ' + data.message, 'error');
        }
    });
}

    // Function to load posts
    function loadPosts() {
        fetch('get_posts.php')
        .then(response => response.json())
        .then(data => {
            const postsContainer = document.getElementById('posts-container');
            postsContainer.innerHTML = '';

            data.forEach(post => {
                // Default likes and comments to 0 if undefined
                const likes = post.likes || 0;
                const comments = post.comments || 0;

                // Check if the current user has liked the post
                const isLiked = post.is_liked || false;

                const postHtml = `
                    <div class="post" id="post-${post.id}">
                        <div class="post-header">
                            <img src="${post.profile_pic || 'Images/profile.jpg'}" alt="${post.username}'s Profile Picture" class="profile-pic" onerror="this.src='Images/profile.jpg'">
                            <div class="post-info">
                                <h3><a href="profile.php?id=${post.user_id}" class="username-link">${post.username}</a></h3>
                                <span class="user-tag">${post.user_tag || ''}</span>
                                <span class="post-time">${new Date(post.created_at).toLocaleString()}</span>
                            </div>
                        </div>
                        <div class="post-content">
                            <p>${post.content}</p>
                        </div>
                        ${post.media_url ? `
                            <div class="post-image">
                                ${post.media_url.endsWith('.mp4') ? `
                                    <video controls>
                                        <source src="${post.media_url}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                ` : `
                                    <img src="${post.media_url}" alt="Post Media">
                                `}
                            </div>
                        ` : ''}
                        <div class="post-footer">
                            <div class="post-reactions">
                                <div class="reaction ${isLiked ? 'liked' : ''}" onclick="toggleLike(${post.id})">
                                    <i class='bx ${isLiked ? 'bxs-heart' : 'bx-heart'}'></i>
                                    <span>${likes}</span>
                                </div>
                                <div class="reaction" onclick="toggleComments(${post.id})">
                                    <i class='bx bx-comment'></i>
                                    <span>${comments}</span>
                                </div>
                            </div>
                        </div>
                        <div class="comments-section" id="comments-${post.id}" style="display: none;">
                            <div class="comment-input">
                                <input type="text" placeholder="Write a comment..." onkeypress="handleCommentKeyPress(event, ${post.id})">
                                <button onclick="addComment(${post.id})">
                                    <i class='bx bx-send'></i>
                                </button>
                            </div>
                            <div class="comments-list" id="comments-list-${post.id}">
                                <!-- Comments will be loaded here -->
                            </div>
                        </div>
                    </div>
                `;
                postsContainer.innerHTML += postHtml;
            });

            // Check if there's a post_id in the URL and scroll to it
            const urlParams = new URLSearchParams(window.location.search);
            const postId = urlParams.get('post_id');
            if (postId) {
                const postElement = document.getElementById(`post-${postId}`);
                if (postElement) {
                    postElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    postElement.style.backgroundColor = '#f0f0f0';
                    setTimeout(() => {
                        postElement.style.backgroundColor = 'white';
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error loading posts:', error);
            showAlert('An error occurred while loading posts.', 'error');
        });
    }

 // Function to like/unlike a post
function toggleLike(postId) {
    const likeButton = document.querySelector(`.reaction[onclick="toggleLike(${postId})"]`);

    fetch('like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Toggle liked state
            likeButton.classList.toggle('liked');

            // Reload posts to update like count
            loadPosts();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while processing your request.', 'error');
    });
}

function addComment(postId) {
    const content = document.getElementById(`comment-input-${postId}`).value.trim();

    if (!content) {
        showAlert('Comment cannot be empty.', 'error');
        return;
    }

    fetch('add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            post_id: postId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            loadComments(postId); // Reload comments after adding a new one
            document.getElementById(`comment-input-${postId}`).value = ''; // Clear the input field
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while processing your request.', 'error');
    });
}
// Function to toggle comments visibility
function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        loadComments(postId); // Load comments when the section is shown
    } else {
        commentsSection.style.display = 'none';
    }
}

function loadComments(postId) {
    fetch(`add_comment.php?post_id=${postId}`)
    .then(response => {
        if (!response.ok) {
            throw new Error(`Server responded with status ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const commentsList = document.getElementById(`comments-list-${postId}`);
        commentsList.innerHTML = ''; // Clear existing comments

        if (data.length === 0) {
            commentsList.innerHTML = '<div class="comment">No comments yet.</div>';
        } else {
            data.forEach(comment => {
                // Add the new HTML structure for each comment
                const commentHtml = `
                    <div class="comment">
                        <img src="${comment.profile_pic}" alt="Profile Picture" class="comment-profile-pic">
                        <div class="comment-content">
                            <strong>${comment.username}</strong>
                            <p>${comment.content}</p>
                        </div>
                    </div>
                `;
                commentsList.innerHTML += commentHtml;
            });
        }
    })
    .catch(error => {
        console.error('Error loading comments:', error);
        showAlert('An error occurred while loading comments: ' + error.message, 'error');
    });
}
    // Load posts when the page loads
    document.addEventListener('DOMContentLoaded', loadPosts);

    // Function to fetch weather data
function fetchWeather(latitude, longitude) {
    const apiKey = '6b1952abec10b0d047487e7b84568617'; // Replace with your API key
    const apiUrl = `https://api.openweathermap.org/data/2.5/weather?lat=${latitude}&lon=${longitude}&appid=${apiKey}&units=metric`;

    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            const weatherInfo = document.getElementById('weather-info');
            const weatherIcon = data.weather[0].icon; // Weather icon code
            const temperature = data.main.temp; // Temperature in Celsius
            const humidity = data.main.humidity; // Humidity percentage
            const windSpeed = data.wind.speed; // Wind speed in m/s
            const weatherDescription = data.weather[0].description; // Weather description

            // Determine if the weather is good or bad for farming
            let weatherStatus = '';
            let statusClass = 'weather-status';
            if (weatherDescription.includes('rain') || weatherDescription.includes('storm')) {
                weatherStatus = 'Bad for farming 🌧️';
                statusClass += ' bad-weather';
            } else if (temperature > 30 || temperature < 10) {
                weatherStatus = 'Bad for farming 🌡️';
                statusClass += ' bad-weather';
            } else {
                weatherStatus = 'Good for farming 🌞';
            }

            // Update the weather widget
            weatherInfo.innerHTML = `
                <img src="https://openweathermap.org/img/wn/${weatherIcon}@2x.png" alt="Weather Icon" class="weather-icon">
                <p><strong>Temperature:</strong> ${temperature}°C</p>
                <p><strong>Humidity:</strong> ${humidity}%</p>
                <p><strong>Wind Speed:</strong> ${windSpeed} m/s</p>
                <p><strong>Condition:</strong> ${weatherDescription}</p>
                <p class="${statusClass}">${weatherStatus}</p>
            `;
        })
        .catch(error => {
            console.error('Error fetching weather data:', error);
            showAlert('Failed to load weather data.', 'error');
        });
}

// Function to get the user's location
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                fetchWeather(latitude, longitude); // Fetch weather data
            },
            error => {
                console.error('Error getting location:', error);
                showAlert('Unable to fetch location.', 'error');
            }
        );
    } else {
        showAlert('Geolocation is not supported by your browser.', 'error');
    }
}

// Load weather data when the page loads
getLocation();

 // Function to generate the mini calendar
 function generateMiniCalendar() {
            const calendarElement = document.getElementById('calendar');
            const seasonElement = document.getElementById('season');
            const today = new Date();
            const month = today.getMonth();
            const year = today.getFullYear();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstDayOfMonth = new Date(year, month, 1).getDay();

            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const seasonNames = ["Winter", "Spring", "Summer", "Autumn"];

            // Determine the season based on the month
            let season;
            if (month >= 11 || month < 2) {
                season = seasonNames[0]; // Winter
            } else if (month >= 2 && month < 5) {
                season = seasonNames[1]; // Spring
            } else if (month >= 5 && month < 8) {
                season = seasonNames[2]; // Summer
            } else {
                season = seasonNames[3]; // Autumn
            }

            // Display the season
            seasonElement.textContent = season;

            // Generate the calendar grid
            let calendarHTML = `<div class="calendar-grid">`;

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDayOfMonth; i++) {
                calendarHTML += `<div class="calendar-day"></div>`;
            }

            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = day === today.getDate() && month === today.getMonth();
                calendarHTML += `<div class="calendar-day ${isToday ? 'today' : ''}">${day}</div>`;
            }

            calendarHTML += `</div>`;
            calendarElement.innerHTML = calendarHTML;
        }

        // Generate the mini calendar when the page loads
        generateMiniCalendar();

    // Function to load notifications
    function loadNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.querySelector('.notification-list');
                const badge = document.querySelector('.notification-badge');
                
                // Update badge count
                badge.textContent = data.unread_count || '0';
                badge.style.display = data.unread_count > 0 ? 'block' : 'none';
                
                // Update notification list
                if (data.notifications && data.notifications.length > 0) {
                    notificationList.innerHTML = data.notifications.map(notification => `
                        <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
                             onclick="handleNotificationClick(${JSON.stringify(notification)})">
                            <img src="${notification.sender_picture || 'Images/profile.jpg'}" 
                                 alt="Profile" 
                                 class="notification-avatar">
                            <div class="notification-content">
                                <div class="notification-text">
                                    <strong>${notification.sender_username}</strong> 
                                    ${notification.type === 'like' ? 'liked your post' : 
                                      notification.type === 'comment' ? 'commented on your post' : 
                                      'sent you a message'}
                                </div>
                                <div class="notification-time">
                                    ${formatTimeAgo(notification.created_at)}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    notificationList.innerHTML = '<div class="notification-item">No notifications</div>';
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                const notificationList = document.querySelector('.notification-list');
                notificationList.innerHTML = '<div class="notification-item">Error loading notifications</div>';
            });
    }

    // Function to handle notification click
    function handleNotificationClick(notification) {
        // Mark as read
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notification.id })
        });

        // Navigate to appropriate page
        if (notification.type === 'message') {
            window.location.href = `message.php?user=${notification.sender_id}`;
        } else if (notification.type === 'like' || notification.type === 'comment') {
            window.location.href = `view_post.php?id=${notification.post_id}`;
        }
    }

    // Function to format time ago
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        return `${Math.floor(seconds / 86400)}d ago`;
    }

    // Load notifications when page loads
    document.addEventListener('DOMContentLoaded', loadNotifications);

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);

    // Function to update notification count
    function updateNotificationCount() {
        fetch('get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update notification count when page loads
    document.addEventListener('DOMContentLoaded', updateNotificationCount);

    // Update notification count every 30 seconds
    setInterval(updateNotificationCount, 30000);

    // Add showAlert function
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
</script>
</body>
</html>