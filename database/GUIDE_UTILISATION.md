# 🗄️ Guide d'utilisation de la base de données

## 🚀 Installation rapide

### Prérequis
- MySQL 8.0 ou MariaDB 10.5+
- Privilèges de création de base de données
- Connexion à votre serveur MySQL

### Installation automatique
```bash
# Se connecter à MySQL
mysql -u root -p

# Exécuter le script d'installation
source /chemin/vers/database/install.sql
```

### Installation manuelle étape par étape
```sql
-- 1. Créer les tables
SOURCE schema/create_tables.sql;

-- 2. Créer les vues
SOURCE schema/create_views.sql;

-- 3. Créer les procédures
SOURCE procedures/stored_procedures.sql;

-- 4. Insérer des données de test (optionnel)
SOURCE seeders/sample_data.sql;
```

---

## 📊 Utilisation courante

### Afficher les alertes actuelles
```sql
-- Toutes les alertes actives
SELECT * FROM v_alertes_actives 
ORDER BY jours_restants ASC;

-- Alertes urgentes uniquement
CALL sp_get_alertes_by_level('urgent');

-- Alertes par niveau
CALL sp_get_alertes_by_level('important');
```

### Consulter le dashboard
```sql
-- Métriques globales
SELECT * FROM v_dashboard_metriques;

-- Statistiques par produit
SELECT * FROM v_statistiques_produits
WHERE lots_actifs > 0
ORDER BY alertes_urgentes DESC;
```

### Gestion des produits
```sql
-- Ajouter un nouveau produit
INSERT INTO produits (nom, code_barre, categorie, marque, unite_mesure) 
VALUES ('Yaourt fraise', '1234567890123', 'Laitages', 'Danone', 'pièce');

-- Rechercher un produit
SELECT * FROM produits 
WHERE nom LIKE '%yaourt%' 
   OR code_barre = '1234567890123';
```

### Gestion des lots
```sql
-- Ajouter un nouveau lot
INSERT INTO lots (produit_id, numero_lot, date_expiration, date_reception, 
                  quantite_initiale, quantite_actuelle, prix_achat, fournisseur) 
VALUES (1, 'LOT2025001', '2025-10-20', '2025-10-11', 24, 24, 0.89, 'Lactalis');

-- Marquer un lot comme soldé
CALL sp_marquer_lot_solde(1, 10.5, 'Promotion fin de journée');

-- Voir l'historique des lots
SELECT * FROM v_historique_lots 
WHERE produit_nom LIKE '%lait%'
ORDER BY derniere_modification DESC;
```

---

## ⚙️ Configuration des paramètres

### Modifier les seuils d'alerte
```sql
-- Désactiver les alertes à 60 jours
UPDATE parametres 
SET valeur = 'false' 
WHERE nom_parametre = 'alerte_j60_active';

-- Changer la couleur des alertes urgentes
UPDATE parametres 
SET valeur = '#DC143C' 
WHERE nom_parametre = 'couleur_alerte_j1';
```

### Paramètres disponibles
- `alerte_j1_active` : Activer/désactiver alertes 1 jour
- `alerte_j7_active` : Activer/désactiver alertes 7 jours  
- `alerte_j30_active` : Activer/désactiver alertes 30 jours
- `alerte_j60_active` : Activer/désactiver alertes 60 jours
- `couleur_alerte_*` : Couleurs des différents niveaux
- `notification_email` : Activer notifications email
- `nom_magasin` : Nom de votre magasin

---

## 🔄 Maintenance automatique

### Mise à jour manuelle des alertes
```sql
-- Recalculer toutes les alertes
CALL sp_update_alertes();

-- Maintenance complète
CALL sp_maintenance_quotidienne();
```

### Rapports périodiques
```sql
-- Rapport hebdomadaire des expirations
CALL sp_rapport_expiration_hebdomadaire();

-- Statistiques par fournisseur
SELECT * FROM v_fournisseurs_stats
ORDER BY alertes_actuelles DESC;
```

---

## 📈 Requêtes avancées

### Produits les plus problématiques
```sql
SELECT 
    p.nom,
    COUNT(l.id) as nombre_lots_expires,
    SUM(l.quantite_initiale) as quantite_totale_perdue
FROM produits p
JOIN lots l ON p.id = l.produit_id
WHERE l.statut = 'perime'
  AND l.date_expiration >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY p.id, p.nom
ORDER BY nombre_lots_expires DESC
LIMIT 10;
```

### Analyse des pertes par catégorie
```sql
SELECT 
    p.categorie,
    COUNT(l.id) as lots_perimes,
    SUM(l.quantite_initiale * COALESCE(l.prix_achat, 0)) as perte_financiere
FROM produits p
JOIN lots l ON p.id = l.produit_id
WHERE l.statut = 'perime'
  AND l.date_expiration >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY p.categorie
ORDER BY perte_financiere DESC;
```

### Rotation des stocks
```sql
SELECT 
    p.nom,
    AVG(DATEDIFF(l.date_solde, l.date_reception)) as duree_moyenne_jours,
    COUNT(CASE WHEN l.statut = 'solde' THEN 1 END) as lots_vendus,
    COUNT(CASE WHEN l.statut = 'perime' THEN 1 END) as lots_perimes
FROM produits p
JOIN lots l ON p.id = l.produit_id
WHERE l.date_reception >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
GROUP BY p.id, p.nom
HAVING lots_vendus > 0
ORDER BY duree_moyenne_jours ASC;
```

---

## 🛡️ Sauvegardes et restauration

### Sauvegarde complète
```bash
mysqldump -u root -p expiration_db > sauvegarde_expiration_$(date +%Y%m%d).sql
```

### Sauvegarde des données uniquement
```bash
mysqldump -u root -p --no-create-info expiration_db > donnees_expiration_$(date +%Y%m%d).sql
```

### Restauration
```bash
mysql -u root -p expiration_db < sauvegarde_expiration_20251011.sql
```

---

## 🔧 Dépannage

### Vérifier la configuration
```sql
-- Voir les paramètres actifs
SELECT * FROM parametres WHERE actif = TRUE;

-- Vérifier les index
SHOW INDEX FROM lots;

-- Statistiques des tables
SELECT 
    table_name,
    table_rows,
    data_length,
    index_length
FROM information_schema.tables 
WHERE table_schema = 'expiration_db';
```

### Problèmes courants

1. **Alertes non mises à jour**
   ```sql
   CALL sp_update_alertes();
   ```

2. **Performance lente**
   ```sql
   ANALYZE TABLE produits, lots, parametres;
   ```

3. **Réinitialiser les paramètres**
   ```sql
   DELETE FROM parametres;
   SOURCE seeders/sample_data.sql;
   ```

---

## 📞 Support

Pour toute question technique :
- Vérifiez d'abord les logs MySQL
- Consultez la documentation dans `/docs/`
- Exécutez les requêtes de diagnostic ci-dessus

**Fichiers importants** :
- `SCHEMA.md` : Documentation détaillée du schéma
- `ERD.md` : Diagramme des relations  
- `install.sql` : Installation automatique