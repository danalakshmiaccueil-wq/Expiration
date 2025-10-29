<?php
/**
 * Configuration de la base de données
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

// Configuration pour cPanel (configuré pour Danalakshmi)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sc3bera6697_danalakshmi_expiration');
define('DB_USER', 'sc3bera6697_danalakshmi_user');
define('DB_PASS', 'WZS8ELBcccS9');
define('DB_CHARSET', 'utf8mb4');

/**
 * Classe de connexion à la base de données
 * Optimisée pour les hébergements cPanel
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $pdo;
    private static $instance = null;

    /**
     * Singleton pattern pour éviter les connexions multiples
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->connect();
    }

    /**
     * Établit la connexion à la base de données
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // Éviter les connexions persistantes sur hébergement partagé
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            // Configuration spécifique pour MySQL sur cPanel
            $this->pdo->exec("SET time_zone = '+00:00'");
            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            
        } catch(PDOException $exception) {
            error_log("Erreur de connexion BDD: " . $exception->getMessage());
            
            // En production, ne pas révéler les détails de l'erreur
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                die(json_encode([
                    'success' => false,
                    'message' => 'Erreur de connexion à la base de données'
                ]));
            } else {
                die(json_encode([
                    'success' => false,
                    'message' => 'Erreur de connexion: ' . $exception->getMessage()
                ]));
            }
        }
    }

    /**
     * Retourne la connexion PDO
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Test de connexion
     */
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return $stmt !== false;
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Fermeture de la connexion
     */
    public function close() {
        $this->pdo = null;
    }

    /**
     * Empêcher le clonage
     */
    private function __clone() {}

    /**
     * Empêcher la désérialisation
     */
    public function __wakeup() {
        throw new Exception("Impossible de désérialiser un singleton.");
    }
}

/**
 * Test rapide de la configuration
 * Décommentez pour tester la connexion
 */
/*
try {
    $db = Database::getInstance();
    if ($db->testConnection()) {
        echo json_encode(['success' => true, 'message' => 'Connexion OK']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Échec du test']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
*/
?>