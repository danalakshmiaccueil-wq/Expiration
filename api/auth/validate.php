<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'auth_utils.php';

try {
    $token = getBearerToken();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'message' => 'Token manquant'
        ]);
        exit();
    }
    
    $payload = verifyToken($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'message' => 'Token invalide ou expiré'
        ]);
        exit();
    }
    
    http_response_code(200);
    echo json_encode([
        'valid' => true,
        'user' => [
            'id' => $payload['user_id'],
            'username' => $payload['username'],
            'role' => $payload['role']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Erreur validation: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'message' => 'Erreur de validation'
    ]);
}
