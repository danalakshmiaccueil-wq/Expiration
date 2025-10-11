# 🗄️ Schéma de base de données - Application Expiration

## Vue d'ensemble
Base de données pour la gestion des dates de péremption des produits alimentaires avec système d'alertes configurables.

---

## 📊 Tables principales

### 1. Table `produits`
**Description** : Catalogue des produits alimentaires du magasin

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique du produit |
| `nom` | VARCHAR(255) | NOT NULL | Nom du produit |
| `code_barre` | VARCHAR(50) | UNIQUE, NULL | Code-barres (optionnel) |
| `categorie` | VARCHAR(100) | NOT NULL | Catégorie (Fruits, Légumes, Laitages, etc.) |
| `description` | TEXT | NULL | Description détaillée (optionnel) |
| `marque` | VARCHAR(100) | NULL | Marque du produit (optionnel) |
| `unite_mesure` | ENUM('kg', 'g', 'L', 'mL', 'pièce') | DEFAULT 'pièce' | Unité de mesure |
| `actif` | BOOLEAN | DEFAULT TRUE | Produit actif/inactif |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Index** :
- Index sur `categorie` pour filtrage rapide
- Index sur `nom` pour recherche
- Index unique sur `code_barre`

---

### 2. Table `lots`
**Description** : Lots de produits avec dates d'expiration spécifiques

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique du lot |
| `produit_id` | INT | FOREIGN KEY → produits(id), NOT NULL | Référence au produit |
| `numero_lot` | VARCHAR(50) | NULL | Numéro de lot fournisseur (optionnel) |
| `date_expiration` | DATE | NOT NULL | Date de péremption |
| `date_reception` | DATE | NOT NULL | Date de réception du lot |
| `quantite_initiale` | DECIMAL(10,3) | NOT NULL, >= 0 | Quantité initiale reçue |
| `quantite_actuelle` | DECIMAL(10,3) | NOT NULL, >= 0 | Quantité restante |
| `prix_achat` | DECIMAL(10,2) | NULL, >= 0 | Prix d'achat unitaire (optionnel) |
| `fournisseur` | VARCHAR(100) | NULL | Nom du fournisseur (optionnel) |
| `statut` | ENUM('actif', 'solde', 'perime', 'retire') | DEFAULT 'actif' | Statut du lot |
| `notes` | TEXT | NULL | Notes libres sur le lot |
| `alerte_j1` | BOOLEAN | DEFAULT FALSE | Alerte 1 jour activée |
| `alerte_j7` | BOOLEAN | DEFAULT FALSE | Alerte 7 jours activée |
| `alerte_j30` | BOOLEAN | DEFAULT FALSE | Alerte 30 jours activée |
| `alerte_j60` | BOOLEAN | DEFAULT FALSE | Alerte 60 jours activée |
| `date_solde` | TIMESTAMP | NULL | Date de marquage comme soldé |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Contraintes** :
- `quantite_actuelle` <= `quantite_initiale`
- `date_expiration` >= `date_reception`
- CASCADE DELETE sur `produit_id`

**Index** :
- Index sur `produit_id` pour jointures
- Index sur `date_expiration` pour calculs d'alertes
- Index sur `statut` pour filtrage
- Index composé sur `(date_expiration, statut)` pour optimiser les alertes

---

### 3. Table `parametres`
**Description** : Configuration globale du système d'alertes

| Champ | Type | Contraintes | Description |
|-------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `nom_parametre` | VARCHAR(50) | UNIQUE, NOT NULL | Nom du paramètre |
| `valeur` | VARCHAR(255) | NOT NULL | Valeur du paramètre |
| `type` | ENUM('boolean', 'integer', 'string', 'json') | DEFAULT 'string' | Type de la valeur |
| `description` | TEXT | NULL | Description du paramètre |
| `actif` | BOOLEAN | DEFAULT TRUE | Paramètre actif/inactif |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Paramètres par défaut** :
- `alerte_j1_active` : TRUE (boolean)
- `alerte_j7_active` : TRUE (boolean)
- `alerte_j30_active` : TRUE (boolean)
- `alerte_j60_active` : TRUE (boolean)
- `couleur_alerte_j1` : "#FF0000" (rouge)
- `couleur_alerte_j7` : "#FF8C00" (orange)
- `couleur_alerte_j30` : "#FFD700" (jaune)
- `couleur_alerte_j60` : "#90EE90" (vert clair)
- `notification_email` : FALSE (boolean)
- `email_notification` : "" (string)

---

## 🔗 Relations entre tables

```
produits (1) ←→ (N) lots
    ↓
    id ←→ produit_id
```

**Type de relation** : One-to-Many (Un produit peut avoir plusieurs lots)

---

## 📈 Vues utiles

### Vue `v_alertes_actives`
```sql
-- Vue pour récupérer tous les lots avec alertes actives
CREATE VIEW v_alertes_actives AS
SELECT 
    l.id as lot_id,
    l.numero_lot,
    p.nom as produit_nom,
    p.categorie,
    l.date_expiration,
    l.quantite_actuelle,
    l.statut,
    CASE 
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 1 THEN 'urgent'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN 'important'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN 'moyen'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 60 THEN 'faible'
        ELSE 'ok'
    END as niveau_alerte,
    DATEDIFF(l.date_expiration, CURDATE()) as jours_restants
FROM lots l
JOIN produits p ON l.produit_id = p.id
WHERE l.statut = 'actif' 
  AND l.date_expiration >= CURDATE()
  AND DATEDIFF(l.date_expiration, CURDATE()) <= 60;
```

### Vue `v_statistiques_produits`
```sql
-- Vue pour statistiques par produit
CREATE VIEW v_statistiques_produits AS
SELECT 
    p.id as produit_id,
    p.nom as produit_nom,
    p.categorie,
    COUNT(l.id) as nombre_lots,
    SUM(l.quantite_actuelle) as quantite_totale,
    MIN(l.date_expiration) as prochaine_expiration,
    COUNT(CASE WHEN l.statut = 'actif' THEN 1 END) as lots_actifs,
    COUNT(CASE WHEN l.statut = 'solde' THEN 1 END) as lots_soldes
FROM produits p
LEFT JOIN lots l ON p.id = l.produit_id
WHERE p.actif = TRUE
GROUP BY p.id, p.nom, p.categorie;
```

---

## 🎯 Stratégie d'indexation

### Index de performance
1. **lots(date_expiration, statut)** - Pour calculs d'alertes rapides
2. **lots(produit_id, statut)** - Pour filtrage par produit
3. **produits(categorie)** - Pour filtrage par catégorie
4. **produits(nom)** - Pour recherche textuelle

### Index de recherche
1. **FULLTEXT sur produits(nom, description)** - Recherche textuelle avancée
2. **lots(numero_lot)** - Recherche par numéro de lot
3. **lots(fournisseur)** - Filtrage par fournisseur

---

## 🔄 Gestion des données

### Règles de suppression
- **Produit supprimé** → Tous les lots associés sont supprimés (CASCADE)
- **Lot "soldé"** → Lot conservé pour historique
- **Lot "périmé"** → Lot conservé pour statistiques

### Règles de mise à jour
- **Changement de statut** → Mise à jour automatique de `updated_at`
- **Modification quantité** → Vérification de cohérence avec `quantite_initiale`
- **Calcul d'alertes** → Mise à jour automatique des flags d'alerte

---

**Prochaine étape** : Création des scripts SQL de création