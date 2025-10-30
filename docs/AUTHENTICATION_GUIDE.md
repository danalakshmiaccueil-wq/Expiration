# 🔐 Système d'Authentification - Danalakshmi Expiration

## Vue d'ensemble

Système d'authentification JWT (JSON Web Token) pour sécuriser l'accès à l'application de gestion des dates d'expiration.

---

## 📁 Structure des fichiers

```
login.html                          # Page de connexion
api/auth/
├── login.php                       # API de connexion
├── logout.php                      # API de déconnexion
├── validate.php                    # Validation des tokens
├── auth_utils.php                  # Fonctions utilitaires JWT
└── database.php                    # Connexion DB pour auth
database/
└── auth_tables.sql                 # Script création tables
```

---

## 🎨 Page de Login (login.html)

### Fonctionnalités

- ✅ Design moderne et responsive
- ✅ Interface bi-colonne (branding + formulaire)
- ✅ Affichage/masquage du mot de passe
- ✅ Option "Se souvenir de moi"
- ✅ Messages d'erreur/succès dynamiques
- ✅ Redirection automatique si déjà connecté
- ✅ Spinner de chargement pendant la connexion

### Champs du formulaire

- **Username** : Nom d'utilisateur
- **Password** : Mot de passe
- **Remember Me** : Se souvenir (token 30 jours vs 24h)

---

## 🔌 API d'Authentification

### POST `/api/auth/login.php`

**Connexion utilisateur**

**Request:**
```json
{
  "username": "admin",
  "password": "Admin2024!",
  "remember": false
}
```

