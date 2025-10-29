# API REST - Gestion des Dates d'Expiration

## 📋 Vue d'ensemble

Cette API REST permet de gérer un système complet de suivi des dates d'expiration pour les produits alimentaires. Elle est optimisée pour un déploiement sur hébergement cPanel et offre un système d'alertes visuelles configurable.

## 🚀 Installation et Déploiement

### Prérequis
- **PHP** : 7.4+ (recommandé : 8.0+)
- **MySQL** : 5.7+ ou MariaDB 10.3+
- **Hébergement** : Compatible cPanel/Shared hosting
- **Extensions PHP** : PDO, PDO_MySQL, JSON, MBString

### Déploiement sur cPanel

1. **Téléchargement des fichiers**
   ```bash
   # Via FileManager cPanel ou FTP
   # Télécharger tous les fichiers dans: public_html/api/
   ```

2. **Configuration de la base de données**
   ```bash
   # 1. Créer une base de données MySQL via cPanel
   # 2. Importer le schéma depuis database/
   # 3. Configurer config/database.php avec vos paramètres
   ```

3. **Permissions des fichiers**
   ```bash
   # Dossiers: 755
   # Fichiers PHP: 644
   # Fichiers config: 600 (recommandé)
   chmod 755 logs/ cache/ classes/ endpoints/ utils/
   chmod 644 *.php
   chmod 600 config/database.php
   ```

4. **Test de l'installation**
   ```
   https://votre-domaine.com/api/test
   ```

## 🏗️ Architecture

```
backend/api/
├── config/           # Configuration
│   ├── config.php    # Configuration générale
│   └── database.php  # Connexion base de données
├── classes/          # Classes métier
│   ├── Database.php  # Singleton de connexion
│   ├── Produit.php   # Gestion produits
│   └── Lot.php       # Gestion lots
├── endpoints/        # Points d'entrée API
│   ├── produits.php  # API produits
│   ├── lots.php      # API lots
│   ├── dashboard.php # API statistiques
│   ├── parametres.php# API paramètres
│   └── alertes.php   # API alertes
├── utils/            # Utilitaires
│   └── helpers.php   # Fonctions helper
├── logs/             # Logs d'application
├── cache/            # Cache temporaire
├── .htaccess         # Configuration Apache
├── index.php         # Page d'accueil API
└── test.php          # Interface de test
```

## 📡 Endpoints API

### Base URL
```
https://votre-domaine.com/api/
```

### 1. Produits (`/api/produits`)

#### GET - Liste des produits
```http
GET /api/produits
GET /api/produits?page=1&limit=20
GET /api/produits?search=nom_produit
GET /api/produits?categorie=Laitiers
```

**Réponse:**
```json
{
  "data": [
    {
      "id": 1,
      "nom": "Lait UHT",
      "categorie": "Laitiers",
      "description": "Lait entier longue conservation",
      "unite_mesure": "L",
      "date_creation": "2025-10-11T10:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "total_pages": 3
  }
}
```

#### POST - Créer un produit
```http
POST /api/produits
Content-Type: application/json

{
  "nom": "Yaourt Nature",
  "categorie": "Laitiers",
  "description": "Yaourt nature bio",
  "unite_mesure": "pièce"
}
```

#### PUT - Modifier un produit
```http
PUT /api/produits/1
Content-Type: application/json

{
  "nom": "Nouveau nom",
  "description": "Nouvelle description"
}
```

#### DELETE - Supprimer un produit
```http
DELETE /api/produits/1
```

### 2. Lots (`/api/lots`)

#### GET - Liste des lots
```http
GET /api/lots
GET /api/lots?produit_id=1
GET /api/lots?alerte=urgent
GET /api/lots?expire_avant=2025-12-01
```

