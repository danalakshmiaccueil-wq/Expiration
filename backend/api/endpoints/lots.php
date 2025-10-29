<?php
/**
 * API Endpoint pour la gestion des lots
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Lot.php';

// Définir les headers CORS
setCorsHeaders();

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer les données JSON pour POST/PUT/PATCH
$input = json_decode(file_get_contents('php://input'), true);

// Récupérer les paramètres de requête
$id = $_GET['id'] ?? null;
$page = $_GET['page'] ?? 1;
$limit = min($_GET['limit'] ?? DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);

// Actions spéciales
$action = $_GET['action'] ?? null;

// Filtres
$filters = [
    'statut' => $_GET['statut'] ?? null,
    'produit_id' => $_GET['produit_id'] ?? null,
    'categorie' => $_GET['categorie'] ?? null,
    'fournisseur' => $_GET['fournisseur'] ?? null,
    'alerte_niveau' => $_GET['alerte_niveau'] ?? null,
    'date_expiration_min' => $_GET['date_expiration_min'] ?? null,
    'date_expiration_max' => $_GET['date_expiration_max'] ?? null
];

// Supprimer les filtres vides
$filters = array_filter($filters, function($value) {
    return $value !== null && $value !== '';
});

try {
    $lot = new Lot();

    switch ($method) {
        case 'GET':
            if ($action === 'alertes') {
                // Récupérer les alertes
                $niveau = $_GET['niveau'] ?? null;
                $alertes = $lot->getAlertes($niveau);
                jsonResponse($alertes, 200, 'Alertes récupérées avec succès');
                
            } elseif ($action === 'update_alertes') {
                // Mettre à jour les alertes
                $lot->updateAlertes();
                jsonResponse(['updated' => true], 200, 'Alertes mises à jour avec succès');
                
            } elseif ($id) {
                // Récupérer un lot spécifique
                $result = $lot->getById($id);
                if (!$result) {
                    jsonResponse(null, 404, 'Lot non trouvé');
                }
                jsonResponse($result, 200, 'Lot récupéré avec succès');
                
            } else {
                // Récupérer tous les lots avec pagination
                $result = $lot->getAll($page, $limit, $filters);
                jsonResponse($result, 200, 'Lots récupérés avec succès');
            }
            break;

        case 'POST':
            // Créer un nouveau lot
            if (!$input) {
                jsonResponse(null, 400, 'Données JSON requises');
            }

            // Validation des champs requis
            $requiredFields = ['produit_id', 'date_expiration', 'date_reception', 'quantite_initiale'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || $input[$field] === '') {
                    jsonResponse(null, 400, "Le champ '$field' est obligatoire");
                }
            }

            // Validation et conversion des types
            $data = [
                'produit_id' => (int)$input['produit_id'],
                'numero_lot' => !empty($input['numero_lot']) ? trim($input['numero_lot']) : null,
                'date_expiration' => $input['date_expiration'],
                'date_reception' => $input['date_reception'],
                'quantite_initiale' => (float)$input['quantite_initiale'],
                'quantite_actuelle' => isset($input['quantite_actuelle']) ? (float)$input['quantite_actuelle'] : (float)$input['quantite_initiale'],
                'prix_achat' => !empty($input['prix_achat']) ? (float)$input['prix_achat'] : null,
                'fournisseur' => !empty($input['fournisseur']) ? trim($input['fournisseur']) : null,
                'statut' => $input['statut'] ?? 'actif',
                'notes' => !empty($input['notes']) ? trim($input['notes']) : null
            ];

            $result = $lot->create($data);
            jsonResponse($result, 201, 'Lot créé avec succès');
            break;

        case 'PUT':
            // Mettre à jour un lot existant
            if (!$id) {
                jsonResponse(null, 400, 'ID du lot requis');
            }

            if (!$input) {
                jsonResponse(null, 400, 'Données JSON requises');
            }

            // Nettoyer les données d'entrée
            $data = [];
            $allowedFields = ['numero_lot', 'date_expiration', 'date_reception', 
                            'quantite_initiale', 'quantite_actuelle', 'prix_achat', 
                            'fournisseur', 'statut', 'notes'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if (in_array($field, ['quantite_initiale', 'quantite_actuelle', 'prix_achat'])) {
                        $data[$field] = !empty($input[$field]) ? (float)$input[$field] : null;
                    } elseif (in_array($field, ['numero_lot', 'fournisseur', 'notes'])) {
                        $data[$field] = !empty($input[$field]) ? trim($input[$field]) : null;
                    } else {
                        $data[$field] = $input[$field];
                    }
                }
            }

            $result = $lot->update($id, $data);
            jsonResponse($result, 200, 'Lot mis à jour avec succès');
            break;

        case 'PATCH':
            // Actions spéciales (marquer soldé, etc.)
            if (!$id) {
                jsonResponse(null, 400, 'ID du lot requis');
            }

            if ($action === 'marquer_solde') {
                $quantiteSolde = isset($input['quantite_solde']) ? (float)$input['quantite_solde'] : null;
                $notes = $input['notes'] ?? '';
                
                $result = $lot->marquerSolde($id, $quantiteSolde, $notes);
                jsonResponse($result, 200, 'Lot marqué comme soldé avec succès');
                
            } else {
                jsonResponse(null, 400, 'Action non reconnue');
            }
            break;

        case 'DELETE':
            // Supprimer un lot
            if (!$id) {
                jsonResponse(null, 400, 'ID du lot requis');
            }

            // Pour la sécurité, on ne supprime pas vraiment, on change le statut
            $result = $lot->update($id, ['statut' => 'retire']);
            if ($result) {
                jsonResponse(['id' => $id], 200, 'Lot retiré avec succès');
            } else {
                jsonResponse(null, 500, 'Erreur lors de la suppression');
            }
            break;

        default:
            jsonResponse(null, 405, 'Méthode non autorisée');
            break;
    }

} catch (Exception $e) {
    // Log de l'erreur
    logError("Erreur API lots: " . $e->getMessage(), [
        'method' => $method,
        'id' => $id,
        'action' => $action,
        'input' => $input,
        'filters' => $filters
    ]);

    // Réponse d'erreur appropriée
    $statusCode = 500;
    $message = $e->getMessage();

    // Mapper certaines erreurs à des codes HTTP spécifiques
    if (strpos($message, 'obligatoire') !== false || 
        strpos($message, 'requis') !== false ||
        strpos($message, 'non valide') !== false ||
        strpos($message, 'supérieure') !== false ||
        strpos($message, 'négative') !== false ||
        strpos($message, 'antérieure') !== false) {
        $statusCode = 400;
    } elseif (strpos($message, 'introuvable') !== false) {
        $statusCode = 404;
    } elseif (strpos($message, 'Seuls les lots actifs') !== false ||
              strpos($message, 'Impossible de supprimer') !== false) {
        $statusCode = 409;
    }

    jsonResponse(null, $statusCode, $message);
}
?>