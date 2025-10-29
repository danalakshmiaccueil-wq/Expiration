<?php
/**
 * API des paramètres système
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/helpers.php';

// Headers CORS et JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Logging de la requête
    ApiUtils::logUserAction($method, 'parametres', $input);

    switch ($method) {
        case 'GET':
            handleGet();
            break;
        case 'POST':
            handlePost($input);
            break;
        case 'PUT':
            handlePut($input);
            break;
        case 'DELETE':
            handleDelete();
            break;
        default:
            jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

} catch (Exception $e) {
    logError("Erreur API parametres: " . $e->getMessage());
    jsonResponse(['error' => 'Erreur interne du serveur'], 500);
}

/**
 * Récupérer les paramètres
 */
function handleGet() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Récupérer tous les paramètres
        $query = "
            SELECT 
                p.id,
                p.nom,
                p.valeur,
                p.type,
                p.description,
                p.date_modification,
                p.modifie_par
            FROM parametres p
            ORDER BY p.nom
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $parametres = $stmt->fetchAll();
        
        // Organiser les paramètres par catégorie
        $categorised = [
            'alertes' => [],
            'couleurs' => [],
            'general' => [],
            'autres' => []
        ];
        
        foreach ($parametres as $param) {
            if (strpos($param['nom'], 'alerte_') === 0) {
                $categorised['alertes'][] = $param;
            } elseif (strpos($param['nom'], 'couleur_') === 0) {
                $categorised['couleurs'][] = $param;
            } elseif (in_array($param['nom'], ['nom_magasin', 'adresse_magasin', 'responsable'])) {
                $categorised['general'][] = $param;
            } else {
                $categorised['autres'][] = $param;
            }
        }
        
        jsonResponse([
            'parametres' => $parametres,
            'categorised' => $categorised,
            'total' => count($parametres)
        ]);
        
    } catch (Exception $e) {
        logError("Erreur récupération paramètres: " . $e->getMessage());
        jsonResponse(['error' => 'Erreur lors de la récupération des paramètres'], 500);
    }
}

/**
 * Créer un nouveau paramètre
 */
function handlePost($input) {
    try {
        if (!$input) {
            jsonResponse(['error' => 'Données manquantes'], 400);
        }
        
        // Validation des données
        $errors = validateParametreData($input, true);
        ApiUtils::errorsToJson($errors);
        
        $db = Database::getInstance()->getConnection();
        
        // Vérifier que le paramètre n'existe pas déjà
        $checkQuery = "SELECT id FROM parametres WHERE nom = :nom";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindValue(':nom', $input['nom']);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            jsonResponse(['error' => 'Un paramètre avec ce nom existe déjà'], 409);
        }
        
        // Insérer le nouveau paramètre
        $query = "
            INSERT INTO parametres (nom, valeur, type, description, modifie_par)
            VALUES (:nom, :valeur, :type, :description, :modifie_par)
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':nom', $input['nom']);
        $stmt->bindValue(':valeur', $input['valeur']);
        $stmt->bindValue(':type', $input['type'] ?? 'string');
        $stmt->bindValue(':description', $input['description'] ?? '');
        $stmt->bindValue(':modifie_par', $input['modifie_par'] ?? 'admin');
        
        if ($stmt->execute()) {
            $id = $db->lastInsertId();
            
            // Récupérer le paramètre créé
            $selectQuery = "SELECT * FROM parametres WHERE id = :id";
            $selectStmt = $db->prepare($selectQuery);
            $selectStmt->bindValue(':id', $id);
            $selectStmt->execute();
            $parametre = $selectStmt->fetch();
            
            // Vider le cache si les paramètres d'alerte ont changé
            if (strpos($input['nom'], 'alerte_') === 0 || strpos($input['nom'], 'couleur_') === 0) {
                ApiUtils::clearCache('dashboard_*');
                ApiUtils::clearCache('lots_*');
            }
            
            jsonResponse([
                'message' => 'Paramètre créé avec succès',
                'parametre' => $parametre
            ], 201);
        } else {
            jsonResponse(['error' => 'Erreur lors de la création du paramètre'], 500);
        }
        
    } catch (Exception $e) {
        logError("Erreur création paramètre: " . $e->getMessage());
        jsonResponse(['error' => 'Erreur lors de la création du paramètre'], 500);
    }
}

/**
 * Mettre à jour un paramètre
 */
