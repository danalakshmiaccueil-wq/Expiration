# 🚀 Guide de développement – Projet **Expiration**

## 🧩 Contexte
Le projet **Expiration** a pour objectif de créer une **application de gestion des dates de péremption des produits** alimentaires du magasin.  
Cette application est **indépendante du système d’inventaire** et sert uniquement à suivre les lots selon leurs dates d’expiration.

## 🎯 Objectifs principaux
- Suivi des produits selon leur date de péremption.
- Alerte visuelle sur les produits arrivant à expiration (1 jour, 1 semaine, 1 mois, 2 mois – configurable).
- Enregistrement des dates d’expiration et des quantités à la réception des marchandises.
- Marquage des lots comme **« Soldé »** (plus en entrepôt).
- Tableau de bord avec filtres et métriques dynamiques.
- Système simple, intuitif, sans gestion de stock en temps réel.

---

## 🧱 Structure du projet
```
Expiration/
│
├── Cahier_de_charge/
│   └── cahier_de_charge.md     ← Cahier des charges complet (rédigé)
│
├── README_DEV_GUIDE.md         ← Ce fichier (résumé pour Copilot & devs)
└── README.md                   ← Présentation du projet
```

---

## 🧰 Instructions Git & VS Code

### 1️⃣ Initialisation locale
Ouvre **VS Code**, puis :
```bash
cd "C:\Users\ariva\OneDrive\Documents\IT\Expiration"
git init -b main
```

Crée ou vérifie les fichiers :
- `Cahier_de_charge/cahier_de_charge.md`
- `README.md`

---

### 2️⃣ Configurer ton identité Git (si pas encore fait)
```bash
git config --global user.name "Ton Nom"
git config --global user.email "ton.email@example.com"
```

---

### 3️⃣ Créer le premier commit
```bash
git add -A
git commit -m "Add cahier de charge and initial project files"
```

---

### 4️⃣ Connecter le dépôt GitHub
```bash
git remote add origin https://github.com/danalakshmiaccueil-wq/Expiration.git
git push -u origin main
```
> Authentifie-toi via **VS Code** (Accounts → Sign in to GitHub).

---

## 🧭 Travail à poursuivre (pour Copilot)
Copilot peut aider sur :
- La **structure du backend** (API CRUD : produits, lots, alertes).
- Le **frontend React/Vue** pour afficher les lots et le tableau de bord.
- La **base de données** : tables `produits`, `lots`, `parametres`.
- Le **système de filtres** par date d’expiration (J+1, J+7, etc.).
- L’**interface de saisie** pour enregistrer produit + date + quantité.

---

## ✅ Prochaines étapes
1. Vérifier que le dépôt `https://github.com/danalakshmiaccueil-wq/Expiration` contient le dossier `Cahier_de_charge/`.
2. Lancer la création du backend (ex. : `backend/` avec API Node.js, Django, ou Laravel).
3. Créer le frontend (ex. : `frontend/` avec React + Tailwind).
4. Configurer Copilot pour assister le développement.

---

**Auteur** : Équipe ExpireDate  
**Dernière mise à jour** : Octobre 2025
