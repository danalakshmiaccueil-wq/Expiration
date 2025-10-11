# 🏗️ Guide de déploiement cPanel - Application Expiration

## 📋 Vue d'ensemble
Guide complet pour déployer l'application de gestion des dates d'expiration sur un hébergement cPanel.

---

## 🎯 Architecture cPanel recommandée

### Structure des fichiers sur le serveur
```
public_html/
├── index.php                 ← Dashboard principal
├── produits.php             ← Gestion des produits
├── lots.php                 ← Gestion des lots
├── alertes.php              ← Vue des alertes
├── parametres.php           ← Configuration
├── statistiques.php         ← Rapports
├── api/                     ← API Backend PHP
│   ├── config/
│   │   ├── database.php     ← Configuration MySQL
│   │   └── config.php       ← Variables globales
│   ├── classes/
│   │   ├── Database.php     ← Connexion DB
│   │   ├── Produit.php      ← Modèle Produit
│   │   ├── Lot.php          ← Modèle Lot
│   │   └── Alerte.php       ← Gestionnaire alertes
│   ├── endpoints/
│   │   ├── produits.php     ← API CRUD produits
│   │   ├── lots.php         ← API CRUD lots
│   │   ├── alertes.php      ← API alertes
│   │   ├── dashboard.php    ← API métriques
│   │   └── parametres.php   ← API paramètres
│   └── utils/
│       ├── cors.php         ← Gestion CORS
│       ├── auth.php         ← Authentification
│       └── helpers.php      ← Fonctions utilitaires
├── assets/
│   ├── css/
│   │   ├── style.css        ← Styles principaux
│   │   └── responsive.css   ← Styles mobile
│   ├── js/
│   │   ├── app.js           ← Application principale
│   │   ├── modules/         ← Modules JavaScript
│   │   └── vendor/          ← Bibliothèques externes
│   └── images/
└── includes/
    ├── header.php           ← En-tête commun
    ├── footer.php           ← Pied de page
    ├── nav.php              ← Navigation
    └── functions.php        ← Fonctions PHP communes
```

---

## 🔧 Configuration MySQL via cPanel

### 1. Création de la base de données
```
1. Aller dans "Bases de données MySQL" dans cPanel
2. Créer une nouvelle base de données : "username_expiration"
3. Créer un utilisateur MySQL : "username_expapp"
4. Assigner l'utilisateur à la base avec tous les privilèges
5. Noter les informations de connexion
```

### 2. Import du schéma
```
1. Aller dans "phpMyAdmin"
2. Sélectionner la base "username_expiration"
3. Onglet "Importer"
4. Choisir le fichier "database/install.sql"
5. Exécuter l'import
6. Vérifier que les tables sont créées
```

### 3. Configuration PHP
```php
// api/config/database.php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'username_expiration');  // Remplacer username
define('DB_USER', 'username_expapp');      // Remplacer username
define('DB_PASS', 'mot_de_passe_securise');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $pdo;

    public function getConnection() {
        $this->pdo = null;
        try {
            $this->pdo = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . DB_CHARSET,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Erreur de connexion: " . $exception->getMessage());
            die("Erreur de connexion à la base de données");
        }
        return $this->pdo;
    }
}
?>
```

---

## 📁 Déploiement étape par étape

### Phase 1 : Préparation locale
```bash
# 1. Finaliser le développement local
# 2. Tester toutes les fonctionnalités
# 3. Optimiser les fichiers CSS/JS
# 4. Préparer le package de déploiement
```

### Phase 2 : Upload des fichiers
```
Méthode 1 - FileManager cPanel :
1. Compresser les fichiers localement (ZIP)
2. Aller dans "Gestionnaire de fichiers"
3. Naviguer vers "public_html"
4. Uploader le fichier ZIP
5. Extraire directement sur le serveur

Méthode 2 - FTP :
1. Utiliser FileZilla ou client FTP
2. Se connecter avec les identifiants cPanel
3. Naviguer vers "public_html"
4. Uploader tous les fichiers
```