function handlePut($input) {
    try {
        if (!$input || !isset($input['id'])) {
            jsonResponse(['error' => 'ID du paramètre manquant'], 400);
        }
        
        // Validation des données
        $errors = validateParametreData($input, false);
        ApiUtils::errorsToJson($errors);
        
        $db = Database::getInstance()->getConnection();
        
        // Vérifier que le paramètre existe
        $checkQuery = "SELECT id, nom FROM parametres WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindValue(':id', $input['id']);
        $checkStmt->execute();
        $existing = $checkStmt->fetch();
        
        if (!$existing) {
            jsonResponse(['error' => 'Paramètre non trouvé'], 404);
        }
        
        // Si le nom change, vérifier l'unicité
        if (isset($input['nom']) && $input['nom'] !== $existing['nom']) {
            $uniqueQuery = "SELECT id FROM parametres WHERE nom = :nom AND id != :id";
            $uniqueStmt = $db->prepare($uniqueQuery);
            $uniqueStmt->bindValue(':nom', $input['nom']);
            $uniqueStmt->bindValue(':id', $input['id']);
            $uniqueStmt->execute();
            
            if ($uniqueStmt->fetch()) {
                jsonResponse(['error' => 'Un paramètre avec ce nom existe déjà'], 409);
            }
        }
        
        // Construire la requête de mise à jour
        $setParts = [];
        $params = [':id' => $input['id']];
        
        $allowedFields = ['nom', 'valeur', 'type', 'description', 'modifie_par'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $setParts[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }
        
        if (empty($setParts)) {
            jsonResponse(['error' => 'Aucune donnée à mettre à jour'], 400);
        }
        
        $query = "
            UPDATE parametres 
            SET " . implode(', ', $setParts) . ", date_modification = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        
        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($stmt->execute()) {
            // Récupérer le paramètre mis à jour
            $selectQuery = "SELECT * FROM parametres WHERE id = :id";
            $selectStmt = $db->prepare($selectQuery);
            $selectStmt->bindValue(':id', $input['id']);
            $selectStmt->execute();
            $parametre = $selectStmt->fetch();
            
            // Vider le cache si nécessaire
            $paramName = $parametre['nom'];
            if (strpos($paramName, 'alerte_') === 0 || strpos($paramName, 'couleur_') === 0) {
                ApiUtils::clearCache('dashboard_*');
                ApiUtils::clearCache('lots_*');
            }
            
            jsonResponse([
                'message' => 'Paramètre mis à jour avec succès',
                'parametre' => $parametre
            ]);
        } else {
            jsonResponse(['error' => 'Erreur lors de la mise à jour du paramètre'], 500);
        }
        
    } catch (Exception $e) {
        logError("Erreur mise à jour paramètre: " . $e->getMessage());
        jsonResponse(['error' => 'Erreur lors de la mise à jour du paramètre'], 500);
    }
}

/**
 * Supprimer un paramètre
 */
function handleDelete() {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            jsonResponse(['error' => 'ID du paramètre manquant'], 400);
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Vérifier que le paramètre existe et n'est pas système
        $checkQuery = "
            SELECT id, nom, type 
            FROM parametres 
            WHERE id = :id
        ";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindValue(':id', $id);
        $checkStmt->execute();
        $parametre = $checkStmt->fetch();
        
        if (!$parametre) {
            jsonResponse(['error' => 'Paramètre non trouvé'], 404);
        }
        
        // Empêcher la suppression des paramètres système critiques
        $parametresSysteme = [
            'alerte_urgent', 'alerte_important', 'alerte_moyen', 'alerte_faible',
            'couleur_urgent', 'couleur_important', 'couleur_moyen', 'couleur_faible', 'couleur_expire'
        ];
        
        if (in_array($parametre['nom'], $parametresSysteme)) {
            jsonResponse(['error' => 'Ce paramètre système ne peut pas être supprimé'], 403);
        }
        
        // Supprimer le paramètre
        $deleteQuery = "DELETE FROM parametres WHERE id = :id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindValue(':id', $id);
        
        if ($deleteStmt->execute()) {
            // Vider le cache si nécessaire
            if (strpos($parametre['nom'], 'alerte_') === 0 || strpos($parametre['nom'], 'couleur_') === 0) {
                ApiUtils::clearCache('dashboard_*');
                ApiUtils::clearCache('lots_*');
            }
            
            jsonResponse([
                'message' => 'Paramètre supprimé avec succès',
                'parametre_supprime' => $parametre
            ]);
        } else {
            jsonResponse(['error' => 'Erreur lors de la suppression du paramètre'], 500);
        }
        
    } catch (Exception $e) {
        logError("Erreur suppression paramètre: " . $e->getMessage());
        jsonResponse(['error' => 'Erreur lors de la suppression du paramètre'], 500);
    }
}

/**
 * Valider les données d'un paramètre
 */
function validateParametreData($data, $isCreate = true) {
    $errors = [];

    if ($isCreate && empty($data['nom'])) {
        $errors[] = "Le nom du paramètre est obligatoire";
    }

    if (isset($data['nom'])) {
        if (strlen(trim($data['nom'])) < 2) {
            $errors[] = "Le nom du paramètre doit contenir au moins 2 caractères";
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['nom'])) {
            $errors[] = "Le nom du paramètre ne peut contenir que des lettres, chiffres et underscores";
        }
    }

    if ($isCreate && !isset($data['valeur'])) {
        $errors[] = "La valeur du paramètre est obligatoire";
    }

    if (isset($data['type'])) {
        $typesValides = ['string', 'int', 'float', 'boolean', 'json', 'color'];
        if (!in_array($data['type'], $typesValides)) {
            $errors[] = "Type de paramètre non valide";
        }
        
        // Validation selon le type
        if (isset($data['valeur'])) {
            switch ($data['type']) {
                case 'int':
                    if (!is_numeric($data['valeur']) || (int)$data['valeur'] != $data['valeur']) {
                        $errors[] = "La valeur doit être un entier";
                    }
                    break;
                case 'float':
                    if (!is_numeric($data['valeur'])) {
                        $errors[] = "La valeur doit être un nombre";
                    }
                    break;
                case 'boolean':
                    if (!in_array(strtolower($data['valeur']), ['true', 'false', '1', '0'])) {
                        $errors[] = "La valeur doit être true ou false";
                    }
                    break;
                case 'json':
                    if (json_decode($data['valeur']) === null && json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = "La valeur doit être un JSON valide";
                    }
                    break;
                case 'color':
                    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $data['valeur'])) {
                        $errors[] = "La valeur doit être une couleur hexadécimale valide (#RRGGBB)";
                    }
                    break;
            }
        }
    }

    return $errors;
}
?>