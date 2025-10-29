<?php
/**
 * API Endpoint pour la gestion des produits
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Produit.php';

// Définir les headers CORS
setCorsHeaders();

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer les données JSON pour POST/PUT
$input = json_decode(file_get_contents('php://input'), true);

// Récupérer les paramètres de requête
$id = $_GET['id'] ?? null;
$page = $_GET['page'] ?? 1;
$limit = min($_GET['limit'] ?? DEFAULT_PAGE_SIZE, MAX_PAGE_SIZE);

// Filtres
$filters = [
    'nom' => $_GET['nom'] ?? null,
    'categorie' => $_GET['categorie'] ?? null,
    'code_barre' => $_GET['code_barre'] ?? null,
    'actif' => isset($_GET['actif']) ? filter_var($_GET['actif'], FILTER_VALIDATE_BOOLEAN) : null
];

// Supprimer les filtres vides
$filters = array_filter($filters, function($value) {
    return $value !== null && $value !== '';
});

try {
    $produit = new Produit();

    switch ($method) {
        case 'GET':
            if ($id) {
                // Récupérer un produit spécifique
                $result = $produit->getById($id);
                if (!$result) {
                    jsonResponse(null, 404, 'Produit non trouvé');
                }
                jsonResponse($result, 200, 'Produit récupéré avec succès');
                
            } elseif (isset($_GET['search'])) {
                // Recherche de produits
                $term = $_GET['search'];
                $searchLimit = min($_GET['limit'] ?? 10, 50);
                $results = $produit->search($term, $searchLimit);
                jsonResponse($results, 200, 'Recherche effectuée avec succès');
                
            } elseif (isset($_GET['categories'])) {
                // Récupérer la liste des catégories
                $categories = $produit->getCategories();
                jsonResponse($categories, 200, 'Catégories récupérées avec succès');
                
            } else {
                // Récupérer tous les produits avec pagination
                $result = $produit->getAll($page, $limit, $filters);
                jsonResponse($result, 200, 'Produits récupérés avec succès');
            }
            break;

        case 'POST':
            // Créer un nouveau produit
            if (!$input) {
                jsonResponse(null, 400, 'Données JSON requises');
            }

            // Validation des champs requis
            $requiredFields = ['nom', 'categorie'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    jsonResponse(null, 400, "Le champ '$field' est obligatoire");
                }
            }

            // Nettoyer et valider les données
            $data = [
                'nom' => trim($input['nom']),
                'code_barre' => !empty($input['code_barre']) ? trim($input['code_barre']) : null,
                'categorie' => trim($input['categorie']),
                'description' => !empty($input['description']) ? trim($input['description']) : null,
                'marque' => !empty($input['marque']) ? trim($input['marque']) : null,
                'unite_mesure' => $input['unite_mesure'] ?? 'pièce',
                'actif' => isset($input['actif']) ? (bool)$input['actif'] : true
            ];

            $result = $produit->create($data);
            jsonResponse($result, 201, 'Produit créé avec succès');
            break;

        case 'PUT':
            // Mettre à jour un produit existant
            if (!$id) {
                jsonResponse(null, 400, 'ID du produit requis');
            }

            if (!$input) {
                jsonResponse(null, 400, 'Données JSON requises');
            }

            // Nettoyer les données d'entrée
            $data = [];
            $allowedFields = ['nom', 'code_barre', 'categorie', 'description', 'marque', 'unite_mesure', 'actif'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if (in_array($field, ['nom', 'code_barre', 'categorie', 'description', 'marque'])) {
                        $data[$field] = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
                    } else {
                        $data[$field] = $input[$field];
                    }
                }
            }

            $result = $produit->update($id, $data);
            jsonResponse($result, 200, 'Produit mis à jour avec succès');
            break;

        case 'DELETE':
            // Supprimer un produit (soft delete)
            if (!$id) {
                jsonResponse(null, 400, 'ID du produit requis');
            }

            $result = $produit->delete($id);
            if ($result) {
                jsonResponse(['id' => $id], 200, 'Produit supprimé avec succès');
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
    logError("Erreur API produits: " . $e->getMessage(), [
        'method' => $method,
        'id' => $id,
        'input' => $input,
        'filters' => $filters
    ]);

    // Réponse d'erreur appropriée
    $statusCode = 500;
    $message = $e->getMessage();

    // Mapper certaines erreurs à des codes HTTP spécifiques
    if (strpos($message, 'obligatoire') !== false || 
        strpos($message, 'requis') !== false ||
        strpos($message, 'non valide') !== false) {
        $statusCode = 400;
    } elseif (strpos($message, 'introuvable') !== false) {
        $statusCode = 404;
    } elseif (strpos($message, 'existe déjà') !== false) {
        $statusCode = 409;
    }

    jsonResponse(null, $statusCode, $message);
}
?>