**Réponse:**
```json
{
  "data": [
    {
      "id": 1,
      "produit_id": 1,
      "numero_lot": "LOT2025001",
      "date_reception": "2025-10-01",
      "date_expiration": "2025-11-01",
      "quantite_initiale": 100,
      "quantite_actuelle": 85,
      "prix_achat": 25.50,
      "fournisseur": "Lactalis",
      "alerte": {
        "niveau": "moyen",
        "jours_restants": 21,
        "couleur": "#FFA500"
      }
    }
  ]
}
```

#### POST - Créer un lot
```http
POST /api/lots
Content-Type: application/json

{
  "produit_id": 1,
  "numero_lot": "LOT2025002",
  "date_reception": "2025-10-11",
  "date_expiration": "2025-12-11",
  "quantite_initiale": 50,
  "quantite_actuelle": 50,
  "prix_achat": 30.00,
  "fournisseur": "Danone"
}
```

#### PUT - Actions sur un lot
```http
# Marquer comme soldé
PUT /api/lots/1
Content-Type: application/json

{
  "action": "marquer_solde",
  "raison": "Promotion écoulée"
}

# Ajuster la quantité
PUT /api/lots/1
Content-Type: application/json

{
  "quantite_actuelle": 75
}
```

### 3. Dashboard (`/api/dashboard`)

#### GET - Statistiques générales
```http
GET /api/dashboard?action=stats
```

**Réponse:**
```json
{
  "stats_generales": {
    "total_produits": 125,
    "total_lots_actifs": 450,
    "valeur_stock_totale": 15750.80,
    "alertes_actives": 23
  },
  "alertes_par_niveau": {
    "urgent": 3,
    "important": 8,
    "moyen": 12,
    "faible": 0
  },
  "top_categories": [
    {"categorie": "Laitiers", "nombre_lots": 85},
    {"categorie": "Conserves", "nombre_lots": 65}
  ]
}
```

#### GET - Résumé des alertes
```http
GET /api/dashboard?action=alertes
```

#### GET - Tendances
```http
GET /api/dashboard?action=tendances&periode=30
```

### 4. Paramètres (`/api/parametres`)

#### GET - Configuration système
```http
GET /api/parametres
```

**Réponse:**
```json
{
  "parametres": [
    {
      "id": 1,
      "nom": "alerte_urgent",
      "valeur": "1",
      "type": "int",
      "description": "Seuil d'alerte urgente (jours)"
    }
  ],
  "categorised": {
    "alertes": [...],
    "couleurs": [...],
    "general": [...]
  }
}
```

#### POST/PUT - Modifier paramètres
```http
POST /api/parametres
Content-Type: application/json

{
  "nom": "nouveau_parametre",
  "valeur": "valeur",
  "type": "string",
  "description": "Description"
}
```

### 5. Alertes (`/api/alertes`)

#### GET - Système d'alertes
```http
GET /api/alertes?action=summary
GET /api/alertes?action=urgentes
GET /api/alertes?action=dashboard
GET /api/alertes?action=par_produit
```

**Réponse Summary:**
```json
{
  "niveaux": [
    {
      "niveau_alerte": "urgent",
      "nombre_alertes": 3,
      "quantite_totale": 25,
      "jours_moyens": -2
    }
  ],
  "categories": [...],
  "tendance_7_jours": [...],
  "totaux": {
    "total_alertes": 23,
    "produits_expires": 5,
    "alertes_urgentes": 3
  }
}
```

## 🔧 Configuration

### Variables d'environnement (config/config.php)

```php
// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'expiration_db');
define('DB_USER', 'user');
define('DB_PASS', 'password');

// Application
define('APP_NAME', 'Gestion Expiration API');
define('APP_VERSION', '1.0.0');
define('ENVIRONMENT', 'production'); // development|production

// Seuils d'alerte (jours)
define('SEUIL_ALERTE_URGENT', 1);
define('SEUIL_ALERTE_IMPORTANT', 7);
define('SEUIL_ALERTE_MOYEN', 30);
define('SEUIL_ALERTE_FAIBLE', 60);

// Couleurs d'alerte
define('DEFAULT_ALERT_COLORS', [
    'urgent' => '#FF0000',      // Rouge
    'important' => '#FF8C00',   // Orange foncé
    'moyen' => '#FFA500',       // Orange
    'faible' => '#FFD700',      // Jaune
    'expire' => '#8B0000'       // Rouge foncé
]);

// Cache et logs
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes
define('LOG_LEVEL', 'INFO');
```

