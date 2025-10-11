# 🗄️ Database - Application Expiration

## Description
Ce dossier contient les scripts de base de données pour l'application de gestion des dates de péremption.

## Structure de la base de données

### Tables principales

#### `produits`
- `id` (PRIMARY KEY)
- `nom` (VARCHAR)
- `code_barre` (VARCHAR, UNIQUE)
- `categorie` (VARCHAR)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

#### `lots`
- `id` (PRIMARY KEY)
- `produit_id` (FOREIGN KEY)
- `date_expiration` (DATE)
- `quantite` (INTEGER)
- `date_reception` (DATE)
- `statut` (ENUM: 'actif', 'solde')
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

#### `parametres`
- `id` (PRIMARY KEY)
- `alerte_j1` (BOOLEAN)
- `alerte_j7` (BOOLEAN)
- `alerte_j30` (BOOLEAN)
- `alerte_j60` (BOOLEAN)
- `updated_at` (TIMESTAMP)

## Fichiers prévus
```
database/
├── schema/
│   ├── create_tables.sql
│   └── indexes.sql
├── migrations/
├── seeders/
│   └── sample_data.sql
└── README.md
```

## Installation
*Instructions à ajouter lors du développement*