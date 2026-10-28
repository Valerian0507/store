# Outils piscine et plannings de démonstration

- `/outils/calculateur-volume` : rectangle, cercle (diamètre) et ellipse. Dimensions intérieures en mètres et profondeur moyenne réelle d’eau. Résultat en m³ et litres. Le budget utilise exclusivement le tarif d’eau, le montant mensuel et le nombre de mois saisis.
- `/outils/analyse-eau` : saisie locale du pH, du chlore libre et du TAC. Les valeurs ne sont pas envoyées au serveur. Pas de dosage automatique, de confirmation de baignade ni de diagnostic à partir d’une photo. Le TAC est comparé uniquement à une plage saisie depuis la notice.
- `/profile/abonnements` : accès authentifié aux plannings de démonstration. Création depuis un consommable, quantité 1–20, intervalle 4/6/8/12 semaines, modification, pause, reprise et annulation. La prochaine échéance est indicative : aucun paiement, commande, envoi ou rappel automatique n’est déclenché.

Les cartes des outils figurent entre « Bien choisir » et « Conseils & inspirations ». Les liens sont également présents dans les guides et les catégories/produits concernés. Dans l’administration produit, la case « Consommable » contrôle l’affichage du formulaire de planning. La migration active cette case pour les six consommables de référence 10, 16, 22, 28, 34 et 39 du catalogue pédagogique.

## Installation

La migration `DoctrineMigrations\Version20261028120000` ajoute `products.replenishable` et la table `care_plans`. Elle a été appliquée à la base locale de ce projet. Pour une autre installation, utiliser la procédure habituelle de migrations Doctrine après examen des migrations en attente.

Les contrôleurs Stimulus sont découverts par l’AssetMapper existant. Aucun nouveau paquet JavaScript n’est nécessaire.

## Validation

```powershell
node --test tests/JavaScript/pool_tools.test.mjs
php bin/phpunit tests/Entity/CarePlanTest.php
php bin/console lint:twig templates/tools templates/profile/care_plans.html.twig
php bin/console lint:container
php tests/Smoke/pool_tools_smoke.php
```

Le dernier script est destiné exclusivement à l’environnement local `dev`. Il utilise de vrais appels HTTP au noyau et des utilisateurs temporaires à l’intérieur d’une transaction, avec rollback dans `finally`. Il vérifie accès anonyme, création, validation, doublons, CSRF, propriété, pause/reprise/annulation et absence de commande. Il ne requiert pas de modifier les droits de la base de test. Les tests unitaires PHP restent utilisables indépendamment de la base de données.

## Sources des repères de l’eau

- [CDC — Home Pool and Hot Tub Water Treatment and Testing](https://www.cdc.gov/healthy-swimming/about/home-pool-and-hot-tub-water-treatment-and-testing.html) : pH et minima de chlore selon bassin et stabilisant ; particularité des mesures supérieures à 10 ppm.
- [CDC — Guidelines for Keeping Your Pool Safe and Healthy](https://www.cdc.gov/healthy-swimming/safety/what-you-can-do-to-stay-healthy-in-swimming-pools.html) : plage usuelle de chlore en piscine. Les instructions propres au bassin et au produit restent nécessaires.
