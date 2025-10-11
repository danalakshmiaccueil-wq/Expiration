# 📊 Diagramme Entity-Relationship (ERD) - Application Expiration

## Vue d'ensemble des relations

```
┌─────────────────────────────┐     ┌─────────────────────────────┐
│          PRODUITS           │ 1:N │            LOTS             │
├─────────────────────────────┤────→├─────────────────────────────┤
│ 🔑 id (PK)                  │     │ 🔑 id (PK)                  │
│ 📝 nom                      │     │ 🔗 produit_id (FK)          │
│ 📱 code_barre (UNIQUE)      │     │ 📝 numero_lot               │
│ 🏷️  categorie               │     │ 📅 date_expiration          │
│ 📄 description              │     │ 📅 date_reception           │
│ 🏢 marque                   │     │ 📊 quantite_initiale        │
│ ⚖️  unite_mesure             │     │ 📊 quantite_actuelle        │
│ ✅ actif                     │     │ 💰 prix_achat               │
│ 📅 created_at               │     │ 🏢 fournisseur              │
│ 📅 updated_at               │     │ 🏷️  statut                  │
└─────────────────────────────┘     │ 📝 notes                    │
                                    │ 🚨 alerte_j1                │
┌─────────────────────────────┐     │ 🚨 alerte_j7                │
│         PARAMETRES          │     │ 🚨 alerte_j30               │
├─────────────────────────────┤     │ 🚨 alerte_j60               │
│ 🔑 id (PK)                  │     │ 📅 date_solde               │
│ 📝 nom_parametre (UNIQUE)   │     │ 📅 created_at               │
│ 📄 valeur                   │     │ 📅 updated_at               │
│ 🔧 type                     │     └─────────────────────────────┘
│ 📄 description              │
│ ✅ actif                     │
│ 📅 updated_at               │
└─────────────────────────────┘
```

## 🔗 Détail des relations

### Relation PRODUITS ↔ LOTS
- **Type** : One-to-Many (1:N)
- **Cardinalité** : Un produit peut avoir plusieurs lots
- **Clé étrangère** : `lots.produit_id` → `produits.id`
- **Contrainte** : CASCADE DELETE (suppression d'un produit supprime ses lots)

### Configuration des alertes
- Les paramètres globaux d'alertes sont stockés dans la table `PARAMETRES`
- Chaque lot a ses propres flags d'alertes (alerte_j1, alerte_j7, etc.)
- Le système calcule les alertes en croisant les paramètres globaux et les dates

## 🎯 Flux de données

### 1. Création d'un produit
```
PRODUITS ← Nouveau produit ajouté
```

### 2. Réception d'un lot
```
PRODUITS → Sélection du produit existant
    ↓
LOTS ← Création du nouveau lot avec date d'expiration
```

### 3. Calcul des alertes
```
PARAMETRES → Configuration des seuils d'alerte
    ↓
LOTS → Calcul des jours restants jusqu'à expiration
    ↓
ALERTES → Génération des alertes selon les seuils
```

### 4. Marquage "Soldé"
```
LOTS → Changement de statut vers "solde"
    ↓
LOTS.date_solde ← Timestamp de la modification
```

## 📋 Règles métier

### Contraintes d'intégrité
1. **Quantités** : `quantite_actuelle` ≤ `quantite_initiale`
2. **Dates** : `date_expiration` ≥ `date_reception`
3. **Statuts** : Transitions valides entre statuts
4. **Unicité** : Code-barres unique par produit (si fourni)

### Règles de calcul d'alertes
```
Jours restants = date_expiration - date_actuelle

SI jours_restants ≤ 1 ALORS niveau = "urgent" (rouge)
SINON SI jours_restants ≤ 7 ALORS niveau = "important" (orange)
SINON SI jours_restants ≤ 30 ALORS niveau = "moyen" (jaune)
SINON SI jours_restants ≤ 60 ALORS niveau = "faible" (vert)
SINON niveau = "ok" (pas d'alerte)
```

### Gestion des statuts de lots
- **actif** : Lot en cours d'utilisation
- **solde** : Lot retiré de l'inventaire (soldé)
- **perime** : Lot dépassé (date d'expiration passée)
- **retire** : Lot retiré pour autre raison (défaut, rappel, etc.)

## 🔧 Index stratégiques

### Index de performance
```sql
-- Pour calculs d'alertes rapides
INDEX idx_lots_expiration_statut ON lots(date_expiration, statut);

-- Pour jointures produits-lots
INDEX idx_lots_produit ON lots(produit_id);

-- Pour recherche par catégorie
INDEX idx_produits_categorie ON produits(categorie);

-- Pour recherche textuelle
INDEX idx_produits_nom ON produits(nom);
```

### Index de recherche utilisateur
```sql
-- Recherche par code-barres
UNIQUE INDEX idx_produits_code_barre ON produits(code_barre);

-- Recherche par numéro de lot
INDEX idx_lots_numero ON lots(numero_lot);

-- Filtrage par fournisseur
INDEX idx_lots_fournisseur ON lots(fournisseur);
```

## 📊 Vues calculées

### Vue des alertes actives
Combine les tables pour afficher les lots nécessitant une attention

### Vue des statistiques
Agrège les données pour le tableau de bord

### Vue d'historique
Suit l'évolution des lots dans le temps