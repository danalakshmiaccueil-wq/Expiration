<?php
/**
 * Utilitaires et fonctions helper pour l'API
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Classe d'utilitaires pour l'API
 */
class ApiUtils {
    
    /**
     * Valider une date au format YYYY-MM-DD
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Nettoyer et valider un email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Générer un token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Vérifier un token CSRF
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Nettoyer les données d'entrée
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }

    /**
     * Paginer des résultats
     */
    public static function paginate($query, $params, $page, $limit, $countQuery = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Requête de comptage
            if (!$countQuery) {
                $countQuery = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $query);
                $countQuery = preg_replace('/ORDER BY .*/', '', $countQuery);
                $countQuery = preg_replace('/LIMIT .*/', '', $countQuery);
            }
            
            $countStmt = $db->prepare($countQuery);
            foreach ($params as $key => $value) {
                if (strpos($countQuery, $key) !== false) {
                    $countStmt->bindValue($key, $value);
                }
            }
            $countStmt->execute();
            $total = $countStmt->fetch()['total'];

            // Requête principale avec pagination
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $data = $stmt->fetchAll();
            
            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit),
                    'has_next' => $page < ceil($total / $limit),
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            throw new Exception("Erreur de pagination: " . $e->getMessage());
        }
    }

    /**
     * Valider les permissions utilisateur (pour future authentification)
     */
    public static function checkPermission($action, $resource = null) {
        // Pour l'instant, tout est autorisé
        // À implémenter selon les besoins de sécurité
        return true;
    }

    /**
     * Logger une action utilisateur
     */
    public static function logUserAction($action, $resource, $data = null) {
        $logEntry = [
            'timestamp' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'action' => $action,
            'resource' => $resource,
            'data' => $data
        ];
        
        $logFile = LOGS_PATH . '/user_actions_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Formater les dates pour l'affichage
     */
    public static function formatDate($date, $format = 'd/m/Y') {
        if (empty($date)) return null;
        
        try {
            $dateObj = new DateTime($date);
            return $dateObj->format($format);
        } catch (Exception $e) {
            return $date; // Retourner la date originale en cas d'erreur
        }
    }

    /**
     * Calculer les jours entre deux dates
     */
    public static function daysBetween($date1, $date2) {
        try {
            $d1 = new DateTime($date1);
            $d2 = new DateTime($date2);
            return $d1->diff($d2)->days;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir la couleur d'alerte selon les jours restants
     */
    public static function getAlertColor($joursRestants) {
        $colors = DEFAULT_ALERT_COLORS;
        
        if ($joursRestants < 0) return $colors['expire'];
        if ($joursRestants <= 1) return $colors['urgent'];
        if ($joursRestants <= 7) return $colors['important'];
        if ($joursRestants <= 30) return $colors['moyen'];
        if ($joursRestants <= 60) return $colors['faible'];
        
        return '#FFFFFF'; // Pas d'alerte
    }

    /**
     * Valider les données d'un produit
     */
    public static function validateProduitData($data, $isCreate = true) {
        $errors = [];

        if ($isCreate && empty($data['nom'])) {
            $errors[] = "Le nom du produit est obligatoire";
        }

        if (isset($data['nom']) && strlen(trim($data['nom'])) < 2) {
            $errors[] = "Le nom du produit doit contenir au moins 2 caractères";
        }

        if ($isCreate && empty($data['categorie'])) {
            $errors[] = "La catégorie est obligatoire";
        }

        if (isset($data['unite_mesure'])) {
            $unitesValides = ['kg', 'g', 'L', 'mL', 'pièce'];
            if (!in_array($data['unite_mesure'], $unitesValides)) {
                $errors[] = "Unité de mesure non valide";
            }
        }

        return $errors;
    }

    /**
     * Valider les données d'un lot
     */
    public static function validateLotData($data, $isCreate = true) {
        $errors = [];

        if ($isCreate && empty($data['produit_id'])) {
            $errors[] = "L'ID du produit est obligatoire";
        }

        if ($isCreate && empty($data['date_expiration'])) {
            $errors[] = "La date d'expiration est obligatoire";
        }

        if ($isCreate && empty($data['date_reception'])) {
            $errors[] = "La date de réception est obligatoire";
        }

        if (isset($data['date_expiration'], $data['date_reception'])) {
            if (!self::validateDate($data['date_expiration']) || !self::validateDate($data['date_reception'])) {
                $errors[] = "Format de date invalide (attendu: YYYY-MM-DD)";
            } elseif ($data['date_expiration'] < $data['date_reception']) {
                $errors[] = "La date d'expiration ne peut pas être antérieure à la date de réception";
            }
        }

        if (isset($data['quantite_initiale']) && $data['quantite_initiale'] <= 0) {
            $errors[] = "La quantité initiale doit être positive";
        }

        if (isset($data['quantite_actuelle']) && $data['quantite_actuelle'] < 0) {
            $errors[] = "La quantité actuelle ne peut pas être négative";
        }

        if (isset($data['prix_achat']) && !empty($data['prix_achat']) && $data['prix_achat'] < 0) {
            $errors[] = "Le prix d'achat ne peut pas être négatif";
        }

        return $errors;
    }

    /**
     * Convertir les erreurs en réponse JSON
     */
    public static function errorsToJson($errors, $status = 400) {
        if (!empty($errors)) {
            jsonResponse([
                'errors' => $errors,
                'error_count' => count($errors)
            ], $status, 'Erreurs de validation');
        }
    }

    /**
     * Cache simple pour les requêtes fréquentes
     */
    public static function getFromCache($key) {
        if (!CACHE_ENABLED) return null;
        
        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_DURATION) {
            return unserialize(file_get_contents($cacheFile));
        }
        
        return null;
    }

    /**
     * Sauvegarder en cache
     */
    public static function saveToCache($key, $data) {
        if (!CACHE_ENABLED) return false;
        
        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';
        return file_put_contents($cacheFile, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Vider le cache
     */
    public static function clearCache($pattern = '*') {
        if (!CACHE_ENABLED) return false;
        
        $files = glob(CACHE_PATH . '/' . $pattern . '.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
}

/**
 * Fonction de debug pour l'environnement de développement
 */
function debugLog($data, $label = 'DEBUG') {
    if (ENVIRONMENT === 'development') {
        $debugFile = LOGS_PATH . '/debug_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $content = "[$timestamp] $label: " . (is_array($data) || is_object($data) ? json_encode($data) : $data) . PHP_EOL;
        file_put_contents($debugFile, $content, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Fonction pour tester la connectivité de l'API
 */
function testApiHealth() {
    $tests = [
        'database' => false,
        'config' => false,
        'logs' => false,
        'cache' => false
    ];

    // Test de la base de données
    try {
        $db = Database::getInstance();
        if ($db->testConnection()) {
            $tests['database'] = true;
        }
    } catch (Exception $e) {
        // Database test failed
    }

    // Test de la configuration
    $tests['config'] = defined('APP_NAME') && defined('DB_HOST');

    // Test des logs
    $tests['logs'] = is_writable(LOGS_PATH);

    // Test du cache
    $tests['cache'] = is_writable(CACHE_PATH);

    $allPassed = array_reduce($tests, function($carry, $test) {
        return $carry && $test;
    }, true);

    return [
        'status' => $allPassed ? 'healthy' : 'warning',
        'tests' => $tests,
        'timestamp' => date('c'),
        'version' => APP_VERSION
    ];
}
?>