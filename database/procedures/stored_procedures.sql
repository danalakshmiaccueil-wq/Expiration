-- ============================================
-- Procédures stockées pour la gestion des alertes
-- Application : Gestion des dates d'expiration
-- Version : 1.0
-- Date : 11 octobre 2025
-- ============================================

USE expiration_db;

DELIMITER //

-- ============================================
-- Procédure : sp_update_alertes
-- Description : Met à jour les flags d'alerte pour tous les lots actifs
-- ============================================

CREATE PROCEDURE sp_update_alertes()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Réinitialiser tous les flags d'alerte
    UPDATE lots 
    SET 
        alerte_j1 = FALSE,
        alerte_j7 = FALSE,
        alerte_j30 = FALSE,
        alerte_j60 = FALSE
    WHERE statut = 'actif';
    
    -- Mettre à jour les alertes selon les paramètres actifs
    UPDATE lots l
    JOIN (
        SELECT 
            (SELECT valeur = 'true' FROM parametres WHERE nom_parametre = 'alerte_j1_active' AND actif = TRUE) as j1_active,
            (SELECT valeur = 'true' FROM parametres WHERE nom_parametre = 'alerte_j7_active' AND actif = TRUE) as j7_active,
            (SELECT valeur = 'true' FROM parametres WHERE nom_parametre = 'alerte_j30_active' AND actif = TRUE) as j30_active,
            (SELECT valeur = 'true' FROM parametres WHERE nom_parametre = 'alerte_j60_active' AND actif = TRUE) as j60_active
    ) p
    SET 
        l.alerte_j1 = CASE 
            WHEN p.j1_active AND DATEDIFF(l.date_expiration, CURDATE()) <= 1 AND DATEDIFF(l.date_expiration, CURDATE()) >= 0 
            THEN TRUE ELSE FALSE END,
        l.alerte_j7 = CASE 
            WHEN p.j7_active AND DATEDIFF(l.date_expiration, CURDATE()) <= 7 AND DATEDIFF(l.date_expiration, CURDATE()) >= 0 
            THEN TRUE ELSE FALSE END,
        l.alerte_j30 = CASE 
            WHEN p.j30_active AND DATEDIFF(l.date_expiration, CURDATE()) <= 30 AND DATEDIFF(l.date_expiration, CURDATE()) >= 0 
            THEN TRUE ELSE FALSE END,
        l.alerte_j60 = CASE 
            WHEN p.j60_active AND DATEDIFF(l.date_expiration, CURDATE()) <= 60 AND DATEDIFF(l.date_expiration, CURDATE()) >= 0 
            THEN TRUE ELSE FALSE END
    WHERE l.statut = 'actif';
    
    -- Marquer automatiquement les lots périmés
    UPDATE lots 
    SET statut = 'perime'
    WHERE statut = 'actif' 
      AND date_expiration < CURDATE();
    
    COMMIT;
    
    -- Retourner un résumé
    SELECT 
        'Alertes mises à jour avec succès' as message,
        (SELECT COUNT(*) FROM lots WHERE alerte_j1 = TRUE) as alertes_j1,
        (SELECT COUNT(*) FROM lots WHERE alerte_j7 = TRUE) as alertes_j7,
        (SELECT COUNT(*) FROM lots WHERE alerte_j30 = TRUE) as alertes_j30,
        (SELECT COUNT(*) FROM lots WHERE alerte_j60 = TRUE) as alertes_j60,
        (SELECT COUNT(*) FROM lots WHERE statut = 'perime') as lots_perimes,
        NOW() as mise_a_jour;
        
END //

-- ============================================
-- Procédure : sp_get_alertes_by_level
-- Description : Récupère les alertes filtrées par niveau d'urgence
-- ============================================

