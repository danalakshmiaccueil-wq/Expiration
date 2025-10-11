-- ============================================
-- Script de création de la base de données
-- Application : Gestion des dates d'expiration
-- Version : 1.0
-- Date : 11 octobre 2025
-- ============================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS expiration_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE expiration_db;

-- ============================================
-- Table : produits
-- Description : Catalogue des produits alimentaires
-- ============================================

CREATE TABLE produits (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    code_barre VARCHAR(50) NULL,
    categorie VARCHAR(100) NOT NULL,
    description TEXT NULL,
    marque VARCHAR(100) NULL,
    unite_mesure ENUM('kg', 'g', 'L', 'mL', 'pièce') DEFAULT 'pièce',
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_produits_code_barre (code_barre),
    INDEX idx_produits_categorie (categorie),
    INDEX idx_produits_nom (nom),
    INDEX idx_produits_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table : lots
-- Description : Lots de produits avec dates d'expiration
-- ============================================

CREATE TABLE lots (
    id INT NOT NULL AUTO_INCREMENT,
    produit_id INT NOT NULL,
    numero_lot VARCHAR(50) NULL,
    date_expiration DATE NOT NULL,
    date_reception DATE NOT NULL,
    quantite_initiale DECIMAL(10,3) NOT NULL,
    quantite_actuelle DECIMAL(10,3) NOT NULL,
    prix_achat DECIMAL(10,2) NULL,
    fournisseur VARCHAR(100) NULL,
    statut ENUM('actif', 'solde', 'perime', 'retire') DEFAULT 'actif',
    notes TEXT NULL,
    alerte_j1 BOOLEAN DEFAULT FALSE,
    alerte_j7 BOOLEAN DEFAULT FALSE,
    alerte_j30 BOOLEAN DEFAULT FALSE,
    alerte_j60 BOOLEAN DEFAULT FALSE,
    date_solde TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    FOREIGN KEY fk_lots_produit (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    INDEX idx_lots_produit (produit_id),
    INDEX idx_lots_expiration (date_expiration),
    INDEX idx_lots_statut (statut),
    INDEX idx_lots_expiration_statut (date_expiration, statut),
    INDEX idx_lots_numero (numero_lot),
    INDEX idx_lots_fournisseur (fournisseur),
    
    -- Contraintes de validation
    CONSTRAINT chk_lots_quantite_positive CHECK (quantite_initiale >= 0),
    CONSTRAINT chk_lots_quantite_actuelle_positive CHECK (quantite_actuelle >= 0),
    CONSTRAINT chk_lots_quantite_coherente CHECK (quantite_actuelle <= quantite_initiale),
    CONSTRAINT chk_lots_prix_positif CHECK (prix_achat IS NULL OR prix_achat >= 0),
    CONSTRAINT chk_lots_dates_coherentes CHECK (date_expiration >= date_reception)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table : parametres
-- Description : Configuration du système d'alertes
-- ============================================

CREATE TABLE parametres (
    id INT NOT NULL AUTO_INCREMENT,
    nom_parametre VARCHAR(50) NOT NULL,
    valeur VARCHAR(255) NOT NULL,
    type ENUM('boolean', 'integer', 'string', 'json') DEFAULT 'string',
    description TEXT NULL,
    actif BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_parametres_nom (nom_parametre),
    INDEX idx_parametres_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insertion des paramètres par défaut
-- ============================================

INSERT INTO parametres (nom_parametre, valeur, type, description) VALUES
('alerte_j1_active', 'true', 'boolean', 'Activer les alertes à 1 jour'),
('alerte_j7_active', 'true', 'boolean', 'Activer les alertes à 7 jours'),
('alerte_j30_active', 'true', 'boolean', 'Activer les alertes à 30 jours'),
('alerte_j60_active', 'true', 'boolean', 'Activer les alertes à 60 jours'),
('couleur_alerte_j1', '#FF0000', 'string', 'Couleur pour alerte urgent (1 jour)'),
('couleur_alerte_j7', '#FF8C00', 'string', 'Couleur pour alerte important (7 jours)'),
('couleur_alerte_j30', '#FFD700', 'string', 'Couleur pour alerte moyen (30 jours)'),
('couleur_alerte_j60', '#90EE90', 'string', 'Couleur pour alerte faible (60 jours)'),
('notification_email', 'false', 'boolean', 'Activer les notifications par email'),
('email_notification', '', 'string', 'Adresse email pour les notifications'),
('nom_magasin', 'Danalakshmi', 'string', 'Nom du magasin'),
('fuseau_horaire', 'Europe/Paris', 'string', 'Fuseau horaire du magasin');

-- ============================================
-- Vérification de la création
-- ============================================

-- Afficher les tables créées
SHOW TABLES;

-- Afficher la structure des tables
DESCRIBE produits;
DESCRIBE lots;
DESCRIBE parametres;

-- Message de confirmation
SELECT 'Base de données créée avec succès!' AS message;