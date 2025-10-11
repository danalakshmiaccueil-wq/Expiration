-- ============================================
-- Vues pour optimiser les requêtes fréquentes
-- Application : Gestion des dates d'expiration
-- Version : 1.0
-- Date : 11 octobre 2025
-- ============================================

USE expiration_db;

-- ============================================
-- Vue : v_alertes_actives
-- Description : Tous les lots nécessitant une alerte
-- ============================================

CREATE OR REPLACE VIEW v_alertes_actives AS
SELECT 
    l.id as lot_id,
    l.numero_lot,
    p.id as produit_id,
    p.nom as produit_nom,
    p.categorie,
    p.marque,
    l.date_expiration,
    l.date_reception,
    l.quantite_actuelle,
    l.unite_mesure,
    l.fournisseur,
    l.statut,
    l.notes,
    DATEDIFF(l.date_expiration, CURDATE()) as jours_restants,
    CASE 
        WHEN DATEDIFF(l.date_expiration, CURDATE()) < 0 THEN 'expire'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 1 THEN 'urgent'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN 'important'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN 'moyen'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 60 THEN 'faible'
        ELSE 'ok'
    END as niveau_alerte,
    CASE 
        WHEN DATEDIFF(l.date_expiration, CURDATE()) < 0 THEN '#8B0000'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 1 THEN '#FF0000'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN '#FF8C00'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN '#FFD700'
        WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 60 THEN '#90EE90'
        ELSE '#FFFFFF'
    END as couleur_alerte,
    l.created_at,
    l.updated_at
FROM lots l
JOIN produits p ON l.produit_id = p.id
WHERE p.actif = TRUE 
  AND l.statut IN ('actif')
  AND (
    DATEDIFF(l.date_expiration, CURDATE()) <= 60 
    OR DATEDIFF(l.date_expiration, CURDATE()) < 0
  )
ORDER BY l.date_expiration ASC, p.nom ASC;

-- ============================================
-- Vue : v_statistiques_produits
-- Description : Statistiques par produit
-- ============================================

CREATE OR REPLACE VIEW v_statistiques_produits AS
SELECT 
    p.id as produit_id,
    p.nom as produit_nom,
    p.categorie,
    p.marque,
    p.unite_mesure,
    COUNT(l.id) as nombre_lots_total,
    COUNT(CASE WHEN l.statut = 'actif' THEN 1 END) as lots_actifs,
    COUNT(CASE WHEN l.statut = 'solde' THEN 1 END) as lots_soldes,
    COUNT(CASE WHEN l.statut = 'perime' THEN 1 END) as lots_perimes,
    COALESCE(SUM(CASE WHEN l.statut = 'actif' THEN l.quantite_actuelle ELSE 0 END), 0) as quantite_totale_active,
    COALESCE(MIN(CASE WHEN l.statut = 'actif' AND l.date_expiration >= CURDATE() THEN l.date_expiration END), NULL) as prochaine_expiration,
    COALESCE(COUNT(CASE WHEN l.statut = 'actif' AND DATEDIFF(l.date_expiration, CURDATE()) <= 7 THEN 1 END), 0) as alertes_urgentes,
    COALESCE(COUNT(CASE WHEN l.statut = 'actif' AND DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN 1 END), 0) as alertes_importantes,
    p.created_at,
    MAX(l.updated_at) as derniere_modification
FROM produits p
LEFT JOIN lots l ON p.id = l.produit_id
WHERE p.actif = TRUE
GROUP BY p.id, p.nom, p.categorie, p.marque, p.unite_mesure, p.created_at
ORDER BY p.nom ASC;

-- ============================================
-- Vue : v_dashboard_metriques
-- Description : Métriques globales pour le tableau de bord
-- ============================================