CREATE PROCEDURE sp_get_alertes_by_level(IN niveau VARCHAR(20))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        RESIGNAL;
    END;
    
    SELECT 
        lot_id,
        numero_lot,
        produit_nom,
        categorie,
        date_expiration,
        quantite_actuelle,
        fournisseur,
        jours_restants,
        niveau_alerte,
        couleur_alerte
    FROM v_alertes_actives
    WHERE 
        CASE 
            WHEN niveau = 'urgent' THEN niveau_alerte IN ('expire', 'urgent')
            WHEN niveau = 'important' THEN niveau_alerte = 'important'
            WHEN niveau = 'moyen' THEN niveau_alerte = 'moyen'
            WHEN niveau = 'faible' THEN niveau_alerte = 'faible'
            WHEN niveau = 'tous' THEN TRUE
            ELSE niveau_alerte = niveau
        END
    ORDER BY 
        CASE niveau_alerte
            WHEN 'expire' THEN 1
            WHEN 'urgent' THEN 2
            WHEN 'important' THEN 3
            WHEN 'moyen' THEN 4
            WHEN 'faible' THEN 5
            ELSE 6
        END,
        date_expiration ASC;
        
END //

-- ============================================
-- Procédure : sp_marquer_lot_solde
-- Description : Marque un lot comme soldé avec vérification
-- ============================================

