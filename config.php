<?php

// Function to parse the .env file
function load_env($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load the .env file from the project root
load_env(__DIR__ . '/.env');

// Configuration de la base de données
$servername = $_ENV['DB_SERVERNAME'] ?? 'localhost';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'farmx';

// Configuration de l'API Hugging Face pour FarmBot (fallback)
define('HUGGING_FACE_API_KEY', $_ENV['HUGGING_FACE_API_KEY'] ?? '');
define('HUGGING_FACE_API_URL', 'https://api-inference.huggingface.co/models/meta-llama/Llama-2-7b-chat-hf');

// Configuration de l'API OpenAI pour FarmBot (principal)
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

// Configuration du chatbot FarmBot
define('FARMBOT_MAX_TOKENS', 300);
define('FARMBOT_TEMPERATURE', 0.7);
define('FARMBOT_TOP_P', 0.9);

// Connexion à la base de données
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?> 