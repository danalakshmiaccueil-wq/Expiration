-- ============================================
-- Script pour ajouter la colonne date_creation
-- Date : 18 octobre 2025
-- ============================================

USE sc3bera6697_danalakshmi_expiration;

-- Vérifier si la colonne existe déjà
SELECT 
    COUNT(*) as column_exists
FROM information_schema.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'sc3bera6697_danalakshmi_expiration'
    AND TABLE_NAME = 'lots'
    AND COLUMN_NAME = 'date_creation';

-- Ajouter la colonne date_creation si elle n'existe pas
-- (Exécuter cette commande manuellement si la colonne n'existe pas)
ALTER TABLE lots 
ADD COLUMN IF NOT EXISTS date_creation DATE DEFAULT CURRENT_DATE
AFTER statut;

-- Mettre à jour les lignes existantes qui n'ont pas de date_creation
UPDATE lots 
SET date_creation = COALESCE(date_creation, DATE(created_at), CURDATE())
WHERE date_creation IS NULL;

-- Vérification
SELECT 
    'Colonne date_creation ajoutée avec succès!' as message,
    COUNT(*) as total_lots,
    COUNT(date_creation) as lots_avec_date
FROM lots;

-- Afficher quelques exemples
SELECT 
    id,
    numero_lot,
    date_creation,
    DATE(created_at) as created_at_date,
    date_expiration
FROM lots
ORDER BY id DESC
LIMIT 10;
