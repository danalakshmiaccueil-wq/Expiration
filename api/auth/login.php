<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';
require_once 'auth_utils.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Récupérer les données JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Données invalides');
    }
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $remember = $data['remember'] ?? false;
    
    // Validation
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Nom d\'utilisateur et mot de passe requis'
        ]);
        exit();
    }
    
    // Connexion à la base de données
    $db = getDBConnection();
    
    // Requête pour récupérer l'utilisateur
    $stmt = $db->prepare("
        SELECT id, username, password, nom, prenom, email, role, actif 
        FROM utilisateurs 
        WHERE username = ? AND actif = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur existe
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Identifiants incorrects'
        ]);
        exit();
    }
    
    // Vérifier le mot de passe
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Identifiants incorrects'
        ]);
        exit();
    }
    
    // Générer un token JWT
    $token = generateToken($user['id'], $user['username'], $user['role'], $remember);
    
    // Mettre à jour la dernière connexion
    $updateStmt = $db->prepare("
        UPDATE utilisateurs 
        SET derniere_connexion = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$user['id']]);
    
    // Enregistrer la session
    $sessionStmt = $db->prepare("
        INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $expiresAt = $remember ? 
        date('Y-m-d H:i:s', strtotime('+30 days')) : 
        date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sessionStmt->execute([
        $user['id'],
        $token,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $expiresAt
    ]);
    
    // Retourner la réponse de succès
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur base de données login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
} catch (Exception $e) {
    error_log('Erreur login: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