CREATE PROCEDURE sp_marquer_lot_solde(
    IN p_lot_id INT,
    IN p_quantite_solde DECIMAL(10,3),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_quantite_actuelle DECIMAL(10,3);
    DECLARE v_statut_actuel VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Vérifier l'état du lot
    SELECT quantite_actuelle, statut 
    INTO v_quantite_actuelle, v_statut_actuel
    FROM lots 
    WHERE id = p_lot_id;
    
    -- Vérifications
    IF v_statut_actuel IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lot introuvable';
    END IF;
    
    IF v_statut_actuel != 'actif' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le lot n''est pas actif';
    END IF;
    
    IF p_quantite_solde > v_quantite_actuelle THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantité à solder supérieure à la quantité disponible';
    END IF;
    
    -- Mettre à jour le lot
    IF p_quantite_solde = v_quantite_actuelle THEN
        -- Lot entièrement soldé
        UPDATE lots 
        SET 
            quantite_actuelle = 0,
            statut = 'solde',
            date_solde = NOW(),
            notes = CONCAT(COALESCE(notes, ''), ' | Soldé: ', COALESCE(p_notes, ''))
        WHERE id = p_lot_id;
    ELSE
        -- Lot partiellement soldé
        UPDATE lots 
        SET 
            quantite_actuelle = quantite_actuelle - p_quantite_solde,
            notes = CONCAT(COALESCE(notes, ''), ' | Solde partielle: ', p_quantite_solde, ' - ', COALESCE(p_notes, ''))
        WHERE id = p_lot_id;
    END IF;
    
    COMMIT;
    
    SELECT 
        'Lot marqué comme soldé avec succès' as message,
        p_lot_id as lot_id,
        p_quantite_solde as quantite_solde,
        v_quantite_actuelle - p_quantite_solde as quantite_restante;
        
END //

-- ============================================
-- Procédure : sp_rapport_expiration_hebdomadaire
-- Description : Génère un rapport des expirations pour la semaine
-- ============================================

CREATE PROCEDURE sp_rapport_expiration_hebdomadaire()
BEGIN
    -- Lots expirant cette semaine
    SELECT 
        'LOTS EXPIRANT CETTE SEMAINE' as section,
        DATE(date_expiration) as date_expiration,
        COUNT(*) as nombre_lots,
        SUM(quantite_actuelle) as quantite_totale,
        GROUP_CONCAT(
            CONCAT(produit_nom, ' (', quantite_actuelle, ' ', 
                   CASE 
                       WHEN quantite_actuelle = 1 THEN 'unité'
                       ELSE 'unités'
                   END, ')')
            ORDER BY date_expiration, produit_nom 
            SEPARATOR '; '
        ) as details_produits
    FROM v_alertes_actives
    WHERE jours_restants BETWEEN 0 AND 7
    GROUP BY DATE(date_expiration)
    ORDER BY date_expiration
    
    UNION ALL
    
    -- Résumé par catégorie
    SELECT 
        'RÉSUMÉ PAR CATÉGORIE' as section,
        categorie as date_expiration,
        COUNT(*) as nombre_lots,
        SUM(quantite_actuelle) as quantite_totale,
        CONCAT('Niveaux: ',
               SUM(CASE WHEN niveau_alerte = 'urgent' THEN 1 ELSE 0 END), ' urgent, ',
               SUM(CASE WHEN niveau_alerte = 'important' THEN 1 ELSE 0 END), ' important'
        ) as details_produits
    FROM v_alertes_actives
    WHERE jours_restants BETWEEN 0 AND 7
    GROUP BY categorie
    ORDER BY nombre_lots DESC;
    
END //

-- ============================================
-- Fonction : fn_get_couleur_alerte
-- Description : Retourne la couleur d'alerte configurée
-- ============================================

CREATE FUNCTION fn_get_couleur_alerte(jours_restants INT)
RETURNS VARCHAR(7)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE couleur VARCHAR(7) DEFAULT '#FFFFFF';
    
    IF jours_restants < 0 THEN
        SET couleur = '#8B0000'; -- Rouge foncé pour expirés
    ELSEIF jours_restants <= 1 THEN
        SELECT valeur INTO couleur FROM parametres WHERE nom_parametre = 'couleur_alerte_j1' AND actif = TRUE;
    ELSEIF jours_restants <= 7 THEN
        SELECT valeur INTO couleur FROM parametres WHERE nom_parametre = 'couleur_alerte_j7' AND actif = TRUE;
    ELSEIF jours_restants <= 30 THEN
        SELECT valeur INTO couleur FROM parametres WHERE nom_parametre = 'couleur_alerte_j30' AND actif = TRUE;
    ELSEIF jours_restants <= 60 THEN
        SELECT valeur INTO couleur FROM parametres WHERE nom_parametre = 'couleur_alerte_j60' AND actif = TRUE;
    END IF;
    
    RETURN COALESCE(couleur, '#FFFFFF');
END //

-- ============================================
-- Procédure : sp_maintenance_quotidienne
-- Description : Maintenance automatique quotidienne
-- ============================================

CREATE PROCEDURE sp_maintenance_quotidienne()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Mettre à jour les alertes
    CALL sp_update_alertes();
    
    -- Nettoyer les anciens lots soldés (plus de 1 an)
    UPDATE lots 
    SET notes = CONCAT(COALESCE(notes, ''), ' | Archivé automatiquement')
    WHERE statut = 'solde' 
      AND date_solde < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
    
    -- Marquer les produits inactifs si aucun lot actif depuis 6 mois
    UPDATE produits p
    SET actif = FALSE
    WHERE p.actif = TRUE
      AND NOT EXISTS (
          SELECT 1 FROM lots l 
          WHERE l.produit_id = p.id 
            AND l.statut = 'actif'
            AND l.date_reception > DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
      );
    
    COMMIT;
    
    SELECT 
        'Maintenance quotidienne terminée' as message,
        NOW() as timestamp_execution;
        
END //

DELIMITER ;

-- ============================================
-- Événement planifié pour maintenance automatique
-- ============================================

-- Activer l'ordonnanceur d'événements
SET GLOBAL event_scheduler = ON;

-- Créer l'événement de maintenance quotidienne
CREATE EVENT IF NOT EXISTS ev_maintenance_quotidienne
ON SCHEDULE EVERY 1 DAY
STARTS '2025-10-12 02:00:00'
COMMENT 'Maintenance quotidienne automatique'
DO
    CALL sp_maintenance_quotidienne();

-- ============================================
-- Tests des procédures
-- ============================================

-- Test de mise à jour des alertes
CALL sp_update_alertes();

-- Test de récupération des alertes urgentes
CALL sp_get_alertes_by_level('urgent');

-- Test de la fonction couleur
SELECT 
    1 as jours_restants, 
    fn_get_couleur_alerte(1) as couleur_1j,
    fn_get_couleur_alerte(7) as couleur_7j,
    fn_get_couleur_alerte(30) as couleur_30j;

SELECT 'Procédures stockées créées avec succès!' AS message;