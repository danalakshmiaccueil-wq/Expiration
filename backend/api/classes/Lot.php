<?php
/**
 * Classe Lot - Gestion des lots et alertes
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Lot {
    private $db;
    private $table = 'lots';

    // Propriétés de la classe
    public $id;
    public $produit_id;
    public $numero_lot;
    public $date_expiration;
    public $date_reception;
    public $quantite_initiale;
    public $quantite_actuelle;
    public $prix_achat;
    public $fournisseur;
    public $statut;
    public $notes;
    public $alerte_j1;
    public $alerte_j7;
    public $alerte_j30;
    public $alerte_j60;
    public $date_solde;
    public $created_at;
    public $updated_at;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupérer tous les lots avec informations produit
     */
    public function getAll($page = 1, $limit = DEFAULT_PAGE_SIZE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ['l.id IS NOT NULL'];
            $params = [];

            // Filtres dynamiques
            if (!empty($filters['statut'])) {
                $where[] = 'l.statut = :statut';
                $params[':statut'] = $filters['statut'];
            }

            if (!empty($filters['produit_id'])) {
                $where[] = 'l.produit_id = :produit_id';
                $params[':produit_id'] = $filters['produit_id'];
            }

            if (!empty($filters['categorie'])) {
                $where[] = 'p.categorie = :categorie';
                $params[':categorie'] = $filters['categorie'];
            }

            if (!empty($filters['fournisseur'])) {
                $where[] = 'l.fournisseur LIKE :fournisseur';
                $params[':fournisseur'] = '%' . $filters['fournisseur'] . '%';
            }

            if (!empty($filters['alerte_niveau'])) {
                switch ($filters['alerte_niveau']) {
                    case 'urgent':
                        $where[] = 'l.alerte_j1 = 1';
                        break;
                    case 'important':
                        $where[] = 'l.alerte_j7 = 1 AND l.alerte_j1 = 0';
                        break;
                    case 'moyen':
                        $where[] = 'l.alerte_j30 = 1 AND l.alerte_j7 = 0';
                        break;
                    case 'faible':
                        $where[] = 'l.alerte_j60 = 1 AND l.alerte_j30 = 0';
                        break;
                }
            }

            if (!empty($filters['date_expiration_min'])) {
                $where[] = 'l.date_expiration >= :date_min';
                $params[':date_min'] = $filters['date_expiration_min'];
            }

            if (!empty($filters['date_expiration_max'])) {
                $where[] = 'l.date_expiration <= :date_max';
                $params[':date_max'] = $filters['date_expiration_max'];
            }

            $whereClause = implode(' AND ', $where);
            
            // Requête principale avec jointure
            $query = "SELECT 
                        l.*,
                        p.nom as produit_nom,
                        p.categorie as produit_categorie,
                        p.unite_mesure as produit_unite,
                        DATEDIFF(l.date_expiration, CURDATE()) as jours_restants,
                        CASE 
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) < 0 THEN 'expire'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 1 THEN 'urgent'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN 'important'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN 'moyen'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 60 THEN 'faible'
                            ELSE 'ok'
                        END as niveau_alerte
                      FROM {$this->table} l
                      JOIN produits p ON l.produit_id = p.id
                      WHERE {$whereClause}
                      ORDER BY l.date_expiration ASC, p.nom ASC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            $lots = $stmt->fetchAll();

            // Compter le total
            $countQuery = "SELECT COUNT(*) as total 
                          FROM {$this->table} l
                          JOIN produits p ON l.produit_id = p.id
                          WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch()['total'];

            return [
                'data' => $lots,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ];

        } catch (PDOException $e) {
            logError("Erreur lors de la récupération des lots: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des lots");
        }
    }

    /**
     * Récupérer un lot par ID avec infos produit
     */
    public function getById($id) {
        try {
            $query = "SELECT 
                        l.*,
                        p.nom as produit_nom,
                        p.categorie as produit_categorie,
                        p.unite_mesure as produit_unite,
                        DATEDIFF(l.date_expiration, CURDATE()) as jours_restants
                      FROM {$this->table} l
                      JOIN produits p ON l.produit_id = p.id
                      WHERE l.id = :id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("Erreur lors de la récupération du lot ID $id: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération du lot");
        }
    }

    /**
     * Créer un nouveau lot
     */
    public function create($data) {
        try {
            // Validation des données
            $this->validateData($data);

            $query = "INSERT INTO {$this->table} 
                     (produit_id, numero_lot, date_expiration, date_reception, 
                      quantite_initiale, quantite_actuelle, prix_achat, fournisseur, 
                      statut, notes) 
                     VALUES (:produit_id, :numero_lot, :date_expiration, :date_reception,
                             :quantite_initiale, :quantite_actuelle, :prix_achat, :fournisseur,
                             :statut, :notes)";

            $stmt = $this->db->prepare($query);

            $stmt->bindParam(':produit_id', $data['produit_id'], PDO::PARAM_INT);
            $stmt->bindParam(':numero_lot', $data['numero_lot']);
            $stmt->bindParam(':date_expiration', $data['date_expiration']);
            $stmt->bindParam(':date_reception', $data['date_reception']);
            $stmt->bindParam(':quantite_initiale', $data['quantite_initiale']);
            $stmt->bindParam(':quantite_actuelle', $data['quantite_actuelle'] ?? $data['quantite_initiale']);
            $stmt->bindParam(':prix_achat', $data['prix_achat']);
            $stmt->bindParam(':fournisseur', $data['fournisseur']);
            $stmt->bindParam(':statut', $data['statut'] ?? 'actif');
            $stmt->bindParam(':notes', $data['notes']);

            if ($stmt->execute()) {
                $lotId = $this->db->lastInsertId();
                
                // Mettre à jour les alertes pour ce lot
                $this->updateAlertes($lotId);
                
                return $this->getById($lotId);
            }

            throw new Exception("Échec de la création du lot");

        } catch (PDOException $e) {
            logError("Erreur lors de la création du lot: " . $e->getMessage());
            throw new Exception("Erreur lors de la création du lot");
        }
    }

    /**
     * Mettre à jour un lot
     */
    public function update($id, $data) {
        try {
            if (!$this->getById($id)) {
                throw new Exception("Lot introuvable");
            }

            $this->validateData($data, false);

            $fields = [];
            $params = [':id' => $id];

            $allowedFields = ['numero_lot', 'date_expiration', 'date_reception', 
                            'quantite_initiale', 'quantite_actuelle', 'prix_achat', 
                            'fournisseur', 'statut', 'notes'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($fields)) {
                throw new Exception("Aucune donnée à mettre à jour");
            }

            $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($stmt->execute()) {
                // Mettre à jour les alertes si la date d'expiration a changé
                if (isset($data['date_expiration'])) {
                    $this->updateAlertes($id);
                }
                
                return $this->getById($id);
            }

            throw new Exception("Échec de la mise à jour du lot");

        } catch (PDOException $e) {
            logError("Erreur lors de la mise à jour du lot ID $id: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour du lot");
        }
    }

    /**
     * Marquer un lot comme soldé
     */
    public function marquerSolde($id, $quantiteSolde = null, $notes = '') {
        try {
            $lot = $this->getById($id);
            if (!$lot) {
                throw new Exception("Lot introuvable");
            }

            if ($lot['statut'] !== 'actif') {
                throw new Exception("Seuls les lots actifs peuvent être soldés");
            }

            // Si quantité non spécifiée, solder tout le lot
            if ($quantiteSolde === null) {
                $quantiteSolde = $lot['quantite_actuelle'];
            }

            if ($quantiteSolde > $lot['quantite_actuelle']) {
                throw new Exception("Quantité à solder supérieure à la quantité disponible");
            }

            $this->db->beginTransaction();

            if ($quantiteSolde == $lot['quantite_actuelle']) {
                // Lot entièrement soldé
                $query = "UPDATE {$this->table} 
                         SET quantite_actuelle = 0, 
                             statut = 'solde', 
                             date_solde = NOW(),
                             notes = CONCAT(COALESCE(notes, ''), ' | Soldé: ', :notes)
                         WHERE id = :id";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':notes', $notes);
            } else {
                // Lot partiellement soldé
                $nouvelleQuantite = $lot['quantite_actuelle'] - $quantiteSolde;
                $query = "UPDATE {$this->table} 
                         SET quantite_actuelle = :nouvelle_quantite,
                             notes = CONCAT(COALESCE(notes, ''), ' | Solde partielle: ', :quantite_solde, ' - ', :notes)
                         WHERE id = :id";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':nouvelle_quantite', $nouvelleQuantite);
                $stmt->bindParam(':quantite_solde', $quantiteSolde);
                $stmt->bindParam(':notes', $notes);
            }

            $stmt->execute();
            $this->db->commit();

            return $this->getById($id);

        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erreur lors du marquage soldé du lot ID $id: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les alertes actives
     */
    public function getAlertes($niveau = null) {
        try {
            $where = ['l.statut = "actif"'];
            $params = [];

            if ($niveau) {
                switch ($niveau) {
                    case 'urgent':
                        $where[] = 'l.alerte_j1 = 1';
                        break;
                    case 'important':
                        $where[] = 'l.alerte_j7 = 1';
                        break;
                    case 'moyen':
                        $where[] = 'l.alerte_j30 = 1';
                        break;
                    case 'faible':
                        $where[] = 'l.alerte_j60 = 1';
                        break;
                }
            } else {
                $where[] = '(l.alerte_j1 = 1 OR l.alerte_j7 = 1 OR l.alerte_j30 = 1 OR l.alerte_j60 = 1)';
            }

            $whereClause = implode(' AND ', $where);

            $query = "SELECT 
                        l.*,
                        p.nom as produit_nom,
                        p.categorie as produit_categorie,
                        DATEDIFF(l.date_expiration, CURDATE()) as jours_restants,
                        CASE 
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) < 0 THEN 'expire'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 1 THEN 'urgent'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN 'important'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN 'moyen'
                            WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 60 THEN 'faible'
                            ELSE 'ok'
                        END as niveau_alerte
                      FROM {$this->table} l
                      JOIN produits p ON l.produit_id = p.id
                      WHERE {$whereClause}
                      ORDER BY 
                        CASE 
                            WHEN l.alerte_j1 = 1 THEN 1
                            WHEN l.alerte_j7 = 1 THEN 2
                            WHEN l.alerte_j30 = 1 THEN 3
                            WHEN l.alerte_j60 = 1 THEN 4
                            ELSE 5
                        END,
                        l.date_expiration ASC";

            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            logError("Erreur lors de la récupération des alertes: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des alertes");
        }
    }

    /**
     * Mettre à jour les alertes pour un lot
     */
    public function updateAlertes($lotId = null) {
        try {
            // Appeler la procédure stockée pour mettre à jour les alertes
            $query = "CALL sp_update_alertes()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return true;

        } catch (PDOException $e) {
            logError("Erreur lors de la mise à jour des alertes: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour des alertes");
        }
    }

    /**
     * Validation des données
     */
    private function validateData($data, $isCreate = true) {
        // Validation produit_id (obligatoire à la création)
        if ($isCreate && empty($data['produit_id'])) {
            throw new Exception("L'ID du produit est obligatoire");
        }

        // Validation des dates
        if (isset($data['date_expiration']) && isset($data['date_reception'])) {
            if ($data['date_expiration'] < $data['date_reception']) {
                throw new Exception("La date d'expiration ne peut pas être antérieure à la date de réception");
            }
        }

        // Validation des quantités
        if (isset($data['quantite_initiale'])) {
            if ($data['quantite_initiale'] <= 0) {
                throw new Exception("La quantité initiale doit être positive");
            }
        }

        if (isset($data['quantite_actuelle'])) {
            if ($data['quantite_actuelle'] < 0) {
                throw new Exception("La quantité actuelle ne peut pas être négative");
            }
        }

        // Validation du prix
        if (isset($data['prix_achat']) && !empty($data['prix_achat'])) {
            if ($data['prix_achat'] < 0) {
                throw new Exception("Le prix d'achat ne peut pas être négatif");
            }
        }

        // Validation du statut
        if (isset($data['statut'])) {
            $statutsValides = ['actif', 'solde', 'perime', 'retire'];
            if (!in_array($data['statut'], $statutsValides)) {
                throw new Exception("Statut non valide");
            }
        }

        return true;
    }
}
?>