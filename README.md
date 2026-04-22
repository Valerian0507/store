# AquaWorld

Application e-commerce développée avec Symfony dans le cadre de la formation **Développeur Web et Web Mobile (TP DWWM)**.

Le projet couvre un parcours complet de boutique en ligne avec catalogue public, panier, compte utilisateur, tunnel de commande et espace d'administration.

## Fonctionnalités

### Partie publique

- page d'accueil
- catalogue produits avec recherche, tri, filtre par catégorie et pagination
- fiche détail d'un produit
- ajout au panier
- gestion du panier
- pages légales

### Authentification et sécurité

- inscription
- connexion / déconnexion
- vérification d'email
- renvoi du lien de vérification
- blocage de la connexion si le compte n'est pas vérifié
- protection CSRF sur les actions sensibles
- gestion des rôles `ROLE_USER` et `ROLE_ADMIN`

### Espace utilisateur

- tableau de bord profil
- consultation des commandes
- détail d'une commande
- gestion des adresses
- définition d'une adresse par défaut

### Checkout

- panier en session pour les visiteurs
- panier persistant en base pour les utilisateurs connectés
- fusion du panier visiteur vers le panier utilisateur après connexion
- récapitulatif de commande
- création de commande
- page de confirmation basée sur des ViewModels

### Administration

- gestion des produits
- upload et suppression d'image produit
- import initial du catalogue depuis un fichier JSON
- gestion des catégories
- gestion des commandes avec recherche, filtres, pagination et mise à jour du statut
- gestion des utilisateurs avec recherche, filtres, pagination, détail et changement de rôle

## Stack technique

- PHP 8.2+
- Symfony 7.4
- Doctrine ORM
- Doctrine Migrations
- MySQL 8
- Twig
- AssetMapper
- Bootstrap
- Docker Compose
- Mailpit
- phpMyAdmin

## Architecture

Le projet est organisé autour de trois zones principales :

- partie publique
- espace profil utilisateur
- espace administration

Organisation générale :

- `Controller -> Repository / Service -> Twig`

Choix techniques principaux :

- logique métier extraite dans des services
- utilisation de ViewModels pour le checkout et la page de succès
- contrôle de propriété sur les données utilisateur
- `UserChecker` pour empêcher la connexion d'un compte non vérifié
- subscriber de connexion pour fusionner le panier session et le panier persistant

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- Docker et Docker Compose
- Symfony CLI (optionnel, pour lancer le serveur de dev)

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/Valerian0507/store.git
cd store
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Démarrer les services Docker

```bash
docker compose up -d
```

Services fournis par `compose.yaml` :

- MySQL sur `127.0.0.1:3307`
- phpMyAdmin sur `http://127.0.0.1:8080`
- Mailpit sur `http://127.0.0.1:8025`

### 4. Configurer l'environnement local

Créer un fichier `.env.local` avec une configuration adaptée à votre machine. Exemple :

```env
DATABASE_URL="mysql://store_user:mot_de_passe@127.0.0.1:3307/store_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://localhost:1025
APP_URL=http://127.0.0.1:8000
```

Les valeurs par défaut définies dans `compose.yaml` sont :

```md
utilisateur : `store_user`
base de données: `store_db`
port : `3307`
```

Si vous utilisez les valeurs par défaut du `docker compose`, adaptez simplement le mot de passe a votre configuration locale

### 5. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 6. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 7. Charger les fixtures utilisateur

```bash
php bin/console doctrine:fixtures:load
```

### 8. Importer le catalogue produits

Le projet fournit un importeur JSON via la commande suivante :

```bash
php bin/console app:import-products
```

Par défaut, le fichier attendu est :

```text
var/data/catalogue.json
```

Il est aussi possible de fournir un autre fichier :

```bash
php bin/console app:import-products chemin/vers/mon-fichier.json
```

### 9. Lancer l'application

Avec Symfony CLI :

```bash
symfony server:start
```

Application disponible ensuite sur :

```text
http://127.0.0.1:8000
```

## Comptes de démonstration

Après chargement des fixtures :

### Administrateur

- email : `admin@store.com`
- mot de passe : `password`

### Utilisateur

- email : `user@store.com`
- mot de passe : `password`

## Entités principales

- `User`
- `Product`
- `Category`
- `Address`
- `Order`
- `OrderItem`
- `Cart`
- `CartItem`

## Captures d'écran

### Page d'accueil

![Page d'accueil 1](docs/screenshots/page-d'accueil1.png)

![Page d'accueil 2](docs/screenshots/page-d'accueil2.png)

### Catalogue produits

![Catalogue produits](docs/screenshots/catalogue.png)

### Fiche produit

![Fiche produit](docs/screenshots/product-show.png)

### Panier

![Panier](docs/screenshots/cart.png)

### Checkout

![Checkout](docs/screenshots/checkout.png)

### Connexion

![Connexion](docs/screenshots/login.png)

### Inscription

![Inscription](docs/screenshots/register.png)

### Administration des produits

![Administration des produits](docs/screenshots/admin-products.png)

### Administration des commandes

![Administration des commandes](docs/screenshots/admin-orders.png)

### Administration des utilisateurs

![Administration des utilisateurs](docs/screenshots/admin-users.png)

## Limites actuelles

- pas de paiement en ligne réel
- pas de gestion de stock au moment de la commande
- pas de réinitialisation de mot de passe
- pas encore de tests automatisés métier

## Auteur

Projet réalisé par **O. Chahinian** dans le cadre de la formation **TP DWWM**.
