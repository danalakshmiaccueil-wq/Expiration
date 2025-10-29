# Structure du Projet - Danalakshmi Expiration Date

## 📋 Vue d'ensemble
Système de gestion des dates d'expiration pour les produits Danalakshmi Store.

**URL Production:** https://expire.danalakshmi.fr

---

## 📁 Structure des Fichiers

### Pages Principales (HTML)
```
├── index.html                  # Dashboard principal avec statistiques
├── add-date.html              # Formulaire d'ajout de dates d'expiration
├── produits-dates.html        # Liste des produits avec dates (avec filtres)
├── archives.html              # Historique des produits soldés
└── gestion-produits.html      # Gestion du catalogue produits
```

### API Backend
```
api/
├── lots.php                   # API CRUD pour les lots (date d'expiration)
├── produits.php              # API CRUD pour les produits
├── auth/                     # Système d'authentification
│   ├── login.php
│   ├── logout.php
│   ├── validate.php
│   ├── auth_utils.php
│   └── database.php
└── config/
    └── database.php          # Configuration base de données
```

### Assets
```
assets/
├── danalakshmi-theme.css     # Thème CSS global
└── logo-danalakshmi.svg      # Logo vectoriel
```

### Base de Données
```
database/
├── install.sql               # Script d'installation complète
├── SCHEMA.md                # Documentation du schéma
├── ERD.md                   # Diagramme entité-relation
├── GUIDE_UTILISATION.md     # Guide d'utilisation
├── procedures/              # Procédures stockées
│   ├── gestion_lots.sql
│   ├── gestion_produits.sql
│   └── statistiques.sql
├── schema/                  # Schémas de tables
│   ├── lots.sql
│   ├── produits.sql
│   └── utilisateurs.sql
└── seeders/                 # Données de test
    └── sample_data.sql
```

### Backend API (Organisé)
```
backend/api/
├── index.php                # Point d'entrée API
├── classes/                 # Classes métier
│   ├── Lot.php
│   └── Produit.php
├── config/
│   ├── config.php
│   └── database.php
├── endpoints/               # Endpoints API REST
│   ├── lots.php
│   ├── produits.php
│   ├── dashboard.php
│   ├── alertes.php
│   └── parametres.php
├── utils/
│   └── helpers.php
├── cache/                   # Cache API
└── logs/                    # Logs d'erreurs
```

### Documentation
```
docs/
├── AUTHENTICATION_ANALYSIS.md    # Analyse système auth
└── FLUTTER_MOBILE_ANALYSIS.md    # Analyse app mobile
```

### Configuration & Utilitaires
```
├── .htaccess                # Configuration Apache
├── .gitignore              # Fichiers ignorés par Git
├── database.php            # Connexion DB principale
├── health.php              # Endpoint santé système
├── helpers.php             # Fonctions utilitaires
├── index.php               # Redirection page principale
├── lots.php                # Legacy - à migrer vers API
├── produits.php            # Legacy - à migrer vers API
└── clear-all-lots.php      # Utilitaire admin
```

### Ressources
```
├── logo danalakshmi.png           # Logo principal (PNG)
├── import-articles-danalakshmi.sql # Import données initiales
├── Liste des articles 03 02 25.xlsx # Liste produits
└── Cahier_de_charge/              # Spécifications projet
```

---

## 🎨 Caractéristiques UI

### Header Moderne (Toutes les pages)
- ✅ Fond blanc avec bordure verte
- ✅ Logo Danalakshmi intégré
- ✅ Texte avec effet gradient vert
- ✅ Navigation avec hover effects
- ✅ Design responsive
- ✅ Ombre subtile pour profondeur

### Dates d'Expiration - Code Couleur
- 🟢 **Vert** : Plus de 90 jours restants (FRAIS)
- 🟠 **Orange** : 90 jours ou moins (EXPIRE BIENTÔT)
- 🔴 **Rouge** : 7 jours ou moins (ATTENTION)

