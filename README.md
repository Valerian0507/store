# AquaWorld – Site web e-commerce Symfony

## Présentation

Ce projet est une application e-commerce développée dans le cadre de ma formation **Développeur Web et Web Mobile (TP DWWM)**.

L’objectif était de concevoir une application web complète avec une partie publique, un espace utilisateur et un espace d’administration, en appliquant une architecture claire ainsi que de bonnes pratiques de développement : sécurité, validation, gestion des rôles, protection CSRF, gestion de fichiers, séparation de la logique métier et utilisation d’une base de données relationnelle.

---

## Fonctionnalités principales

### Partie publique

- affichage du catalogue produits
- consultation de la fiche d’un produit
- ajout au panier
- gestion du panier

### Authentification / sécurité

- inscription
- connexion / déconnexion
- vérification d’email
- renvoi de l’email de confirmation
- réinitialisation du mot de passe
- blocage de la connexion si l’email n’est pas vérifié

### Espace utilisateur

- tableau de bord du profil
- consultation des commandes
- détail d’une commande
- gestion des adresses :
  - liste
  - création
  - modification
  - suppression
  - contrôle de propriété

### Panier et commande

- panier en session pour les visiteurs
- panier persistant en base pour les utilisateurs connectés
- fusion du panier session vers le panier utilisateur après connexion
- checkout
- création de commande
- page de confirmation avec ViewModel

### Administration

- gestion des produits :

  - liste
  - création
  - modification
  - suppression
  - upload d’image
  - import initial du catalogue produits depuis un fichier JSON
- gestion des commandes :

  - liste
  - détail
  - mise à jour du statut
  - recherche
  - filtres
  - pagination
- gestion des catégories :

  - liste
  - création
  - modification
  - suppression
- gestion des utilisateurs :

  - liste
  - détail
  - commandes récentes
  - mise à jour du rôle
  - recherche / filtres / pagination
  - bloc adresses

---

## Stack technique

- **PHP 8.2+**
- **Symfony 7.4**
- **MySQL**
- **Doctrine ORM**
- **Twig**
- **Docker**
- **AssetMapper**
- **Bootstrap**
- sans Webpack Encore

---

## Architecture

Le projet est structuré en trois zones principales :

- **partie publique**
- **espace profil utilisateur**
- **espace administration**

Architecture générale :

- **Controller -> Repository / Service -> Twig**

Choix techniques principaux :

- séparation de la logique métier dans les services
- utilisation de ViewModel pour certaines pages
- gestion des rôles `ROLE_USER` / `ROLE_ADMIN`
- protection CSRF sur les actions sensibles
- contrôle de sécurité et de propriété sur les données utilisateur
- vérification d’email avec `VerifyEmailBundle`
- contrôle d’accès à la connexion avec un `UserChecker`
- fusion du panier après connexion via un subscriber

---

## Compétences mobilisées

Ce projet m’a permis de mobiliser plusieurs compétences du TP DWWM :

- développer une application web dynamique avec Symfony
- concevoir et manipuler une base de données relationnelle
- mettre en œuvre des fonctionnalités sécurisées
- structurer une application selon une architecture claire
- gérer des rôles utilisateurs et des accès
- développer une interface utilisateur avec Twig et Bootstrap
- administrer des contenus, des commandes et des utilisateurs

---

## Prérequis

Avant de lancer le projet, il faut disposer de :

- PHP 8.2 ou supérieur
- Composer
- Docker et Docker Compose
- MySQL
- Symfony CLI (optionnel mais pratique)

---

## Installation

### 1. Cloner le projet

```bash
git clone <URL_DU_REPO>
cd <NOM_DU_DOSSIER>
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer les variables d’environnement

Créer un fichier `.env.local` et configurer notamment :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/nom_de_la_base?serverVersion=8.0"
MAILER_DSN=null://null
```

Adapter les valeurs selon votre environnement local.

### 4. Démarrer les services Docker

```bash
docker compose up -d
```

### 5. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 6. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 7. Charger les données de démonstration

```bash
php bin/console doctrine:fixtures:load
```

Les fixtures permettent de créer les comptes de démonstration, notamment les utilisateurs et l’administrateur.

### 8. Importer les produits depuis le fichier JSON

Le catalogue peut être initialisé à partir d’un fichier JSON situé par défaut dans : ***var/data/catalogue.json***

```bash
php bin/console app:import-products
```

Si besoin, un autre fichier peut être passé en argument :

```bash
php bin/console app:import-products chemin/vers/mon-fichier.json
```

---

## Base de données

La base de données repose sur plusieurs entités principales :

- User
- Product
- Category
- Address
- Order
- OrderItem
- Cart
- CartItem

Le schéma permet notamment :

- la gestion des utilisateurs et de leurs rôles
- la gestion du catalogue produits
- la gestion des adresses utilisateur
- la gestion des commandes et de leurs lignes
- la persistance du panier utilisateur

Le projet permet également le chargement initial de données produits à partir d’un fichier JSON, si cette fonctionnalité est utilisée pour alimenter le catalogue de démonstration.

---

## Lancement du projet

Selon votre configuration, vous pouvez lancer le projet avec Symfony CLI :

```bash
symfony server:start
```

Puis accéder à l’application via :

```bash
http://127.0.0.1:8000
```

---

## Comptes de démonstration

```md
## Administrateur

- email : `admin@example.com`
- mot de passe : `password`

## Utilisateur

- email : `user@example.com`
- mot de passe : `password`

> Adapter ces identifiants selon vos fixtures.
```

---

## Captures d'écran

Voici quelques écrans représentatifs de l’application :

### Catalogue produits

![Catalogue produits](docs/screenshots/catalogue.png)

### Fiche produit

![Fiche produit](docs/screenshots/product-show.png)

### Panier

![Panier](docs/screenshots/cart.png)

### Checkout

![Checkout](docs/screenshots/checkout.png)

### Administration des produits

![Administration des produits](docs/screenshots/admin-products.png)

### Administration des commandes

![Administration des commandes](docs/screenshots/admin-orders.png)

### Administration des utilisateurs

![Administration des utilisateurs](docs/screenshots/admin-users.png)

---

## Axes d’amélioration

Plusieurs évolutions peuvent être envisagées :

- paiement en ligne réel
- gestion de stock
- tableau de bord de statistiques en administration
- amélioration de l’accessibilité
- tests automatisés
- amélioration du design responsive
- envoi d’emails transactionnels supplémentaires

---

## Auteur

Projet réalisé par **O. Chahinian** dans le cadre de la formation **TP DWWM**.