### Phase 3 : Configuration serveur
```php
// 1. Vérifier les permissions des fichiers
chmod 755 sur les dossiers
chmod 644 sur les fichiers PHP/HTML/CSS/JS

// 2. Configuration .htaccess pour l'API
# public_html/.htaccess
RewriteEngine On

# Redirection API propre
RewriteRule ^api/([^/]+)/?$ api/endpoints/$1.php [L,QSA]

# Sécurité
<Files "*.sql">
    Order Allow,Deny
    Deny from all
</Files>

# Cache des assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
</IfModule>
```

---

## 🚀 Optimisations cPanel

### Performance PHP
```php
// api/config/config.php
<?php
// Configuration pour hébergement partagé
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 30);

// Cache simple pour les requêtes fréquentes
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes

// Gestion des erreurs en production
if ($_SERVER['HTTP_HOST'] !== 'localhost') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}
?>
```

### Optimisation base de données
```sql
-- Optimisation pour hébergement partagé
SET SESSION query_cache_type = ON;

-- Index spécifiques pour les requêtes fréquentes
ALTER TABLE lots ADD INDEX idx_alertes_rapides (statut, date_expiration);
ALTER TABLE produits ADD INDEX idx_recherche (nom, categorie);

-- Optimisation des vues pour le cache
-- (Les vues sont déjà optimisées dans notre schéma)
```

---

## 🔐 Sécurité cPanel

### Protection des fichiers sensibles
```php
// api/config/security.php
<?php
// Vérification de l'origine des requêtes
function checkOrigin() {
    $allowed_origins = [
        'https://votre-domaine.com',
        'https://www.votre-domaine.com'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!in_array($origin, $allowed_origins) && !empty($origin)) {
        http_response_code(403);
        die('Accès interdit');
    }
}

// Protection CSRF simple
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}
?>
```

### Sauvegarde automatique
```php
// api/utils/backup.php
<?php
// Script de sauvegarde à exécuter via cron job
function createBackup() {
    $backup_dir = __DIR__ . '/../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = sprintf(
        'mysqldump -h %s -u %s -p%s %s > %s',
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $backup_dir . $filename
    );
    
    exec($command);
    
    // Nettoyer les anciennes sauvegardes (garder 7 jours)
    $files = glob($backup_dir . 'backup_*.sql');
    if (count($files) > 7) {
        array_map('unlink', array_slice($files, 0, count($files) - 7));
    }
}
?>
```

---

## 📊 Monitoring et maintenance

### Logs et surveillance
```php
// api/utils/logger.php
<?php
class Logger {
    private static $log_file;
    
    public static function init() {
        self::$log_file = __DIR__ . '/../logs/app.log';
        if (!is_dir(dirname(self::$log_file))) {
            mkdir(dirname(self::$log_file), 0755, true);
        }
    }
    
    public static function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>
```

### Tâches cron recommandées
```bash
# cPanel > Cron Jobs

# Mise à jour des alertes toutes les heures
0 * * * * /usr/bin/php /home/username/public_html/api/utils/update_alerts.php

# Sauvegarde quotidienne à 2h du matin
0 2 * * * /usr/bin/php /home/username/public_html/api/utils/backup.php

# Nettoyage des logs hebdomadaire
0 3 * * 0 /usr/bin/php /home/username/public_html/api/utils/cleanup_logs.php
```

---

## ✅ Checklist de déploiement

### Avant déploiement
- [ ] Tests complets en local
- [ ] Base de données optimisée
- [ ] Configuration de production testée
- [ ] Assets minifiés et optimisés
- [ ] Documentation mise à jour

### Pendant déploiement
- [ ] Sauvegarde des données existantes
- [ ] Upload des fichiers
- [ ] Configuration MySQL
- [ ] Test des permissions
- [ ] Vérification des connexions

### Après déploiement
- [ ] Test de toutes les fonctionnalités
- [ ] Vérification des logs d'erreur
- [ ] Configuration du monitoring
- [ ] Mise en place des sauvegardes
- [ ] Documentation utilisateur final

---

**Prochaine étape recommandée** : Commencer le développement de l'API PHP en suivant cette architecture cPanel-friendly ! 🚀