<?php
/**
 * Configuration de la base de données - FINALE
 */

define("DB_HOST", "localhost");
define("DB_NAME", "sc3bera6697_danalakshmi_expiration");
define("DB_USER", "sc3bera6697_danalakshmi_user");
define("DB_PASS", "0617443516Et?");
define("DB_CHARSET", "utf8mb4");

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $pdo;
    
    public function getConnection() {
        if ($this->pdo == null) {
            try {
                $this->pdo = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . DB_CHARSET,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch(PDOException $e) {
                error_log("Erreur connexion DB: " . $e->getMessage());
                throw $e;
            }
        }
        return $this->pdo;
    }
}
?>