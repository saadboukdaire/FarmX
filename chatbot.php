<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Utilisateur';
$profile_pic = isset($_SESSION['profile_pic']) && $_SESSION['profile_pic'] ? $_SESSION['profile_pic'] : 'Images/profile.jpg';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriBot - Assistant Agricole | FarmX</title>
    <link rel="icon" href="Images/logo.jpg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #7ec850 0%, #b6a16b 100%); /* Green to earth brown */
            min-height: 100vh;
            padding: 20px;
        }

        .chatbot-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .chatbot-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .chatbot-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .bot-avatar {
            width: 60px;
            height: 60px;
            background: #fff;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #4CAF50;
        }

        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.bot .message-content {
            background: #e3f2fd;
            color: #1565c0;
            border-bottom-left-radius: 5px;
        }

        .message.user .message-content {
            background: #4CAF50;
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .message.bot .message-avatar {
            background: #4CAF50;
            color: white;
        }

        .message.user .message-avatar {
            background: #2196F3;
            color: white;
        }

        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .chat-input-form {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: #4CAF50;
        }

        .send-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .send-button:hover {
            background: #45a049;
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .typing-indicator {
            display: none;
            padding: 12px 16px;
            background: #e3f2fd;
            border-radius: 18px;
            margin-bottom: 15px;
            color: #1565c0;
        }

        .typing-dots {
            display: inline-block;
        }

        .typing-dots::after {
            content: '';
            animation: typing 1.5s infinite;
        }

        @keyframes typing {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }

        .welcome-message {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 20px 0;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .back-button:hover {
            background: rgba(255,255,255,0.3);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .chatbot-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .chat-messages {
                height: 350px;
            }
            
            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="chatbot-container">
        <div class="chatbot-header">
            <a href="main.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <div class="bot-avatar">
                <i class="fas fa-seedling"></i>
            </div>
            <h1>AgriBot</h1>
            <p>Votre assistant agricole intelligent</p>
        </div>

        <div class="welcome-message" id="welcomeMessage">
            <i class="fas fa-robot"></i> Bonjour ! Je suis AgriBot, votre assistant agricole. Posez-moi vos questions sur l'agriculture, les cultures, l'élevage, le sol, l'irrigation ou la météo agricole.
        </div>

        <div class="chat-messages" id="chatMessages">
        </div>

        <div class="typing-indicator" id="typingIndicator">
            <i class="fas fa-robot"></i> AgriBot réfléchit<span class="typing-dots"></span>
        </div>

        <div class="chat-input-container">
            <form class="chat-input-form" id="chatForm">
                <input type="text" class="chat-input" id="messageInput" placeholder="Posez votre question agricole..." autocomplete="off">
                <button type="submit" class="send-button" id="sendButton">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const chatForm = document.getElementById('chatForm');
        const typingIndicator = document.getElementById('typingIndicator');
        const userProfilePic = <?php echo json_encode($profile_pic); ?>;

        function addMessage(content, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
            
            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            if (isUser) {
                avatar.innerHTML = `<img src="${userProfilePic}" alt="Votre photo de profil" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
            } else {
                avatar.innerHTML = '<i class="fas fa-seedling"></i>';
            }
            
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            if (!isUser) {
                messageContent.innerHTML = marked.parse(content);
            } else {
                messageContent.textContent = content;
            }
            if (isUser) {
                messageDiv.appendChild(messageContent);
                messageDiv.appendChild(avatar);
            } else {
                messageDiv.appendChild(avatar);
                messageDiv.appendChild(messageContent);
            }
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showTyping() {
            typingIndicator.style.display = 'block';
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function hideTyping() {
            typingIndicator.style.display = 'none';
        }

        async function sendMessage(message) {
            try {
                showTyping();
                const response = await fetch('http://localhost:5000/chatbot_api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                hideTyping();

                if (data.success) {
                    addMessage(data.response);
                } else {
                    addMessage(data.response || 'Désolé, je ne peux pas répondre pour le moment. Veuillez réessayer.');
                }
            } catch (error) {
                hideTyping();
                addMessage('Erreur de connexion. Veuillez réessayer.');
                console.error('Error:', error);
            }
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;
            // Remove welcome message if present
            const welcome = document.getElementById('welcomeMessage');
            if (welcome) welcome.style.display = 'none';
            addMessage(message, true);
            messageInput.value = '';
            sendButton.disabled = true;
            await sendMessage(message);
            sendButton.disabled = false;
            messageInput.focus();
        });

        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });

        // Focus sur l'input au chargement
        messageInput.focus();
    </script>
</body>
</html> 