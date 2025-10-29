<?php
/**
 * API Endpoint pour le dashboard et métriques
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Définir les headers CORS
setCorsHeaders();

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Seules les requêtes GET sont autorisées pour le dashboard
if ($method !== 'GET') {
    jsonResponse(null, 405, 'Seules les requêtes GET sont autorisées');
}

try {
    $db = Database::getInstance()->getConnection();
    $action = $_GET['action'] ?? 'metriques';

    switch ($action) {
        case 'metriques':
        case 'dashboard':
            // Métriques principales du dashboard
            $query = "SELECT * FROM v_dashboard_metriques";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $metriques = $stmt->fetch();

            // Ajouter des métriques calculées supplémentaires
            $metriques['pourcentage_alertes'] = $metriques['total_lots_actifs'] > 0 
                ? round(($metriques['alertes_urgentes'] + $metriques['alertes_importantes']) / $metriques['total_lots_actifs'] * 100, 2)
                : 0;

            $metriques['taux_rotation'] = $metriques['lots_soldes'] + $metriques['lots_perimes'] > 0
                ? round($metriques['lots_soldes'] / ($metriques['lots_soldes'] + $metriques['lots_perimes']) * 100, 2)
                : 0;

            jsonResponse($metriques, 200, 'Métriques du dashboard récupérées avec succès');
            break;

        case 'alertes_resume':
            // Résumé des alertes par niveau
            $query = "SELECT 
                        niveau_alerte,
                        COUNT(*) as nombre,
                        SUM(quantite_actuelle) as quantite_totale,
                        GROUP_CONCAT(DISTINCT produit_categorie ORDER BY produit_categorie) as categories_impactees
                      FROM v_alertes_actives 
                      GROUP BY niveau_alerte
                      ORDER BY 
                        CASE niveau_alerte
                            WHEN 'expire' THEN 1
                            WHEN 'urgent' THEN 2
                            WHEN 'important' THEN 3
                            WHEN 'moyen' THEN 4
                            WHEN 'faible' THEN 5
                            ELSE 6
                        END";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $alertes = $stmt->fetchAll();

            jsonResponse($alertes, 200, 'Résumé des alertes récupéré avec succès');
            break;

        case 'statistiques_produits':
            // Statistiques par produit
            $limit = min($_GET['limit'] ?? 10, 50);
            $orderBy = $_GET['order_by'] ?? 'alertes_urgentes';
            $allowedOrders = ['alertes_urgentes', 'lots_actifs', 'quantite_totale_active', 'produit_nom'];
            
            if (!in_array($orderBy, $allowedOrders)) {
                $orderBy = 'alertes_urgentes';
            }

            $query = "SELECT * FROM v_statistiques_produits 
                     WHERE lots_actifs > 0 
                     ORDER BY {$orderBy} DESC, produit_nom ASC 
                     LIMIT :limit";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $statistiques = $stmt->fetchAll();

            jsonResponse($statistiques, 200, 'Statistiques par produit récupérées avec succès');
            break;

        case 'fournisseurs_stats':
            // Statistiques par fournisseur
            $query = "SELECT * FROM v_fournisseurs_stats 
                     ORDER BY alertes_actuelles DESC, nombre_lots DESC 
                     LIMIT 20";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $fournisseurs = $stmt->fetchAll();

            jsonResponse($fournisseurs, 200, 'Statistiques par fournisseur récupérées avec succès');
            break;

        case 'tendances':
            // Tendances sur les 30 derniers jours
            $query = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as nouveaux_lots,
                        SUM(quantite_initiale) as quantite_recue,
                        AVG(DATEDIFF(date_expiration, date_reception)) as duree_moyenne_jours
                      FROM lots 
                      WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      GROUP BY DATE(created_at)
                      ORDER BY date ASC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $tendances = $stmt->fetchAll();

            // Statistiques des lots soldés sur la même période
            $query2 = "SELECT 
                         DATE(date_solde) as date,
                         COUNT(*) as lots_soldes,
                         SUM(quantite_initiale) as quantite_solde
                       FROM lots 
                       WHERE date_solde >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                       AND statut = 'solde'
                       GROUP BY DATE(date_solde)
                       ORDER BY date ASC";

            $stmt2 = $db->prepare($query2);
            $stmt2->execute();
            $ventesData = $stmt2->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

            // Fusionner les données
            foreach ($tendances as &$jour) {
                $date = $jour['date'];
                $jour['lots_soldes'] = $ventesData[$date]['lots_soldes'] ?? 0;
                $jour['quantite_solde'] = $ventesData[$date]['quantite_solde'] ?? 0;
            }

            jsonResponse($tendances, 200, 'Tendances sur 30 jours récupérées avec succès');
            break;

        case 'categories_repartition':
            // Répartition par catégories avec alertes
            $query = "SELECT 
                        p.categorie,
                        COUNT(DISTINCT l.id) as total_lots,
                        COUNT(DISTINCT CASE WHEN l.statut = 'actif' THEN l.id END) as lots_actifs,
                        COALESCE(SUM(CASE WHEN l.statut = 'actif' THEN l.quantite_actuelle ELSE 0 END), 0) as quantite_active,
                        COUNT(DISTINCT CASE WHEN l.alerte_j1 = 1 THEN l.id END) as alertes_urgentes,
                        COUNT(DISTINCT CASE WHEN l.alerte_j7 = 1 THEN l.id END) as alertes_importantes,
                        COUNT(DISTINCT CASE WHEN l.alerte_j30 = 1 THEN l.id END) as alertes_moyennes
                      FROM produits p
                      LEFT JOIN lots l ON p.id = l.produit_id
                      WHERE p.actif = 1
                      GROUP BY p.categorie
                      HAVING total_lots > 0
                      ORDER BY alertes_urgentes DESC, lots_actifs DESC";

            $stmt = $db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll();

            jsonResponse($categories, 200, 'Répartition par catégories récupérée avec succès');
            break;

        case 'prochaines_expirations':
            // Prochaines expirations (7 prochains jours)
            $limit = min($_GET['limit'] ?? 20, 100);
            
            $query = "SELECT 
                        l.id,
                        l.numero_lot,
                        p.nom as produit_nom,
                        p.categorie,
                        l.date_expiration,
                        l.quantite_actuelle,
                        l.fournisseur,
                        DATEDIFF(l.date_expiration, CURDATE()) as jours_restants,
                        CASE 
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) < 0 THEN 'expire'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 1 THEN 'urgent'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN 'important'
                            ELSE 'normal'
                        END as niveau_alerte
                      FROM lots l
                      JOIN produits p ON l.produit_id = p.id
                      WHERE l.statut = 'actif' 
                      AND l.date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      ORDER BY l.date_expiration ASC, p.nom ASC
                      LIMIT :limit";

            $stmt = $db->prepare($query);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $expirations = $stmt->fetchAll();

            jsonResponse($expirations, 200, 'Prochaines expirations récupérées avec succès');
            break;

        case 'performances':
            // Métriques de performance système
            $performances = [
                'uptime' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                'timestamp' => date('c'),
                'php_version' => PHP_VERSION,
                'database_status' => 'connected'
            ];

            // Test de performance BDD
            $start = microtime(true);
            $db->query("SELECT 1");
            $performances['db_response_time'] = round((microtime(true) - $start) * 1000, 2) . ' ms';

            jsonResponse($performances, 200, 'Métriques de performance récupérées avec succès');
            break;

        default:
            jsonResponse(null, 400, 'Action non reconnue. Actions disponibles: metriques, alertes_resume, statistiques_produits, fournisseurs_stats, tendances, categories_repartition, prochaines_expirations, performances');
            break;
    }

} catch (Exception $e) {
    logError("Erreur API dashboard: " . $e->getMessage(), [
        'action' => $action ?? 'unknown',
        'query_params' => $_GET
    ]);

    jsonResponse(null, 500, 'Erreur lors de la récupération des données du dashboard');
}
?>