**Response (succès):**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "nom": "Administrateur",
    "prenom": "Système",
    "email": "admin@danalakshmi.fr",
    "role": "admin"
  }
}
```

**Response (échec):**
```json
{
  "success": false,
  "message": "Identifiants incorrects"
}
```

---

### POST `/api/auth/validate.php`

**Valider un token**

**Headers:**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Response:**
```json
{
  "valid": true,
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

---

### POST `/api/auth/logout.php`

**Déconnexion utilisateur**

**Headers:**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Response:**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

---

## 💾 Base de Données

### Table `utilisateurs`

```sql
id                  INT             # ID unique
username            VARCHAR(50)     # Nom d'utilisateur (unique)
password            VARCHAR(255)    # Mot de passe haché (bcrypt)
nom                 VARCHAR(100)    # Nom
prenom              VARCHAR(100)    # Prénom
email               VARCHAR(150)    # Email (unique)
role                ENUM            # admin|gestionnaire|utilisateur
actif               TINYINT(1)      # Compte actif (1) ou non (0)
date_creation       TIMESTAMP       # Date de création
derniere_connexion  TIMESTAMP       # Dernière connexion
created_at          TIMESTAMP       # Date création
updated_at          TIMESTAMP       # Date modification
```

### Table `sessions`

```sql
id          INT         # ID unique
user_id     INT         # FK vers utilisateurs
token       TEXT        # Token JWT
ip_address  VARCHAR(45) # Adresse IP
user_agent  TEXT        # Navigateur/Device
expires_at  TIMESTAMP   # Date d'expiration
created_at  TIMESTAMP   # Date création
```

---

## 👥 Utilisateurs par Défaut

### Administrateur
- **Username:** `admin`
- **Password:** `Admin2024!`
- **Role:** admin
- **Email:** admin@danalakshmi.fr

### Gestionnaire
- **Username:** `gestionnaire`
- **Password:** `Gestionnaire2024!`
- **Role:** gestionnaire
- **Email:** gestionnaire@danalakshmi.fr

### Utilisateur
- **Username:** `user`
- **Password:** `User2024!`
- **Role:** utilisateur
- **Email:** user@danalakshmi.fr

> ⚠️ **IMPORTANT:** Changez ces mots de passe immédiatement en production !

---

## 🔐 Sécurité

### JWT (JSON Web Token)

**Structure:**
```
Header.Payload.Signature
```

**Payload:**
```json
{
  "user_id": 1,
  "username": "admin",
  "role": "admin",
  "iat": 1698765432,  // Issued at
  "exp": 1698851832   // Expiration
}
```

**Durée de validité:**
- Sans "Se souvenir" : **24 heures**
- Avec "Se souvenir" : **30 jours**

### Hachage des mots de passe

- Algorithme: **bcrypt**
- Cost factor: **12**
- Fonction PHP: `password_hash()` avec `PASSWORD_BCRYPT`

### Stockage des tokens

**Frontend:**
- `localStorage` : Token persistant (remember me)
- `sessionStorage` : Token de session

**Backend:**
- Table `sessions` : Historique et gestion des sessions actives

---

## 🚀 Installation

### 1. Créer les tables

```bash
mysql -u sc3bera6697_user_exp -p sc3bera6697_danalakshmi_expiration < database/auth_tables.sql
```

### 2. Configurer la base de données

Modifier `api/auth/database.php` si nécessaire :

```php
$host = 'localhost';
$dbname = 'sc3bera6697_danalakshmi_expiration';
$username = 'sc3bera6697_user_exp';
$password = 'ExpDate2024!Secure';
```

### 3. Configurer la clé secrète JWT

Modifier `api/auth/auth_utils.php` :

```php
define('JWT_SECRET', 'VOTRE_CLE_SECRETE_UNIQUE');
```

> 💡 Générez une clé aléatoire forte de 64+ caractères

### 4. Déployer les fichiers

```bash
# Upload sur le serveur
lftp -c "
  open -u expire@expire.danalakshmi.fr,PASSWORD ftp.sc3bera6697.universe.wf
  cd public_html
  put login.html
  mirror -R api/auth/ api/auth/
  quit
"
```

---

## 🔄 Utilisation dans les pages

### Vérifier l'authentification

```javascript
// Au chargement de la page
window.addEventListener('load', async function() {
    const token = localStorage.getItem('authToken') || 
                  sessionStorage.getItem('authToken');
    
    if (!token) {
        window.location.href = 'login.html';
        return;
    }
    
    // Valider le token
    const response = await fetch('api/auth/validate.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });
    
    const data = await response.json();
    
    if (!data.valid) {
        localStorage.removeItem('authToken');
        sessionStorage.removeItem('authToken');
        window.location.href = 'login.html';
    }
});
```

### Protéger une API

```php
<?php
require_once 'auth/auth_utils.php';

// Vérifier l'authentification
$user = requireAuth();

// $user contient: user_id, username, role, etc.
// Continuer avec le code de l'API...
?>
```

### Déconnexion

```javascript
async function logout() {
    const token = localStorage.getItem('authToken') || 
                  sessionStorage.getItem('authToken');
    
    await fetch('api/auth/logout.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });
    
    localStorage.removeItem('authToken');
    sessionStorage.removeItem('authToken');
    sessionStorage.removeItem('user');
    
    window.location.href = 'login.html';
}
```

---

## 🛠️ Maintenance

### Nettoyer les sessions expirées

**Manuellement:**
```sql
CALL clean_expired_sessions();
```

**Automatiquement (Cron):**
```bash
# Tous les jours à 3h du matin
0 3 * * * mysql -u USER -pPASS DB -e "CALL clean_expired_sessions();"
```

### Désactiver un utilisateur

```sql
UPDATE utilisateurs SET actif = 0 WHERE username = 'nom_utilisateur';
```

### Changer le mot de passe

```php
<?php
$newPassword = 'NouveauMotDePasse123!';
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

// Mettre à jour dans la DB
$stmt = $db->prepare("UPDATE utilisateurs SET password = ? WHERE username = ?");
$stmt->execute([$hashedPassword, 'admin']);
?>
```

---

## 📝 Logs et Monitoring

### Logs d'erreur

Les erreurs sont enregistrées via `error_log()` dans les fichiers PHP.

Localisation (selon config serveur) :
- `/var/log/php/error.log`
- `/var/log/apache2/error.log`

### Sessions actives

```sql
SELECT 
    u.username,
    u.nom,
    u.prenom,
    s.ip_address,
    s.created_at,
    s.expires_at
FROM sessions s
JOIN utilisateurs u ON s.user_id = u.id
WHERE s.expires_at > NOW()
ORDER BY s.created_at DESC;
```

---

## 🔒 Bonnes Pratiques

✅ **À FAIRE:**
- Changer les mots de passe par défaut
- Utiliser HTTPS en production
- Générer une clé JWT unique et forte
- Implémenter rate limiting (limite tentatives)
- Logger les tentatives de connexion échouées
- Nettoyer régulièrement les sessions expirées
- Utiliser des mots de passe forts (min 12 caractères)

❌ **À NE PAS FAIRE:**
- Stocker les tokens en clair dans la DB
- Utiliser les mots de passe par défaut
- Partager la clé JWT
- Désactiver HTTPS
- Ignorer les logs d'erreur

---

## 🆘 Dépannage

### Problème: "Token invalide"
- Vérifier que la clé JWT est identique partout
- Vérifier l'expiration du token
- Vérifier les headers Authorization

### Problème: "Erreur de connexion DB"
- Vérifier les credentials dans `database.php`
- Vérifier que les tables existent
- Vérifier les permissions de l'utilisateur MySQL

### Problème: "Identifiants incorrects"
- Vérifier username/password
- Vérifier que le compte est actif (`actif = 1`)
- Vérifier le hash du mot de passe

---

**Système d'authentification prêt ! 🎉**
