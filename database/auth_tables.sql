-- Script SQL pour créer les tables d'authentification
-- Base de données: sc3bera6697_danalakshmi_expiration

-- Table utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    role ENUM('admin', 'gestionnaire', 'utilisateur') DEFAULT 'utilisateur',
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table sessions
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer un utilisateur admin par défaut
-- Mot de passe: Admin2024! (à changer immédiatement en production)
INSERT INTO utilisateurs (username, password, nom, prenom, email, role, actif) 
VALUES (
    'admin',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5oo2IQ7z8HqH6',
    'Administrateur',
    'Système',
    'admin@danalakshmi.fr',
    'admin',
    1
) ON DUPLICATE KEY UPDATE username = username;

-- Créer un utilisateur gestionnaire de test
-- Mot de passe: Gestionnaire2024!
INSERT INTO utilisateurs (username, password, nom, prenom, email, role, actif) 
VALUES (
    'gestionnaire',
    '$2y$12$K7v.MqE1/h4XYz9LK0f9wOxzEP3Z2t5B6N8C4J6H5L3M2Q8R7T9P1',
    'Gestionnaire',
    'Test',
    'gestionnaire@danalakshmi.fr',
    'gestionnaire',
    1
) ON DUPLICATE KEY UPDATE username = username;

-- Créer un utilisateur standard de test
-- Mot de passe: User2024!
INSERT INTO utilisateurs (username, password, nom, prenom, email, role, actif) 
VALUES (
    'user',
    '$2y$12$N9p.QrF2/i5YZz0MK1g0xOyzFP4A3u6C7O9D5K7I6M4N3R9S8U0Q2',
    'Utilisateur',
    'Test',
    'user@danalakshmi.fr',
    'utilisateur',
    1
) ON DUPLICATE KEY UPDATE username = username;

-- Nettoyer les sessions expirées (à exécuter régulièrement)
DELETE FROM sessions WHERE expires_at < NOW();

-- Vue pour les utilisateurs actifs
CREATE OR REPLACE VIEW v_utilisateurs_actifs AS
SELECT 
    id,
    username,
    nom,
    prenom,
    email,
    role,
    derniere_connexion,
    date_creation
FROM utilisateurs
WHERE actif = 1;

-- Procédure stockée pour nettoyer les sessions expirées
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS clean_expired_sessions()
BEGIN
    DELETE FROM sessions WHERE expires_at < NOW();
    SELECT ROW_COUNT() as sessions_supprimees;
END //
DELIMITER ;

-- Trigger pour mettre à jour la date de modification
DELIMITER //
CREATE TRIGGER IF NOT EXISTS before_update_utilisateur
BEFORE UPDATE ON utilisateurs
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END //
DELIMITER ;
