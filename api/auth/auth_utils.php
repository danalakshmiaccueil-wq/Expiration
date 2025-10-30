<?php
/**
 * Fonctions utilitaires pour l'authentification
 */

// Clé secrète pour JWT (à changer en production)
define('JWT_SECRET', 'Danalakshmi_2024_Secret_Key_Change_In_Production');

/**
 * Génère un token JWT
 */
function generateToken($userId, $username, $role, $remember = false) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    $expiration = $remember ? 
        time() + (30 * 24 * 60 * 60) : // 30 jours
        time() + (24 * 60 * 60);        // 24 heures
    
    $payload = json_encode([
        'user_id' => $userId,
        'username' => $username,
        'role' => $role,
        'iat' => time(),
        'exp' => $expiration
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    return $jwt;
}

/**
 * Vérifie et décode un token JWT
 */
function verifyToken($token) {
    try {
        $tokenParts = explode('.', $token);
        
        if (count($tokenParts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $tokenParts;
        
        // Vérifier la signature
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], 
            base64_encode(hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true)));
        
        if ($signature !== $base64UrlSignature) {
            return false;
        }
        
        // Décoder le payload
        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        // Vérifier l'expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }
        
        return $payloadData;
        
    } catch (Exception $e) {
        error_log('Erreur vérification token: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtient le token depuis les headers
 */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    
    if (!empty($headers)) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * Obtient le header Authorization
 */
function getAuthorizationHeader() {
    $headers = null;
    
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)), 
            array_values($requestHeaders)
        );
        
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    return $headers;
}

/**
 * Vérifie si l'utilisateur est authentifié
 */
function requireAuth() {
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
            'message' => 'Token invalide ou expiré'
        ]);
        exit();
    }
    
    return $payload;
}

/**
 * Hache un mot de passe
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
