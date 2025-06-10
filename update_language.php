<?php
session_start();
require_once 'includes/translations.php';
require_once 'database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $lang = $_POST['language'];
    if (in_array($lang, ['en', 'fr'])) {
        $_SESSION['language'] = $lang;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?> 