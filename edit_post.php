<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "farmx";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['post_id'];
    $content = $_POST['content'];
    
    $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $content, $postId, $_SESSION['user_id']);
    $stmt->execute();
    
    header("Location: profile.php");
    exit();
}

// Get the post to edit
$postId = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $postId, $_SESSION['user_id']);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
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

        /* Your existing edit post styles */
        .edit-post-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .edit-post-container h1 {
            color: #3e8e41;
            margin-bottom: 20px;
        }
        
        .edit-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 150px;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .edit-form button {
            background-color: #3e8e41;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .edit-form button:hover {
            background-color: #2d682f;
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
    
    <div class="edit-post-container">
        <h1>Edit Post</h1>
        <form class="edit-form" method="POST">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <textarea name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            <button type="submit">Update Post</button>
            <a href="profile.php" style="margin-left: 10px;">Cancel</a>
        </form>
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