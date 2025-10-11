-- ============================================
-- Données de test pour l'application Expiration
-- Application : Gestion des dates d'expiration
-- Version : 1.0
-- Date : 11 octobre 2025
-- ============================================

USE expiration_db;

-- ============================================
-- Insertion des produits de test
-- ============================================

INSERT INTO produits (nom, code_barre, categorie, description, marque, unite_mesure) VALUES
-- Produits laitiers
('Lait demi-écrémé 1L', '3256223456789', 'Laitages', 'Lait UHT demi-écrémé longue conservation', 'Lactel', 'L'),
('Yaourt nature x8', '3456789123456', 'Laitages', 'Yaourts nature sans arôme artificiel', 'Danone', 'pièce'),
('Fromage blanc 500g', '7891234567890', 'Laitages', 'Fromage blanc 20% MG', 'Yoplait', 'g'),
('Beurre doux 250g', '1234567890123', 'Laitages', 'Beurre de baratte doux AOP', 'Président', 'g'),
('Crème fraîche 30cl', '9876543210987', 'Laitages', 'Crème fraîche épaisse 30% MG', 'Elle & Vire', 'mL'),

-- Fruits et légumes
('Pommes Golden', NULL, 'Fruits', 'Pommes Golden de France', 'Vergers de France', 'kg'),
('Bananes', NULL, 'Fruits', 'Bananes des Antilles françaises', 'Cavendish', 'kg'),
('Oranges', NULL, 'Fruits', 'Oranges de Valencia', 'Citrus d\'Or', 'kg'),
('Carottes', NULL, 'Légumes', 'Carottes de pleine terre', 'Terre de Soleil', 'kg'),
('Tomates grappes', NULL, 'Légumes', 'Tomates grappes de serre', 'Savéol', 'kg'),
('Salade verte', NULL, 'Légumes', 'Salade batavia française', 'Maraîchers Unis', 'pièce'),

-- Viandes et poissons
('Escalope de poulet', '2345678901234', 'Viandes', 'Escalope de poulet fermier', 'Loué', 'kg'),
('Steaks hachés x4', '3456789012345', 'Viandes', 'Steaks hachés 15% MG', 'Charal', 'pièce'),
('Saumon fumé 150g', '4567890123456', 'Poissons', 'Saumon fumé d\'Écosse', 'Labeyrie', 'g'),
('Crevettes cuites 200g', '5678901234567', 'Poissons', 'Crevettes cuites décortiquées', 'Océan Délices', 'g'),

-- Pain et pâtisserie
('Pain de mie complet', '6789012345678', 'Boulangerie', 'Pain de mie aux céréales complètes', 'Harry\'s', 'pièce'),
('Croissants x6', '7890123456789', 'Boulangerie', 'Croissants au beurre artisanaux', 'Boulangerie Martin', 'pièce'),
('Baguette tradition', NULL, 'Boulangerie', 'Baguette de tradition française', 'Boulangerie Martin', 'pièce'),

-- Épicerie
('Riz basmati 1kg', '8901234567890', 'Épicerie', 'Riz basmati long grain', 'Taureau Ailé', 'kg'),
('Pâtes spaghetti 500g', '9012345678901', 'Épicerie', 'Spaghetti n°5 blé dur', 'Barilla', 'g'),
('Huile d\'olive 75cl', '1098765432109', 'Épicerie', 'Huile d\'olive extra vierge', 'Puget', 'mL'),
('Conserve tomates 400g', '2109876543210', 'Épicerie', 'Tomates pelées entières', 'Mutti', 'g'),

-- Surgelés
('Pizza margherita', '3210987654321', 'Surgelés', 'Pizza margherita pâte fine', 'Buitoni', 'pièce'),
('Légumes pour wok', '4321098765432', 'Surgelés', 'Mélange de légumes asiatiques', 'Picard', 'g'),
('Glace vanille 1L', '5432109876543', 'Surgelés', 'Glace à la vanille de Madagascar', 'Häagen-Dazs', 'L');

-- ============================================
-- Insertion des lots de test
-- ============================================

-- Lots avec différents niveaux d'alerte
INSERT INTO lots (produit_id, numero_lot, date_expiration, date_reception, quantite_initiale, quantite_actuelle, prix_achat, fournisseur, statut, notes) VALUES

-- Alertes urgentes (1 jour)
(1, 'LAI241011001', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 24.000, 18.000, 0.89, 'Lactalis France', 'actif', 'À vendre rapidement'),
(15, 'VIA241011001', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 2.500, 1.200, 8.50, 'Boucherie Centrale', 'actif', 'Promotion urgente nécessaire'),

-- Alertes importantes (7 jours)
(2, 'DAN241005001', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 48.000, 32.000, 0.45, 'Danone France', 'actif', 'Vente en cours'),
(3, 'YOP241006001', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), 12.000, 8.500, 1.20, 'Yoplait Distribution', 'actif', NULL),
(16, 'BAG241010001', DATE_ADD(CURDATE(), INTERVAL 6 DAY), CURDATE(), 25.000, 15.000, 1.15, 'Boulangerie Martin', 'actif', 'Pain frais du jour'),

