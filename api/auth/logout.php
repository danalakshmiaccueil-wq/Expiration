<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';
require_once 'auth_utils.php';

try {
    $token = getBearerToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token manquant'
        ]);
        exit();
    }
    
    $payload = verifyToken($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token invalide'
        ]);
        exit();
    }
    
    // Supprimer la session de la base de données
    $db = getDBConnection();
    $stmt = $db->prepare("DELETE FROM sessions WHERE token = ? OR user_id = ?");
    $stmt->execute([$token, $payload['user_id']]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Déconnexion réussie'
    ]);
    
} catch (Exception $e) {
    error_log('Erreur logout: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la déconnexion'
    ]);
}
