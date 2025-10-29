<?php
/**
 * API des alertes et notifications
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/helpers.php';

// Headers CORS et JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    ApiUtils::logUserAction($method, 'alertes', $input);

    switch ($method) {
        case 'GET':
            handleGet();
            break;
        case 'POST':
            handlePost($input);
            break;
        default:
            jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

} catch (Exception $e) {
    logError("Erreur API alertes: " . $e->getMessage());
    jsonResponse(['error' => 'Erreur interne du serveur'], 500);
}

/**
 * Récupérer les alertes
 */
function handleGet() {
    try {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                getAlertesList();
                break;
            case 'summary':
                getAlertesSummary();
                break;
            case 'dashboard':
                getAlertesForDashboard();
                break;
            case 'urgentes':
                getAlertesUrgentes();
                break;
            case 'par_produit':
                getAlertesByProduit();
                break;
            default:
                jsonResponse(['error' => 'Action non reconnue'], 400);
        }
        
    } catch (Exception $e) {
        logError("Erreur récupération alertes: " . $e->getMessage());
        jsonResponse(['error' => 'Erreur lors de la récupération des alertes'], 500);
    }
}

/**
 * Liste complète des alertes avec filtres
 */
function getAlertesList() {
    $db = Database::getInstance()->getConnection();
    
    // Paramètres de filtrage
    $niveau = $_GET['niveau'] ?? null;
    $categorie = $_GET['categorie'] ?? null;
    $dateMin = $_GET['date_min'] ?? null;
    $dateMax = $_GET['date_max'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    
    // Construction de la requête
    $whereConditions = ["l.quantite_actuelle > 0", "l.est_solde = 0"];
    $params = [];
    
    if ($niveau) {
        $whereConditions[] = "v.niveau_alerte = :niveau";
        $params[':niveau'] = $niveau;
    }
    
    if ($categorie) {
        $whereConditions[] = "p.categorie = :categorie";
        $params[':categorie'] = $categorie;
    }
    
    if ($dateMin) {
        $whereConditions[] = "l.date_expiration >= :date_min";
        $params[':date_min'] = $dateMin;
    }
    
    if ($dateMax) {
        $whereConditions[] = "l.date_expiration <= :date_max";
        $params[':date_max'] = $dateMax;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $query = "
        SELECT 
            v.*,
            p.nom as produit_nom,
            p.categorie,
            l.numero_lot,
            l.date_reception,
            l.quantite_actuelle,
            l.unite_mesure,
            l.fournisseur
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        WHERE $whereClause
        ORDER BY v.jours_restants ASC, v.niveau_alerte ASC
    ";
    
    $countQuery = "
        SELECT COUNT(*) as total
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        WHERE $whereClause
    ";
    
    $result = ApiUtils::paginate($query, $params, $page, $limit, $countQuery);
    
    // Ajouter les couleurs d'alerte
    foreach ($result['data'] as &$alerte) {
        $alerte['couleur'] = ApiUtils::getAlertColor($alerte['jours_restants']);
        $alerte['est_expire'] = $alerte['jours_restants'] < 0;
        $alerte['urgence_score'] = calculateUrgenceScore($alerte);
    }
    
    jsonResponse($result);
}

/**
 * Résumé des alertes par niveau
 */
function getAlertesSummary() {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier le cache
    $cacheKey = 'alertes_summary_' . date('Y-m-d-H');
    $cached = ApiUtils::getFromCache($cacheKey);
    if ($cached) {
        jsonResponse($cached);
        return;
    }
    
    // Résumé par niveau d'alerte
    $query = "
        SELECT 
            niveau_alerte,
            COUNT(*) as nombre_alertes,
            SUM(quantite_actuelle) as quantite_totale,
            AVG(jours_restants) as jours_moyens,
            MIN(jours_restants) as jours_min,
            MAX(jours_restants) as jours_max
        FROM vue_alertes_actives
        GROUP BY niveau_alerte
        ORDER BY 
            CASE niveau_alerte
                WHEN 'urgent' THEN 1
                WHEN 'important' THEN 2
                WHEN 'moyen' THEN 3
                WHEN 'faible' THEN 4
                ELSE 5
            END
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $niveaux = $stmt->fetchAll();
    
    // Alertes par catégorie
    $queryCat = "
        SELECT 
            p.categorie,
            COUNT(v.lot_id) as nombre_alertes,
            SUM(v.quantite_actuelle) as quantite_totale
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        GROUP BY p.categorie
        ORDER BY nombre_alertes DESC
    ";
    
    $stmtCat = $db->prepare($queryCat);
    $stmtCat->execute();
    $categories = $stmtCat->fetchAll();
    
    // Tendance sur 7 jours
    $queryTrend = "
        SELECT 
            DATE(l.date_expiration) as date_expiration,
            COUNT(*) as alertes_jour
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        WHERE l.date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(l.date_expiration)
        ORDER BY date_expiration
    ";
    
    $stmtTrend = $db->prepare($queryTrend);
    $stmtTrend->execute();
    $tendance = $stmtTrend->fetchAll();
    
    // Total général
    $totalQuery = "
        SELECT 
            COUNT(*) as total_alertes,
            SUM(quantite_actuelle) as quantite_totale_alertes,
            COUNT(CASE WHEN jours_restants < 0 THEN 1 END) as produits_expires,
            COUNT(CASE WHEN niveau_alerte = 'urgent' THEN 1 END) as alertes_urgentes
        FROM vue_alertes_actives
    ";
    
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $totaux = $totalStmt->fetch();
    
    $result = [
        'niveaux' => $niveaux,
        'categories' => $categories,
        'tendance_7_jours' => $tendance,
        'totaux' => $totaux,
        'timestamp' => date('c')
    ];
    
    // Sauvegarder en cache
    ApiUtils::saveToCache($cacheKey, $result);
    
    jsonResponse($result);
}

/**
 * Alertes pour le tableau de bord (version simplifiée)
 */
function getAlertesForDashboard() {
    $db = Database::getInstance()->getConnection();
    
    // Alertes les plus urgentes (limite 10)
    $queryUrgent = "
        SELECT 
            v.*,
            p.nom as produit_nom,
            l.numero_lot
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        WHERE v.niveau_alerte IN ('urgent', 'important')
        ORDER BY v.jours_restants ASC
        LIMIT 10
    ";
    
    $stmtUrgent = $db->prepare($queryUrgent);
    $stmtUrgent->execute();
    $alertesUrgentes = $stmtUrgent->fetchAll();
    
    // Compteurs rapides
    $queryStats = "
        SELECT 
            COUNT(CASE WHEN niveau_alerte = 'urgent' THEN 1 END) as urgent,
            COUNT(CASE WHEN niveau_alerte = 'important' THEN 1 END) as important,
            COUNT(CASE WHEN niveau_alerte = 'moyen' THEN 1 END) as moyen,
            COUNT(CASE WHEN niveau_alerte = 'faible' THEN 1 END) as faible,
            COUNT(CASE WHEN jours_restants < 0 THEN 1 END) as expires
        FROM vue_alertes_actives
    ";
    
    $stmtStats = $db->prepare($queryStats);
    $stmtStats->execute();
    $stats = $stmtStats->fetch();
    
    jsonResponse([
        'alertes_urgentes' => $alertesUrgentes,
        'compteurs' => $stats,
        'timestamp' => date('c')
    ]);
}

/**
 * Alertes urgentes uniquement
 */
function getAlertesUrgentes() {
    $db = Database::getInstance()->getConnection();
    
    $query = "
        SELECT 
            v.*,
            p.nom as produit_nom,
            p.categorie,
            l.numero_lot,
            l.date_reception,
            l.quantite_actuelle,
            l.unite_mesure,
            l.fournisseur
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        WHERE v.niveau_alerte = 'urgent'
        ORDER BY v.jours_restants ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $alertes = $stmt->fetchAll();
    
    foreach ($alertes as &$alerte) {
        $alerte['couleur'] = ApiUtils::getAlertColor($alerte['jours_restants']);
        $alerte['est_expire'] = $alerte['jours_restants'] < 0;
        $alerte['urgence_score'] = calculateUrgenceScore($alerte);
    }
    
    jsonResponse([
        'alertes_urgentes' => $alertes,
        'total' => count($alertes)
    ]);
}

/**
 * Alertes groupées par produit
 */
function getAlertesByProduit() {
    $db = Database::getInstance()->getConnection();
    
    $query = "
        SELECT 
            p.id as produit_id,
            p.nom as produit_nom,
            p.categorie,
            COUNT(v.lot_id) as nombre_lots_alerte,
            MIN(v.jours_restants) as jours_min,
            MAX(v.jours_restants) as jours_max,
            SUM(v.quantite_actuelle) as quantite_totale,
            GROUP_CONCAT(
                CONCAT(l.numero_lot, ':', v.jours_restants, ':', v.niveau_alerte) 
                ORDER BY v.jours_restants ASC 
                SEPARATOR '|'
            ) as details_lots
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        GROUP BY p.id, p.nom, p.categorie
        ORDER BY MIN(v.jours_restants) ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $produits = $stmt->fetchAll();
    
    // Parser les détails des lots
    foreach ($produits as &$produit) {
        $details = [];
        if (!empty($produit['details_lots'])) {
            $lots = explode('|', $produit['details_lots']);
            foreach ($lots as $lot) {
                $parts = explode(':', $lot);
                if (count($parts) === 3) {
                    $details[] = [
                        'numero_lot' => $parts[0],
                        'jours_restants' => (int)$parts[1],
                        'niveau_alerte' => $parts[2],
                        'couleur' => ApiUtils::getAlertColor((int)$parts[1])
                    ];
                }
            }
        }
        $produit['lots'] = $details;
        unset($produit['details_lots']);
    }
    
    jsonResponse([
        'produits_avec_alertes' => $produits,
        'total_produits' => count($produits)
    ]);
}

/**
 * Marquer une alerte comme vue/traitée
 */
function handlePost($input) {
    try {
        $action = $input['action'] ?? null;
        
        switch ($action) {
            case 'marquer_vue':
                marquerAlerteVue($input);
                break;
            case 'generer_rapport':
                genererRapportAlertes($input);
                break;
            default:
                jsonResponse(['error' => 'Action non reconnue'], 400);
        }
        
    } catch (Exception $e) {
        logError("Erreur action alerte: " . $e->getMessage());
        jsonResponse(['error' => 'Erreur lors du traitement de l\'action'], 500);
    }
}

/**
 * Marquer une alerte comme vue
 */
function marquerAlerteVue($input) {
    if (!isset($input['lot_id'])) {
        jsonResponse(['error' => 'ID du lot manquant'], 400);
    }
    
    // Pour l'instant, on log juste l'action
    // Dans une version future, on pourrait avoir une table des alertes vues
    ApiUtils::logUserAction('alerte_vue', 'lot', ['lot_id' => $input['lot_id']]);
    
    jsonResponse(['message' => 'Alerte marquée comme vue']);
}

/**
 * Générer un rapport d'alertes
 */
function genererRapportAlertes($input) {
    $db = Database::getInstance()->getConnection();
    
    $dateDebut = $input['date_debut'] ?? date('Y-m-d');
    $dateFin = $input['date_fin'] ?? date('Y-m-d', strtotime('+30 days'));
    $format = $input['format'] ?? 'json';
    
    $query = "
        SELECT 
            p.nom as produit,
            p.categorie,
            l.numero_lot,
            l.date_expiration,
            l.quantite_actuelle,
            l.unite_mesure,
            v.jours_restants,
            v.niveau_alerte,
            l.fournisseur
        FROM vue_alertes_actives v
        JOIN lots l ON v.lot_id = l.id
        JOIN produits p ON l.produit_id = p.id
        WHERE l.date_expiration BETWEEN :date_debut AND :date_fin
        ORDER BY l.date_expiration ASC, v.niveau_alerte ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':date_debut', $dateDebut);
    $stmt->bindValue(':date_fin', $dateFin);
    $stmt->execute();
    $donnees = $stmt->fetchAll();
    
    if ($format === 'csv') {
        // Générer CSV (à implémenter si nécessaire)
        jsonResponse(['message' => 'Format CSV non encore implémenté'], 501);
    } else {
        jsonResponse([
            'rapport' => $donnees,
            'periode' => ['debut' => $dateDebut, 'fin' => $dateFin],
            'total_alertes' => count($donnees),
            'genere_le' => date('c')
        ]);
    }
}

/**
 * Calculer un score d'urgence pour prioriser les alertes
 */
function calculateUrgenceScore($alerte) {
    $score = 0;
    
    // Score basé sur les jours restants
    if ($alerte['jours_restants'] < 0) {
        $score += 100; // Déjà expiré
    } elseif ($alerte['jours_restants'] <= 1) {
        $score += 80;
    } elseif ($alerte['jours_restants'] <= 7) {
        $score += 60;
    } elseif ($alerte['jours_restants'] <= 30) {
        $score += 40;
    } else {
        $score += 20;
    }
    
    // Score basé sur la quantité
    if ($alerte['quantite_actuelle'] > 100) {
        $score += 20;
    } elseif ($alerte['quantite_actuelle'] > 50) {
        $score += 15;
    } elseif ($alerte['quantite_actuelle'] > 10) {
        $score += 10;
    } else {
        $score += 5;
    }
    
    return $score;
}
?>