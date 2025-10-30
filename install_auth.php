<?php
/**
 * Script d'installation des tables d'authentification
 * À exécuter une seule fois puis à supprimer
 */

header('Content-Type: text/plain; charset=utf-8');

// Configuration
$host = 'localhost';
$dbname = 'sc3bera6697_danalakshmi_expiration';
$username = 'sc3bera6697_danalakshmi_user';
$password = '0617443516Et?';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Table utilisateurs
    echo "📋 Création table 'utilisateurs'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table 'utilisateurs' créée\n\n";
    
    // Table sessions
    echo "📋 Création table 'sessions'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Table 'sessions' créée\n\n";
    
    // Utilisateur admin
    echo "👨‍💼 Création utilisateur 'admin'...\n";
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (username, password, nom, prenom, email, role, actif) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE username = username
    ");
    $stmt->execute([
        'admin',
        password_hash('Admin2024!', PASSWORD_BCRYPT, ['cost' => 12]),
        'Administrateur',
        'Système',
        'admin@danalakshmi.fr',
        'admin',
        1
    ]);
    echo "✅ Utilisateur 'admin' créé (password: Admin2024!)\n\n";
    
    // Utilisateur gestionnaire
    echo "👨‍💻 Création utilisateur 'gestionnaire'...\n";
    $stmt->execute([
        'gestionnaire',
        password_hash('Gestionnaire2024!', PASSWORD_BCRYPT, ['cost' => 12]),
        'Gestionnaire',
        'Test',
        'gestionnaire@danalakshmi.fr',
        'gestionnaire',
        1
    ]);
    echo "✅ Utilisateur 'gestionnaire' créé (password: Gestionnaire2024!)\n\n";
    
    // Utilisateur standard
    echo "👤 Création utilisateur 'user'...\n";
    $stmt->execute([
        'user',
        password_hash('User2024!', PASSWORD_BCRYPT, ['cost' => 12]),
        'Utilisateur',
        'Test',
        'user@danalakshmi.fr',
        'utilisateur',
        1
    ]);
    echo "✅ Utilisateur 'user' créé (password: User2024!)\n\n";
    
    // Vérification
    echo "📊 Vérification des données...\n";
    $stmt = $pdo->query("SELECT username, nom, prenom, role FROM utilisateurs");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nUtilisateurs créés:\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($users as $user) {
        echo sprintf("%-15s | %-20s | %-12s\n", 
            $user['username'], 
            $user['nom'] . ' ' . $user['prenom'],
            $user['role']
        );
    }
    echo str_repeat("-", 60) . "\n";
    
    echo "\n✅ Installation terminée avec succès !\n";
    echo "\n⚠️  IMPORTANT: Supprimez ce fichier (install_auth.php) après utilisation !\n";
    echo "\n🔗 Testez la connexion sur: https://expire.danalakshmi.fr/login.html\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    http_response_code(500);
}
