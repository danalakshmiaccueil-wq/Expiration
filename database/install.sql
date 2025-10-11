-- ============================================
-- Script d'installation complète
-- Application : Gestion des dates d'expiration
-- Version : 1.0
-- Date : 11 octobre 2025
-- ============================================

-- ATTENTION: Ce script va créer une nouvelle base de données
-- Assurez-vous d'avoir les privilèges nécessaires

-- ============================================
-- Étape 1: Création de la base de données et des tables
-- ============================================

SOURCE schema/create_tables.sql;

-- ============================================
-- Étape 2: Création des vues
-- ============================================

SOURCE schema/create_views.sql;

-- ============================================
-- Étape 3: Création des procédures stockées
-- ============================================

SOURCE procedures/stored_procedures.sql;

-- ============================================
-- Étape 4: Insertion des données de test (optionnel)
-- ============================================

-- Décommentez la ligne suivante pour insérer des données de test
-- SOURCE seeders/sample_data.sql;

-- ============================================
-- Vérification de l'installation
-- ============================================

USE expiration_db;

-- Vérifier les tables
SELECT 
    'Tables créées' as verification,
    COUNT(*) as nombre
FROM information_schema.tables 
WHERE table_schema = 'expiration_db' 
  AND table_type = 'BASE TABLE';

-- Vérifier les vues
SELECT 
    'Vues créées' as verification,
    COUNT(*) as nombre
FROM information_schema.tables 
WHERE table_schema = 'expiration_db' 
  AND table_type = 'VIEW';

-- Vérifier les procédures
SELECT 
    'Procédures créées' as verification,
    COUNT(*) as nombre
FROM information_schema.routines 
WHERE routine_schema = 'expiration_db' 
  AND routine_type = 'PROCEDURE';

-- Vérifier les fonctions
SELECT 
    'Fonctions créées' as verification,
    COUNT(*) as nombre
FROM information_schema.routines 
WHERE routine_schema = 'expiration_db' 
  AND routine_type = 'FUNCTION';

-- Vérifier les paramètres par défaut
SELECT 
    'Paramètres configurés' as verification,
    COUNT(*) as nombre
FROM parametres;

-- ============================================
-- Messages de fin d'installation
-- ============================================

SELECT 
    '✅ INSTALLATION TERMINÉE AVEC SUCCÈS!' as status,
    'Base de données expiration_db prête à utiliser' as message,
    NOW() as timestamp_installation;

-- Instructions pour la suite
SELECT 
    'PROCHAINES ÉTAPES:' as titre,
    '1. Configurer les paramètres dans la table "parametres"' as etape_1,
    '2. Commencer à ajouter vos produits' as etape_2,
    '3. Enregistrer vos premiers lots' as etape_3,
    '4. Tester le système d\'alertes' as etape_4;

-- Afficher les requêtes utiles
SELECT 
    'REQUÊTES UTILES:' as titre,
    'SELECT * FROM v_alertes_actives;' as voir_alertes,
    'SELECT * FROM v_dashboard_metriques;' as voir_dashboard,
    'CALL sp_update_alertes();' as mettre_a_jour_alertes,
    'CALL sp_get_alertes_by_level("urgent");' as voir_alertes_urgentes;