## 🎨 Système d'Alertes

### Niveaux d'alerte configurables

1. **Urgent** (défaut: ≤ 1 jour) - Rouge `#FF0000`
2. **Important** (défaut: ≤ 7 jours) - Orange foncé `#FF8C00`
3. **Moyen** (défaut: ≤ 30 jours) - Orange `#FFA500`
4. **Faible** (défaut: ≤ 60 jours) - Jaune `#FFD700`
5. **Expiré** (< 0 jours) - Rouge foncé `#8B0000`

### Calcul automatique des alertes

Les alertes sont calculées automatiquement via des vues MySQL et des procédures stockées. Le système met à jour les niveaux d'alerte en temps réel selon les paramètres configurables.

## 🔒 Sécurité

### Mesures implémentées

- **Validation d'entrée** : Tous les inputs sont validés et nettoyés
- **Protection SQL Injection** : Utilisation exclusive de requêtes préparées
- **Headers sécurisés** : X-Frame-Options, X-XSS-Protection, etc.
- **CORS configuré** : Contrôle d'accès cross-origin
- **Logs de sécurité** : Traçabilité des actions utilisateur
- **Protection fichiers** : .htaccess bloque l'accès aux fichiers sensibles

### Configuration .htaccess

Le fichier `.htaccess` inclus configure :
- Routage d'URL propre
- Headers de sécurité
- Compression GZIP
- Protection des fichiers sensibles
- Cache des réponses API

## 🧪 Tests et Debugging

### Interface de test intégrée

Accédez à `/api/test` pour une interface de test complète avec :
- Tests de santé de l'API
- Tests de tous les endpoints
- Tests de performance
- Création de données de test
- Visualisation des réponses JSON

### Health Check

```http
GET /api/health
```

Retourne l'état de santé de l'API (base de données, cache, logs, configuration).

### Logs

Les logs sont automatiquement générés dans `/logs/` :
- `error_YYYY-MM-DD.log` : Erreurs système
- `user_actions_YYYY-MM-DD.log` : Actions utilisateur
- `debug_YYYY-MM-DD.log` : Debug (mode development uniquement)

## 📊 Performance et Cache

### Stratégie de cache

- **Cache activé** en production pour les requêtes coûteuses
- **Durée par défaut** : 5 minutes
- **Invalidation** automatique lors des modifications
- **Stockage** : Fichiers dans `/cache/`

### Optimisations

- **Pagination** automatique des grandes listes
- **Indexes** optimisés sur la base de données
- **Compression GZIP** via .htaccess
- **Requêtes optimisées** avec les vues MySQL

## 🚨 Codes d'erreur

| Code | Description |
|------|-------------|
| 200  | Succès |
| 201  | Créé avec succès |
| 400  | Données invalides |
| 401  | Non autorisé |
| 403  | Accès interdit |
| 404  | Ressource non trouvée |
| 409  | Conflit (doublon) |
| 422  | Erreur de validation |
| 500  | Erreur serveur |
| 503  | Service indisponible |

## 🔄 Versions et Changelog

### Version 1.0.0 (2025-10-11)
- ✅ API REST complète
- ✅ Système d'alertes configurables  
- ✅ Dashboard et statistiques
- ✅ Optimisation cPanel
- ✅ Interface de test
- ✅ Documentation complète

## 📞 Support

Pour le support technique ou les questions :
1. Vérifiez les logs dans `/logs/`
2. Utilisez l'interface de test `/api/test`
3. Consultez la documentation de l'API
4. Contactez l'équipe de développement

---

**Développé pour Danalakshmi Store** - Système de gestion des dates d'expiration v1.0