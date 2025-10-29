<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'api/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Supprimer tous les lots
    $sql = "DELETE FROM lots";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $deletedCount = $stmt->rowCount();
    
    // Réinitialiser l'auto-increment
    $pdo->exec("ALTER TABLE lots AUTO_INCREMENT = 1");
    
    echo json_encode([
        'success' => true,
        'message' => "Tous les lots ont été supprimés ($deletedCount lots effacés)",
        'deleted_count' => $deletedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?>