### Fonctionnalités Affichage
- 📅 Date d'expiration en grand et colorée
- ➕ Date d'ajout (date_fabrication) affichée
- 📦 Type d'unité affiché (palettes/cartons/packs)
- 🏷️ "Lot" au lieu de "Emplacement"
- 📊 Quantité actuelle / Quantité initiale

---

## 🔧 Configuration Requise

### Serveur
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache avec mod_rewrite
- Extension PDO MySQL

### Base de Données
```
Nom: sc3bera6697_danalakshmi_expiration
Tables principales:
- produits (catalogue produits)
- lots (dates d'expiration et stocks)
- utilisateurs (authentification)
```

### Déploiement FTP
```
Serveur: ftp.sc3bera6697.universe.wf
Utilisateur: expire@expire.danalakshmi.fr
Répertoire: public_html/
```

---

## 📝 Fichiers Supprimés (Nettoyage)

### Backups & Versions Obsolètes
- ❌ add-date-backup.html, add-date-fixed.html, add-date-improved.html
- ❌ index-backup.html, index-nouveau.html, index-test.html
- ❌ temp-index.html

### Fichiers de Test
- ❌ test-api-lots.html, test-database.php, test-db-config.php
- ❌ test-produits.html, test-search-debug.html, test-simple.html
- ❌ test-ultra-simple.html, ultra-simple.html
- ❌ diagnostic-complet.html, debug-produits.html

### Versions API Obsolètes
- ❌ lots-current.php, lots-demo.php, lots-improved.php
- ❌ produits-current.php, produits-demo.php, produits-fixed.php
- ❌ api/produits-fixed.php

### Scripts Obsolètes
- ❌ deploy-*.sh (deploy-api-fix, deploy-assets, deploy-backend, etc.)
- ❌ fix-*.php (fix-api-final, fix-api-pagination, etc.)
- ❌ import-*.py, import-*.sh (scripts d'import obsolètes)
- ❌ diagnostic-*.sh, test-correction-api.sh

### Dossiers Supprimés
- ❌ frontend/ (projet séparé non utilisé)
- ❌ test-v2/ (tests obsolètes)
- ❌ backup-ftp/ (backups inutiles)

### Documentation Obsolète
- ❌ CHECKLIST_DEPLOY.md, DEPLOIEMENT_TERMINE.md
- ❌ GUIDE_CPANEL_SOUS_DOMAINE.md, GUIDE_DEPLOIEMENT_CPANEL.md
- ❌ CONFIGURATION_BDD_FINALE.md, DIAGNOSTIC_FINAL.md
- ❌ TACHES_TERMINEES.md, TESTS_SUMMARY.md

---

## 🚀 Quick Start

### Déploiement Local
```bash
# 1. Cloner le repository
git clone https://github.com/danalakshmiaccueil-wq/Expiration.git

# 2. Configurer la base de données
mysql -u root -p < database/install.sql

# 3. Configurer la connexion DB dans api/config/database.php

# 4. Lancer serveur local
php -S localhost:8000
```

### Déploiement Production (FTP)
```bash
# Upload via LFTP
lftp -c "
set ftp:ssl-allow no
open -u expire@expire.danalakshmi.fr,PASSWORD ftp.sc3bera6697.universe.wf
cd public_html
mput *.html *.php logo*.png
mirror -R api/ api/
mirror -R assets/ assets/
quit
"
```

---

## 📚 Documentation Conservée
- ✅ **QUICK_START.md** - Guide démarrage rapide
- ✅ **README_DEV_GUIDE.md** - Guide développeur
- ✅ **FTP_INFO.md** - Informations FTP
- ✅ **database/GUIDE_UTILISATION.md** - Guide base de données
- ✅ **docs/** - Documentation technique

---

## 🔄 Historique Git
**Dernier commit:** Nettoyage projet et nouveau header moderne
- Nouveau header blanc avec gradient
- Suppression ~80 fichiers obsolètes
- Structure organisée et maintenable
- Code couleur dates d'expiration

---

## 👥 Contact & Support
**Projet:** Danalakshmi Store - Gestion Expiration  
**Repository:** danalakshmiaccueil-wq/Expiration  
**Production:** https://expire.danalakshmi.fr
