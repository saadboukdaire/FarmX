<?php
class Translations {
    private $db;
    private $translations = [];
    private $currentLang;

    public function __construct($db) {
        $this->db = $db;
        $this->currentLang = $_SESSION['language'] ?? 'en';
        $this->loadTranslations();
    }

    private function loadTranslations() {
        try {
            $stmt = $this->db->prepare("SELECT translation_key, translation_value FROM translations WHERE language_code = ?");
            $stmt->execute([$this->currentLang]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->translations[$row['translation_key']] = $row['translation_value'];
            }
        } catch (PDOException $e) {
            // Log error and use default translations
            error_log("Translation loading error: " . $e->getMessage());
            $this->loadDefaultTranslations();
        }
    }

    private function loadDefaultTranslations() {
        // Default English translations as fallback
        $this->translations = [
            'welcome' => 'Welcome back!',
            'username_placeholder' => 'Username or Email',
            'password_placeholder' => 'Password',
            'login' => 'Login',
            'no_account' => 'Don\'t have an account?',
            'register' => 'Register',
            'ok' => 'OK',
            'login_success' => 'Login successful!',
            'login_error' => 'Invalid username or password',
            'server_error' => 'Server error occurred'
        ];
    }

    public function get($key) {
        return $this->translations[$key] ?? $key;
    }

    public function setLanguage($lang) {
        if (in_array($lang, ['en', 'fr'])) {
            $this->currentLang = $lang;
            $_SESSION['language'] = $lang;
            $this->loadTranslations();
        }
    }

    public function getCurrentLanguage() {
        return $this->currentLang;
    }

    public function getAllTranslations($lang) {
        try {
            $stmt = $this->db->prepare("SELECT translation_key, translation_value FROM translations WHERE language_code = ?");
            $stmt->execute([$lang]);
            
            $translations = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $translations[$row['translation_key']] = $row['translation_value'];
            }
            return $translations;
        } catch (PDOException $e) {
            error_log("Error getting all translations: " . $e->getMessage());
            return $this->loadDefaultTranslations();
        }
    }
}
?> 