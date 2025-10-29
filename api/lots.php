<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inclusion de la configuration
if (file_exists('api/config/database.php')) {
    require_once 'api/config/database.php';
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
} else {
    // Configuration directe si les fichiers ne sont pas trouvés
    define("DB_HOST", "localhost");
    define("DB_NAME", "sc3bera6697_danalakshmi_expiration");
    define("DB_USER", "sc3bera6697_danalakshmi_user");
    define("DB_PASS", "0617443516Et?");
    define("DB_CHARSET", "utf8mb4");
    
    class Database {
        private $host = DB_HOST;
        private $db_name = DB_NAME;
        private $username = DB_USER;
        private $password = DB_PASS;
        private $pdo;
        
        public function getConnection() {
            if ($this->pdo == null) {
                try {
                    $this->pdo = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . DB_CHARSET,
                        $this->username,
                        $this->password,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false
                        ]
                    );
                } catch(PDOException $e) {
                    error_log("Erreur connexion DB: " . $e->getMessage());
                    throw $e;
                }
            }
            return $this->pdo;
        }
    }
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            throw new Exception('Méthode non supportée');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGet($pdo) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $show_sold = isset($_GET['show_sold']) ? $_GET['show_sold'] === '1' : false;
    $produit_id = isset($_GET['produit_id']) ? (int)$_GET['produit_id'] : null;
    
    // Base query avec JOIN pour récupérer le nom du produit
    $where_conditions = [];
    $params = [];
    
    // Filtrage par produit spécifique
    if ($produit_id) {
        $where_conditions[] = "l.produit_id = :produit_id";
        $params[':produit_id'] = $produit_id;
    }
    
    // Recherche par nom de produit ou numéro de lot
    if (!empty($search)) {
        $where_conditions[] = "(p.nom LIKE :search OR l.numero_lot LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Filtrage par statut d'expiration
    if (!empty($status_filter)) {
        switch ($status_filter) {
            case 'expired':
                $where_conditions[] = "l.date_expiration < CURDATE() AND l.statut != 'soldé'";
                break;
            case 'today':
                $where_conditions[] = "l.date_expiration = CURDATE() AND l.statut != 'soldé'";
                break;
            case 'week':
                $where_conditions[] = "l.date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND l.statut != 'soldé'";
                break;
            case 'month':
                $where_conditions[] = "l.date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND l.statut != 'soldé'";
                break;
            case 'sold':
                $where_conditions[] = "l.statut = 'soldé'";
                break;
        }
    }
    
    // Masquer les lots soldés si la case n'est pas cochée
    if (!$show_sold && $status_filter !== 'sold') {
        $where_conditions[] = "l.statut != 'soldé'";
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Requête principale
    $sql = "SELECT l.*, p.nom as produit_nom
            FROM lots l 
            LEFT JOIN produits p ON l.produit_id = p.id 
            $where_clause
            ORDER BY l.date_expiration ASC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind des paramètres WHERE en premier
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind des paramètres LIMIT/OFFSET avec le bon type
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total 
                  FROM lots l 
                  LEFT JOIN produits p ON l.produit_id = p.id 
                  $where_clause";
    
    $count_stmt = $pdo->prepare($count_sql);
    // Bind seulement les paramètres du WHERE (pas limit/offset)
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $lots,
        'count' => count($lots),
        'total' => (int)$total
    ]);
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Données JSON invalides');
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createLot($pdo, $input);
            break;
        case 'mark_sold':
            markAsSold($pdo, $input);
            break;
        default:
            throw new Exception('Action non supportée');
    }
}

function createLot($pdo, $data) {
    $required_fields = ['produit_id', 'quantite', 'date_expiration'];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }
    
    // Générer un numéro de lot unique
    $numero_lot = 'LOT' . time();
    
    // Utiliser la date de fabrication (date d'ajout) fournie ou la date actuelle
    $date_fabrication = !empty($data['date_fabrication']) ? $data['date_fabrication'] : date('Y-m-d');
    
    $sql = "INSERT INTO lots (produit_id, numero_lot, date_expiration, date_fabrication, 
            quantite_initiale, quantite_actuelle, lieu_stockage, statut)
            VALUES (:produit_id, :numero_lot, :date_expiration, :date_fabrication,
            :quantite_initiale, :quantite_actuelle, :lieu_stockage, 'actif')";
    
    // Ajouter le type d'unité dans le lieu_stockage temporairement
    $lieu_stockage = $data['emplacement'] ?? '';
    $unite_type = $data['unite_type'] ?? 'palette';
    if ($lieu_stockage && $unite_type !== 'unité') {
        $lieu_stockage .= ' (' . $unite_type . ')';
    } elseif (!$lieu_stockage && $unite_type !== 'unité') {
        $lieu_stockage = '(' . $unite_type . ')';
    }
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        ':produit_id' => $data['produit_id'],
        ':numero_lot' => $numero_lot,
        ':date_expiration' => $data['date_expiration'],
        ':date_fabrication' => $date_fabrication,
        ':quantite_initiale' => $data['quantite'],
        ':quantite_actuelle' => $data['quantite'],
        ':lieu_stockage' => $lieu_stockage
    ]);
    
    if ($success) {
        $lot_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Lot créé avec succès',
            'data' => [
                'id' => $lot_id,
                'numero_lot' => $numero_lot
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de la création du lot');
    }
}

function markAsSold($pdo, $data) {
    if (empty($data['id'])) {
        throw new Exception('ID du lot requis');
    }
    
    $sql = "UPDATE lots SET 
            statut = 'soldé',
            date_solde = CURDATE(),
            date_modification = NOW()
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([':id' => $data['id']]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Lot marqué comme soldé'
        ]);
    } else {
        throw new Exception('Lot non trouvé ou déjà soldé');
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        throw new Exception('ID du lot requis');
    }
    
    $id = $input['id'];
    $updates = [];
    $params = [':id' => $id];
    
    // Champs modifiables
    $allowed_fields = ['date_expiration', 'quantite_actuelle', 'lieu_stockage', 'statut'];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        throw new Exception('Aucune donnée à modifier');
    }
    
    $updates[] = "date_modification = NOW()";
    $sql = "UPDATE lots SET " . implode(', ', $updates) . " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Lot modifié avec succès'
        ]);
    } else {
        throw new Exception('Lot non trouvé');
    }
}

function handleDelete($pdo) {
    if (empty($_GET['id'])) {
        throw new Exception('ID du lot requis');
    }
    
    $id = $_GET['id'];
    
    $sql = "DELETE FROM lots WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([':id' => $id]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Lot supprimé avec succès'
        ]);
    } else {
        throw new Exception('Lot non trouvé');
    }
}
?>