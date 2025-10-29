<?php
/**
 * Page d'accueil de l'API
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/helpers.php';

// Headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

try {
    // Test de santé de l'API
    $health = testApiHealth();
    
    // Informations sur l'API
    $apiInfo = [
        'name' => APP_NAME,
        'version' => APP_VERSION,
        'description' => 'API REST pour la gestion des dates d\'expiration des produits alimentaires',
        'environment' => ENVIRONMENT,
        'timestamp' => date('c'),
        'health' => $health,
        'endpoints' => [
            'produits' => [
                'url' => '/api/produits',
                'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
                'description' => 'Gestion des produits'
            ],
            'lots' => [
                'url' => '/api/lots',
                'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
                'description' => 'Gestion des lots de produits'
            ],
            'dashboard' => [
                'url' => '/api/dashboard',
                'methods' => ['GET'],
                'description' => 'Statistiques et métriques'
            ],
            'parametres' => [
                'url' => '/api/parametres',
                'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
                'description' => 'Configuration du système'
            ],
            'alertes' => [
                'url' => '/api/alertes',
                'methods' => ['GET', 'POST'],
                'description' => 'Système d\'alertes d\'expiration'
            ]
        ],
        'documentation' => [
            'test_interface' => '/api/test',
            'health_check' => '/api/health',
            'github' => 'https://github.com/votre-repo/expiration-management'
        ]
    ];

    // Si c'est un health check simple
    if (isset($_GET['health'])) {
        jsonResponse($health);
        exit;
    }

    // Réponse complète de l'API
    jsonResponse($apiInfo);

} catch (Exception $e) {
    logError("Erreur API index: " . $e->getMessage());
    jsonResponse([
        'error' => 'Service temporairement indisponible',
        'timestamp' => date('c')
    ], 503);
}
?>