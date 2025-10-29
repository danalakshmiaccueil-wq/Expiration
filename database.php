<?php
function getConnection() {
    $host = 'localhost';
    $dbname = 'sc3bera6697_danalakshmi_expiration';
    $username = 'sc3bera6697_danalakshmi_expiration';
    $password = '0617443516Et?';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>