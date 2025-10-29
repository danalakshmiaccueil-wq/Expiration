<?php
/**
 * Configuration générale de l'application
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détection de l'environnement
define('ENVIRONMENT', ($_SERVER['HTTP_HOST'] === 'localhost' || 
                      strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                      strpos($_SERVER['HTTP_HOST'], '.local') !== false) ? 'development' : 'production');

// Configuration des erreurs selon l'environnement
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Configuration pour hébergement cPanel
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 30);
ini_set('max_input_time', 60);

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration de l'application
define('APP_NAME', 'Expiration Manager');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Danalakshmi');

// Chemins de l'application
define('ROOT_PATH', dirname(__DIR__));
define('API_PATH', ROOT_PATH . '/api');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// URLs de base (à adapter selon votre domaine)
define('BASE_URL', ENVIRONMENT === 'development' 
    ? 'http://localhost/expiration' 
    : 'https://votre-domaine.com');
define('API_URL', BASE_URL . '/api');

// Configuration du cache
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes
define('CACHE_PATH', ROOT_PATH . '/cache');

// Configuration des alertes
define('DEFAULT_ALERT_COLORS', [
    'expire' => '#8B0000',  // Rouge foncé pour expirés
    'urgent' => '#FF0000',  // Rouge pour 1 jour
    'important' => '#FF8C00', // Orange pour 7 jours
    'moyen' => '#FFD700',   // Jaune pour 30 jours
    'faible' => '#90EE90'   // Vert clair pour 60 jours
]);

// Configuration de sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 heure

// Headers CORS pour l'API
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://127.0.0.1',
    'https://votre-domaine.com',
    'https://www.votre-domaine.com'
]);

// Configuration de pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Configuration des logs
define('LOG_LEVEL', ENVIRONMENT === 'development' ? 'DEBUG' : 'ERROR');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10 MB

// Configuration des uploads (si nécessaire)
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

/**
 * Fonction d'autoload simple pour les classes
 */
function autoloadClasses($className) {
    $paths = [
        API_PATH . '/classes/' . $className . '.php',
        API_PATH . '/utils/' . $className . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Enregistrer l'autoloader
spl_autoload_register('autoloadClasses');

/**
 * Fonction pour créer les dossiers nécessaires
 */
function createRequiredDirectories() {
    $directories = [LOGS_PATH, UPLOADS_PATH, CACHE_PATH];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Créer les dossiers au premier chargement
createRequiredDirectories();

/**
 * Fonction pour obtenir la configuration d'un paramètre
 */
function getConfig($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        // Charger la configuration depuis la base de données si disponible
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT nom_parametre, valeur FROM parametres WHERE actif = 1");
            $params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $config = $params;
        } catch (Exception $e) {
            $config = [];
        }
    }
    
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Fonction pour définir les headers CORS
 */
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, ALLOWED_ORIGINS) || ENVIRONMENT === 'development') {
        header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json; charset=UTF-8");
    
    // Répondre aux requêtes OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * Fonction de réponse JSON standardisée
 */
function jsonResponse($data, $status = 200, $message = '') {
    http_response_code($status);
    
    $response = [
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'timestamp' => date('c'),
        'data' => $data
    ];
    
    if (!empty($message)) {
        $response['message'] = $message;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Fonction pour logger les erreurs
 */
function logError($message, $context = []) {
    $logFile = LOGS_PATH . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logEntry = "[$timestamp] ERROR: $message $contextStr" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Gestionnaire d'erreurs global
set_error_handler(function($severity, $message, $file, $line) {
    logError("PHP Error: $message in $file:$line", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
});

// Gestionnaire d'exceptions global
set_exception_handler(function($exception) {
    logError("Uncaught Exception: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    if (ENVIRONMENT === 'production') {
        jsonResponse(null, 500, 'Erreur interne du serveur');
    } else {
        jsonResponse([
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ], 500, 'Erreur de développement');
    }
});
?>