<?php
/**
 * Classe Produit - Gestion des produits
 * Application : Gestion des dates d'expiration
 * Version : 1.0
 * Date : 11 octobre 2025
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Produit {
    private $db;
    private $table = 'produits';

    // Propriétés de la classe
    public $id;
    public $nom;
    public $code_barre;
    public $categorie;
    public $description;
    public $marque;
    public $unite_mesure;
    public $actif;
    public $created_at;
    public $updated_at;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupérer tous les produits avec pagination et filtres
     */
    public function getAll($page = 1, $limit = DEFAULT_PAGE_SIZE, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ['actif = 1']; // Par défaut, seulement les produits actifs
            $params = [];

            // Filtres dynamiques
            if (!empty($filters['nom'])) {
                $where[] = 'nom LIKE :nom';
                $params[':nom'] = '%' . $filters['nom'] . '%';
            }

            if (!empty($filters['categorie'])) {
                $where[] = 'categorie = :categorie';
                $params[':categorie'] = $filters['categorie'];
            }

            if (!empty($filters['code_barre'])) {
                $where[] = 'code_barre = :code_barre';
                $params[':code_barre'] = $filters['code_barre'];
            }

            if (isset($filters['actif'])) {
                $where[0] = 'actif = :actif'; // Remplacer le filtre par défaut
                $params[':actif'] = $filters['actif'] ? 1 : 0;
            }

            $whereClause = implode(' AND ', $where);
            
            // Requête principale
            $query = "SELECT * FROM {$this->table} 
                     WHERE {$whereClause} 
                     ORDER BY nom ASC 
                     LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($query);
            
            // Bind des paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            $produits = $stmt->fetchAll();

            // Compter le total pour la pagination
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch()['total'];

            return [
                'data' => $produits,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ];

        } catch (PDOException $e) {
            logError("Erreur lors de la récupération des produits: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des produits");
        }
    }

    /**
     * Récupérer un produit par ID
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("Erreur lors de la récupération du produit ID $id: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération du produit");
        }
    }

    /**
     * Créer un nouveau produit
     */
    public function create($data) {
        try {
            // Validation des données
            $this->validateData($data);

            $query = "INSERT INTO {$this->table} 
                     (nom, code_barre, categorie, description, marque, unite_mesure, actif) 
                     VALUES (:nom, :code_barre, :categorie, :description, :marque, :unite_mesure, :actif)";

            $stmt = $this->db->prepare($query);

            // Nettoyage et assignation des données
            $stmt->bindParam(':nom', $data['nom']);
            $stmt->bindParam(':code_barre', $data['code_barre']);
            $stmt->bindParam(':categorie', $data['categorie']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':marque', $data['marque']);
            $stmt->bindParam(':unite_mesure', $data['unite_mesure']);
            $stmt->bindParam(':actif', $data['actif'] ?? 1, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->id = $this->db->lastInsertId();
                return $this->getById($this->id);
            }

            throw new Exception("Échec de la création du produit");

        } catch (PDOException $e) {
            logError("Erreur lors de la création du produit: " . $e->getMessage());
            
            // Gestion des erreurs spécifiques
            if ($e->getCode() == 23000) { // Violation de contrainte unique
                throw new Exception("Un produit avec ce code-barres existe déjà");
            }
            
            throw new Exception("Erreur lors de la création du produit");
        }
    }

    /**
     * Mettre à jour un produit
     */
    public function update($id, $data) {
        try {
            // Vérifier que le produit existe
            if (!$this->getById($id)) {
                throw new Exception("Produit introuvable");
            }

            // Validation des données
            $this->validateData($data, false);

            // Construction dynamique de la requête
            $fields = [];
            $params = [':id' => $id];

            foreach (['nom', 'code_barre', 'categorie', 'description', 'marque', 'unite_mesure', 'actif'] as $field) {
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
                return $this->getById($id);
            }

            throw new Exception("Échec de la mise à jour du produit");

        } catch (PDOException $e) {
            logError("Erreur lors de la mise à jour du produit ID $id: " . $e->getMessage());
            
            if ($e->getCode() == 23000) {
                throw new Exception("Un produit avec ce code-barres existe déjà");
            }
            
            throw new Exception("Erreur lors de la mise à jour du produit");
        }
    }

    /**
     * Supprimer un produit (soft delete)
     */
    public function delete($id) {
        try {
            // Vérifier que le produit existe
            if (!$this->getById($id)) {
                throw new Exception("Produit introuvable");
            }

            // Vérifier s'il y a des lots associés
            $countQuery = "SELECT COUNT(*) as total FROM lots WHERE produit_id = :id AND statut = 'actif'";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $countStmt->execute();
            
            if ($countStmt->fetch()['total'] > 0) {
                throw new Exception("Impossible de supprimer : des lots actifs sont associés à ce produit");
            }

            // Soft delete : marquer comme inactif
            $query = "UPDATE {$this->table} SET actif = 0 WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            return $stmt->execute();

        } catch (PDOException $e) {
            logError("Erreur lors de la suppression du produit ID $id: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression du produit");
        }
    }

    /**
     * Récupérer les catégories distinctes
     */
    public function getCategories() {
        try {
            $query = "SELECT DISTINCT categorie FROM {$this->table} 
                     WHERE actif = 1 AND categorie IS NOT NULL 
                     ORDER BY categorie";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            logError("Erreur lors de la récupération des catégories: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des catégories");
        }
    }

    /**
     * Rechercher des produits
     */
    public function search($term, $limit = 10) {
        try {
            $query = "SELECT id, nom, categorie, code_barre 
                     FROM {$this->table} 
                     WHERE actif = 1 
                     AND (nom LIKE :term OR code_barre LIKE :term OR categorie LIKE :term)
                     ORDER BY 
                         CASE WHEN nom LIKE :exact_term THEN 1 ELSE 2 END,
                         nom ASC
                     LIMIT :limit";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':term', '%' . $term . '%');
            $stmt->bindValue(':exact_term', $term . '%');
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError("Erreur lors de la recherche de produits: " . $e->getMessage());
            throw new Exception("Erreur lors de la recherche");
        }
    }

    /**
     * Validation des données
     */
    private function validateData($data, $isCreate = true) {
        // Validation du nom (obligatoire)
        if ($isCreate && empty($data['nom'])) {
            throw new Exception("Le nom du produit est obligatoire");
        }
        
        if (isset($data['nom'])) {
            $data['nom'] = trim($data['nom']);
            if (strlen($data['nom']) < 2) {
                throw new Exception("Le nom du produit doit contenir au moins 2 caractères");
            }
            if (strlen($data['nom']) > 255) {
                throw new Exception("Le nom du produit ne peut pas dépasser 255 caractères");
            }
        }

        // Validation de la catégorie (obligatoire)
        if ($isCreate && empty($data['categorie'])) {
            throw new Exception("La catégorie est obligatoire");
        }

        // Validation de l'unité de mesure
        if (isset($data['unite_mesure'])) {
            $unitesValides = ['kg', 'g', 'L', 'mL', 'pièce'];
            if (!in_array($data['unite_mesure'], $unitesValides)) {
                throw new Exception("Unité de mesure non valide");
            }
        }

        // Validation du code-barres (si fourni)
        if (!empty($data['code_barre'])) {
            $data['code_barre'] = trim($data['code_barre']);
            if (!preg_match('/^[0-9A-Za-z\-_]+$/', $data['code_barre'])) {
                throw new Exception("Format de code-barres non valide");
            }
        }

        return true;
    }
}
?>