CREATE OR REPLACE VIEW v_dashboard_metriques AS
SELECT 
    -- Totaux généraux
    (SELECT COUNT(*) FROM produits WHERE actif = TRUE) as total_produits,
    (SELECT COUNT(*) FROM lots WHERE statut = 'actif') as total_lots_actifs,
    (SELECT COALESCE(SUM(quantite_actuelle), 0) FROM lots WHERE statut = 'actif') as quantite_totale,
    
    -- Alertes par niveau
    (SELECT COUNT(*) FROM v_alertes_actives WHERE niveau_alerte = 'expire') as lots_expires,
    (SELECT COUNT(*) FROM v_alertes_actives WHERE niveau_alerte = 'urgent') as alertes_urgentes,
    (SELECT COUNT(*) FROM v_alertes_actives WHERE niveau_alerte = 'important') as alertes_importantes,
    (SELECT COUNT(*) FROM v_alertes_actives WHERE niveau_alerte = 'moyen') as alertes_moyennes,
    (SELECT COUNT(*) FROM v_alertes_actives WHERE niveau_alerte = 'faible') as alertes_faibles,
    
    -- Statistiques des lots
    (SELECT COUNT(*) FROM lots WHERE statut = 'solde') as lots_soldes,
    (SELECT COUNT(*) FROM lots WHERE statut = 'perime') as lots_perimes,
    (SELECT COUNT(*) FROM lots WHERE statut = 'retire') as lots_retires,
    
    -- Dates importantes
    (SELECT MIN(date_expiration) FROM lots WHERE statut = 'actif' AND date_expiration >= CURDATE()) as prochaine_expiration,
    (SELECT MAX(date_reception) FROM lots WHERE statut = 'actif') as derniere_reception,
    
    -- Catégories
    (SELECT COUNT(DISTINCT categorie) FROM produits WHERE actif = TRUE) as nombre_categories,
    
    -- Timestamp de la vue
    CURRENT_TIMESTAMP as derniere_mise_a_jour;

-- ============================================
-- Vue : v_historique_lots
-- Description : Historique des modifications de lots
-- ============================================

CREATE OR REPLACE VIEW v_historique_lots AS
SELECT 
    l.id as lot_id,
    l.numero_lot,
    p.nom as produit_nom,
    p.categorie,
    l.date_expiration,
    l.date_reception,
    l.quantite_initiale,
    l.quantite_actuelle,
    l.statut,
    l.fournisseur,
    l.date_solde,
    l.created_at as date_creation,
    l.updated_at as derniere_modification,
    DATEDIFF(COALESCE(l.date_solde, CURRENT_TIMESTAMP), l.date_reception) as duree_vie_jours,
    CASE 
        WHEN l.statut = 'solde' AND l.date_solde IS NOT NULL THEN 'Soldé'
        WHEN l.statut = 'perime' THEN 'Périmé'
        WHEN l.statut = 'retire' THEN 'Retiré'
        ELSE 'Actif'
    END as statut_libelle
FROM lots l
JOIN produits p ON l.produit_id = p.id
ORDER BY l.updated_at DESC;

-- ============================================
-- Vue : v_fournisseurs_stats
-- Description : Statistiques par fournisseur
-- ============================================

CREATE OR REPLACE VIEW v_fournisseurs_stats AS
SELECT 
    COALESCE(l.fournisseur, 'Non spécifié') as fournisseur,
    COUNT(*) as nombre_lots,
    COUNT(DISTINCT l.produit_id) as nombre_produits_differents,
    COALESCE(SUM(l.quantite_initiale), 0) as quantite_totale_recue,
    COALESCE(SUM(CASE WHEN l.statut = 'actif' THEN l.quantite_actuelle ELSE 0 END), 0) as quantite_actuelle,
    COUNT(CASE WHEN l.statut = 'solde' THEN 1 END) as lots_soldes,
    COUNT(CASE WHEN l.statut = 'perime' THEN 1 END) as lots_perimes,
    MIN(l.date_reception) as premiere_livraison,
    MAX(l.date_reception) as derniere_livraison,
    COALESCE(AVG(l.prix_achat), 0) as prix_moyen,
    COUNT(CASE WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 7 AND l.statut = 'actif' THEN 1 END) as alertes_actuelles
FROM lots l
GROUP BY COALESCE(l.fournisseur, 'Non spécifié')
HAVING COUNT(*) > 0
ORDER BY nombre_lots DESC;

-- ============================================
-- Messages de confirmation
-- ============================================

SELECT 'Vues créées avec succès!' AS message;
SHOW FULL TABLES WHERE table_type = 'VIEW';