-- Alertes moyennes (30 jours)
(4, 'PRE241001001', DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 20.000, 16.000, 2.80, 'Lactalis France', 'actif', 'Beurre de qualité'),
(5, 'ELL241002001', DATE_ADD(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 36.000, 28.000, 1.75, 'Elle & Vire', 'actif', NULL),
(18, 'TAU240930001', DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 10.000, 7.500, 2.95, 'Epicerie Centrale', 'actif', 'Stock normal'),

-- Alertes faibles (60 jours)
(19, 'BAR241001001', DATE_ADD(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 50.000, 42.000, 1.25, 'Epicerie Centrale', 'actif', NULL),
(20, 'PUG240925001', DATE_ADD(CURDATE(), INTERVAL 55 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 12.000, 10.200, 4.50, 'Epicerie Centrale', 'actif', 'Huile de qualité'),

-- Produits OK (plus de 60 jours)
(21, 'MUT240920001', DATE_ADD(CURDATE(), INTERVAL 180 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY), 48.000, 35.000, 0.95, 'Epicerie Centrale', 'actif', 'Conserves longue durée'),
(17, 'HAR241008001', DATE_ADD(CURDATE(), INTERVAL 120 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 30.000, 25.000, 2.10, 'Boulangerie Industrielle', 'actif', NULL),

-- Lots soldés (pour historique)
(6, 'FRA240928001', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), 15.000, 0.000, 1.80, 'Vergers de France', 'solde', 'Soldé avant expiration'),
(7, 'CAV240925001', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 20.000, 0.000, 2.20, 'Import Fruits', 'solde', 'Promotion réussie'),

-- Lots périmés (pour statistiques)
(8, 'CIT240920001', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 18 DAY), 12.000, 8.000, 2.50, 'Citrus d\'Or', 'perime', 'Périmé - à jeter'),

-- Lots futurs (réceptions récentes)
(9, 'TER241011001', DATE_ADD(CURDATE(), INTERVAL 90 DAY), CURDATE(), 25.000, 25.000, 1.95, 'Terre de Soleil', 'actif', 'Livraison du jour'),
(10, 'SAV241011001', DATE_ADD(CURDATE(), INTERVAL 8 DAY), CURDATE(), 15.000, 15.000, 3.20, 'Savéol', 'actif', 'Tomates fraîches'),
(11, 'MAR241011001', DATE_ADD(CURDATE(), INTERVAL 3 DAY), CURDATE(), 30.000, 30.000, 0.85, 'Maraîchers Unis', 'actif', 'Salade du jour'),

-- Lots avec différents fournisseurs
(12, 'LOU241009001', DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 8.000, 6.000, 12.50, 'Volailles de Loué', 'actif', 'Poulet fermier'),
(13, 'CHA241008001', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 16.000, 12.000, 4.20, 'Charal Distribution', 'actif', 'Viande fraîche'),
(14, 'LAB241007001', DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY), 6.000, 4.500, 15.80, 'Labeyrie France', 'actif', 'Saumon premium');

-- ============================================
-- Mise à jour des flags d'alerte selon les dates
-- ============================================

-- Mise à jour automatique des alertes selon les jours restants
UPDATE lots 
SET 
    alerte_j1 = CASE WHEN DATEDIFF(date_expiration, CURDATE()) <= 1 AND DATEDIFF(date_expiration, CURDATE()) >= 0 THEN TRUE ELSE FALSE END,
    alerte_j7 = CASE WHEN DATEDIFF(date_expiration, CURDATE()) <= 7 AND DATEDIFF(date_expiration, CURDATE()) >= 0 THEN TRUE ELSE FALSE END,
    alerte_j30 = CASE WHEN DATEDIFF(date_expiration, CURDATE()) <= 30 AND DATEDIFF(date_expiration, CURDATE()) >= 0 THEN TRUE ELSE FALSE END,
    alerte_j60 = CASE WHEN DATEDIFF(date_expiration, CURDATE()) <= 60 AND DATEDIFF(date_expiration, CURDATE()) >= 0 THEN TRUE ELSE FALSE END
WHERE statut = 'actif';

-- Mise à jour des dates de soldé pour les lots soldés
UPDATE lots 
SET date_solde = DATE_ADD(date_reception, INTERVAL FLOOR(RAND() * 10) + 1 DAY)
WHERE statut = 'solde' AND date_solde IS NULL;

-- ============================================
-- Vérification des données insérées
-- ============================================

-- Compter les enregistrements
SELECT 'Produits insérés' as table_name, COUNT(*) as nombre FROM produits
UNION ALL
SELECT 'Lots insérés' as table_name, COUNT(*) as nombre FROM lots
UNION ALL
SELECT 'Paramètres configurés' as table_name, COUNT(*) as nombre FROM parametres;

-- Afficher les alertes par niveau
SELECT 
    niveau_alerte,
    COUNT(*) as nombre_lots,
    GROUP_CONCAT(DISTINCT produit_nom ORDER BY produit_nom SEPARATOR ', ') as produits
FROM v_alertes_actives 
GROUP BY niveau_alerte 
ORDER BY 
    CASE niveau_alerte
        WHEN 'expire' THEN 1
        WHEN 'urgent' THEN 2
        WHEN 'important' THEN 3
        WHEN 'moyen' THEN 4
        WHEN 'faible' THEN 5
        ELSE 6
    END;

-- Afficher un aperçu du dashboard
SELECT * FROM v_dashboard_metriques;

SELECT 'Données de test insérées avec succès!' AS message;