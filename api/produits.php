<?php
require_once "config/database.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $method = $_SERVER["REQUEST_METHOD"];
    
    switch($method) {
        case "GET":
            if (isset($_GET["id"])) {
                $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ? AND actif = 1");
                $stmt->execute([$_GET["id"]]);
                $produit = $stmt->fetch();
                
                if ($produit) {
                    echo json_encode(["success" => true, "data" => $produit]);
                } else {
                    http_response_code(404);
                    echo json_encode(["success" => false, "message" => "Produit non trouvé"]);
                }
            } else {
                // Pour compatibilité avec le frontend existant
                // Retourner les premiers 100 produits par défaut
                $limit = 100;
                
                // Si pagination demandée explicitement
                if (isset($_GET["page"]) || isset($_GET["limit"])) {
                    $page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
                    $limit = isset($_GET["limit"]) ? min(200, max(1, intval($_GET["limit"]))) : 50;
                    $offset = ($page - 1) * $limit;
                } else {
                    $offset = 0;
                }
                
                // Filtres
                $where = ["actif = 1", "nom IS NOT NULL", "nom != ''", "nom != '0'"];
                $params = [];
                
                if (isset($_GET["search"]) && !empty($_GET["search"])) {
                    $where[] = "(nom LIKE ? OR categorie LIKE ? OR code_barre LIKE ?)";
                    $search = "%" . $_GET["search"] . "%";
                    $params[] = $search;
                    $params[] = $search;
                    $params[] = $search;
                }
                
                if (isset($_GET["categorie"]) && !empty($_GET["categorie"])) {
                    $where[] = "categorie = ?";
                    $params[] = $_GET["categorie"];
                }
                
                $whereClause = " WHERE " . implode(" AND ", $where);
                
                // Compter le total
                $countSql = "SELECT COUNT(*) FROM produits" . $whereClause;
                $stmt = $pdo->prepare($countSql);
                $stmt->execute($params);
                $total = $stmt->fetchColumn();
                
                // Récupérer les produits
                $sql = "SELECT * FROM produits" . $whereClause . " ORDER BY nom LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $produits = $stmt->fetchAll();
                
                $response = [
                    "success" => true,
                    "data" => $produits,
                    "count" => count($produits),
                    "total" => intval($total)
                ];
                
                // Ajouter pagination seulement si demandée
                if (isset($_GET["page"]) || isset($_GET["limit"])) {
                    $response["pagination"] = [
                        "page" => $page ?? 1,
                        "limit" => $limit,
                        "total" => intval($total),
                        "pages" => ceil($total / $limit)
                    ];
                }
                
                echo json_encode($response);
            }
            break;
            
        case "POST":
            $input = json_decode(file_get_contents("php://input"), true);
            
            if (!$input || !isset($input["nom"])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Nom du produit requis"]);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO produits (nom, code_barre, categorie, prix_unitaire, unite_mesure, seuil_alerte, fournisseur, actif) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $input["nom"],
                $input["code_barre"] ?? "AUTO" . time(),
                $input["categorie"] ?? "Général",
                $input["prix_unitaire"] ?? 0,
                $input["unite_mesure"] ?? "unité", 
                $input["seuil_alerte"] ?? 10,
                $input["fournisseur"] ?? "Non spécifié"
            ]);
            
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
            $stmt->execute([$id]);
            $produit = $stmt->fetch();
            
            http_response_code(201);
            echo json_encode(["success" => true, "data" => $produit, "message" => "Produit créé avec succès"]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Méthode non autorisée"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur: " . $e->getMessage()]);
}
?>