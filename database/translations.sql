-- Create translations table
CREATE TABLE IF NOT EXISTS translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(2) NOT NULL,
    translation_key VARCHAR(50) NOT NULL,
    translation_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (language_code, translation_key)
);

-- Insert English translations
INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('en', 'welcome', 'Welcome back!'),
('en', 'username_placeholder', 'Username or Email'),
('en', 'password_placeholder', 'Password'),
('en', 'login', 'Login'),
('en', 'no_account', 'Don\'t have an account?'),
('en', 'register', 'Register'),
('en', 'ok', 'OK'),
('en', 'login_success', 'Login successful!'),
('en', 'login_error', 'Invalid username or password'),
('en', 'server_error', 'Server error occurred'),
('en', 'create_account', 'Create Account'),
('en', 'email_placeholder', 'Email'),
('en', 'confirm_password_placeholder', 'Confirm password'),
('en', 'account_type_label', 'Account Type:'),
('en', 'farmer_label', 'Farmer'),
('en', 'consumer_label', 'Consumer'),
('en', 'gender_label', 'Gender (Optional):'),
('en', 'male_label', 'Male'),
('en', 'female_label', 'Female'),
('en', 'have_account', 'Already have an account?'),
('en', 'phone_placeholder', '6XXXXXXXX or 7XXXXXXXX');

-- Insert French translations
INSERT INTO translations (language_code, translation_key, translation_value) VALUES
('fr', 'welcome', 'Bienvenue!'),
('fr', 'username_placeholder', 'Nom d\'utilisateur ou Email'),
('fr', 'password_placeholder', 'Mot de passe'),
('fr', 'login', 'Connexion'),
('fr', 'no_account', 'Pas de compte?'),
('fr', 'register', 'S\'inscrire'),
('fr', 'ok', 'OK'),
('fr', 'login_success', 'Connexion réussie!'),
('fr', 'login_error', 'Nom d\'utilisateur ou mot de passe invalide'),
('fr', 'server_error', 'Une erreur de serveur s\'est produite'),
('fr', 'create_account', 'Créer un compte'),
('fr', 'email_placeholder', 'Email'),
('fr', 'confirm_password_placeholder', 'Confirmer le mot de passe'),
('fr', 'account_type_label', 'Type de compte:'),
('fr', 'farmer_label', 'Agriculteur'),
('fr', 'consumer_label', 'Consommateur'),
('fr', 'gender_label', 'Genre (Optionnel):'),
('fr', 'male_label', 'Homme'),
('fr', 'female_label', 'Femme'),
('fr', 'have_account', 'Vous avez déjà un compte?'),
('fr', 'phone_placeholder', '6XXXXXXXX ou 7XXXXXXXX'); 