<?php
/**
 * Connexion à la base de données pour l'authentification
 */

function getDBConnection() {
    $host = 'localhost';
    $dbname = 'sc3bera6697_danalakshmi_expiration';
    $username = 'sc3bera6697_danalakshmi_user';
    $password = '0617443516Et?';
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        error_log('Erreur de connexion DB: ' . $e->getMessage());
        throw new Exception('Impossible de se connecter à la base de données');
    }
}
