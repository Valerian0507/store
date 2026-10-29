# Pool tools and demo replenishment plans

- `/outils/calculateur-volume`: rectangle, circle (diameter) and ellipse. Inner dimensions in metres and real average water depth. Result in m³ and litres. The budget only uses the water price, the monthly amount and the number of months entered.
- `/outils/analyse-eau`: local input of pH, free chlorine and total alkalinity (TAC). The values are not sent to the server. No automatic dosing, no "safe to swim" confirmation and no diagnosis from a photo. TAC is only compared to a range typed from the product manual.
- `/profile/abonnements`: logged-in access to the demo replenishment plans. Created from a consumable, quantity 1–20, interval 4/6/8/12 weeks, edit, pause, resume and cancel. The next date is only indicative: no payment, order, shipment or reminder is ever triggered.

The tool cards sit between "Bien choisir" and "Conseils & inspirations" on the home page. Links are also shown in the advice guides and on the related categories and products. In the product admin, the "Consommable" checkbox controls whether the plan form is displayed. The migration ticks it for the six consumables of the educational catalogue (references 10, 16, 22, 28, 34 and 39).

## Installation

Migration `DoctrineMigrations\Version20261028120000` adds `products.replenishable` and the `care_plans` table. For another installation, run the usual Doctrine migrations after reviewing the pending ones.

The Stimulus controllers are discovered by the existing AssetMapper setup. No new JavaScript package is needed.

## Checks

```powershell
node --test tests/JavaScript/pool_tools.test.mjs
php bin/phpunit tests/Entity/CarePlanTest.php
php bin/console lint:twig templates/tools templates/profile/care_plans.html.twig
php bin/console lint:container
php tests/Smoke/pool_tools_smoke.php
```

The last script is for the local `dev` environment only. It sends real HTTP requests to the kernel and uses temporary users inside a transaction, rolled back in `finally`. It checks anonymous access, creation, validation, duplicates, CSRF, ownership, pause/resume/cancel and that no order is created. It does not need any rights on the test database. The PHP unit tests can be run without a database.

## Sources for the water reference values

- [CDC — Home Pool and Hot Tub Water Treatment and Testing](https://www.cdc.gov/healthy-swimming/about/home-pool-and-hot-tub-water-treatment-and-testing.html): pH and minimum chlorine depending on the pool type and the stabilizer; special case of readings above 10 ppm.
- [CDC — Guidelines for Keeping Your Pool Safe and Healthy](https://www.cdc.gov/healthy-swimming/safety/what-you-can-do-to-stay-healthy-in-swimming-pools.html): usual chlorine range in pools. The pool's and the product's own instructions are still required.
