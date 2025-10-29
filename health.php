<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Inclure notre configuration de base de données qui fonctionne
    require_once 'config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test simple de connexion
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => date('c'),
        'message' => 'API fonctionnelle'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'database' => 'disconnected',
        'timestamp' => date('c'),
        'message' => $e->getMessage()
    ]);
}
?>
