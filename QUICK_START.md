# 🚀 DÉMARRAGE RAPIDE - Tests Locaux
## Danalakshmi Store - Guide Express

> **⚡ En 5 minutes : Testez l'application complète sur votre ordinateur**

---

## 🎯 Étapes Ultra-Rapides

### 1. **Installer un Serveur Local** (5 minutes)

```bash
# Choisir selon votre système :

# 🖥️ XAMPP (Recommandé - Tous systèmes)
# Télécharger : https://www.apachefriends.org/
# ✅ Simple, stable, bien documenté

# 🍎 MAMP (macOS/Windows)
# Télécharger : https://www.mamp.info/
# ✅ Interface graphique élégante

# 🪟 WAMP (Windows seulement)
# Télécharger : https://www.wampserver.com/
# ✅ Intégration Windows optimisée
```

### 2. **Démarrer les Services** (30 secondes)

```bash
# Dans XAMPP Control Panel :
✅ Démarrer Apache (Port 80)
✅ Démarrer MySQL (Port 3306)

# Vérifier : http://localhost/ 
# → Page d'accueil XAMPP doit s'afficher
```

### 3. **Copier le Projet** (1 minute)

```bash
# Depuis le dossier du projet :
./copy-to-local.sh

# Le script fait tout automatiquement :
# ✅ Détecte XAMPP/MAMP/WAMP
# ✅ Copie tous les fichiers
# ✅ Configure la base de données
# ✅ Définit les permissions
```

### 4. **Créer la Base de Données** (1 minute)

```bash
# Automatique avec le script :
./setup-database.sh

# OU manuellement :
# 1. Aller sur http://localhost/phpmyadmin/
# 2. Créer base "danalakshmi_expiration"
# 3. Importer database/install.sql
```

### 5. **Tester l'Application** (2 minutes)

```bash
# 🔧 Backend API :
http://localhost/danalakshmi/api/health
# → Doit retourner : {"status":"healthy"}

# 🎨 Frontend Application :
http://localhost/danalakshmi/
# → Dashboard doit s'afficher

# 🧪 Interface de Test :
http://localhost/danalakshmi/api/test
# → Interface complète pour tester l'API
```

---

## ✅ Checklist Express

- [ ] **Serveur installé** : XAMPP/MAMP/WAMP
- [ ] **Services démarrés** : Apache + MySQL
- [ ] **Projet copié** : `./copy-to-local.sh`
- [ ] **Base créée** : `./setup-database.sh`
- [ ] **API testée** : `/api/health` répond
- [ ] **Frontend testé** : Dashboard s'affiche
- [ ] **PWA testée** : Installation possible

---

## 🔥 Tests Essentiels

### **Dashboard (2 minutes)**
```bash
# 1. Aller sur http://localhost/danalakshmi/
# 2. Vérifier que les statistiques s'affichent
# 3. Tester la navigation entre onglets
```

### **Gestion Produits (3 minutes)**
```bash
# 1. Cliquer sur "Produits"
# 2. Ajouter un nouveau produit
# 3. Modifier un produit existant
# 4. Tester la recherche
```

### **Gestion Lots (3 minutes)**
```bash
# 1. Cliquer sur "Lots"
# 2. Ajouter un lot avec date d'expiration
# 3. Vérifier les alertes visuelles (couleurs)
# 4. Tester les filtres par statut
```

### **PWA (2 minutes)**
```bash
# 1. Dans Chrome/Edge : Icône d'installation dans la barre
# 2. Installer l'application
# 3. Tester le mode hors ligne (DevTools → Network → Offline)
```

---

## 🆘 Dépannage Express

### **❌ Apache ne démarre pas**
```bash
# Cause : Port 80 occupé
# Solution : Arrêter Skype ou changer le port Apache (8080)
```

### **❌ MySQL ne démarre pas**
```bash
# Cause : Port 3306 occupé ou autre MySQL actif
# Solution : Arrêter autres services MySQL
```

### **❌ Page 404 sur /danalakshmi/**
```bash
# Cause : Projet non copié ou mal placé
# Solution : Relancer ./copy-to-local.sh
```

### **❌ Erreur 500 sur l'API**
```bash
# Cause : Base de données non configurée
# Solution : Relancer ./setup-database.sh
```

### **❌ Frontend ne charge pas**
```bash
# Cause : Assets manquants
# Solution : Vérifier htdocs/danalakshmi/assets/
```

---

## 📊 Validation Express

### **✅ API Fonctionnelle**
```bash
curl http://localhost/danalakshmi/api/health
# Réponse : {"status":"healthy","message":"API opérationnelle"}
```

### **✅ Base de Données**
```bash
# Dans phpMyAdmin : http://localhost/phpmyadmin/
# Base "danalakshmi_expiration" avec 8+ tables/vues
```

### **✅ Frontend Responsive**
```bash
# Tester sur :
# - Desktop (1920x1080)
# - Tablette (768x1024) 
# - Mobile (375x667)
```

### **✅ Performance Locale**
```bash
# Lighthouse dans Chrome DevTools :
# Performance > 80, PWA = 100
```

---

## 🎯 Prêt pour Production

### **Si tous les tests passent :**

```bash
# 🎉 Application validée en local !
# 🚀 Prête pour déploiement cPanel

# Créer l'archive de production :
cd frontend/
./build.sh

# Suivre ensuite : frontend/DEPLOYMENT.md
```

---

## 📱 Bonus : Test PWA Mobile

### **Simulation Mobile**
```bash
# 1. Chrome DevTools (F12)
# 2. Toggle Device Toolbar (Ctrl+Shift+M)
# 3. Choisir iPhone/Android
# 4. Tester navigation tactile
# 5. Installer PWA (Add to Home Screen)
```

---

## 💡 Conseils Pro

### **🔍 Debug Facile**
- **API** : Interface `/api/test` pour tout tester
- **Frontend** : DevTools Console pour les erreurs
- **Base** : phpMyAdmin pour inspecter les données

### **⚡ Performance**
- **Cache** : Configuré automatiquement (1 min en local)
- **Logs** : Mode debug activé pour le développement
- **Refresh** : F5 pour recharger, Ctrl+F5 pour cache clear

### **🔒 Sécurité**
- **Local seulement** : Configuration non sécurisée pour tests
- **Production** : Utiliser les configs du guide DEPLOYMENT.md

---

**🎯 En 10 minutes maximum, votre environnement de test est opérationnel !**

> **L'application fonctionne exactement comme en production, vous pouvez tester toutes les fonctionnalités sans risque.**

---

## 📞 Support Rapide

- **Documentation complète** : `TEST_LOCAL.md`
- **Guide production** : `frontend/DEPLOYMENT.md`
- **Interface de test** : `http://localhost/danalakshmi/api/test`
- **Logs en temps réel** : `htdocs/danalakshmi/api/logs/`