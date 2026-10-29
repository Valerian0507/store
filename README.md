# AquaSphère

E-commerce demo shop (pools, spas and pool care) built with Symfony for the **Développeur Web et Web Mobile (TP DWWM)** certification.

The site itself is in French (it targets French customers); the code, comments and documentation are in English.

> Educational project: no real sale is made. Payments go through Stripe in **test mode**.

## Features

### Public site

- home page: product universes, spa banner, advice articles, pool tools, newsletter sign-up
- catalogue with search, sorting, category filter and pagination
- product page (stock status, specifications, add to cart without page reload)
- pool tools: pool volume calculator and water test comparison (`/outils/...`, see [docs/pool-tools.md](docs/pool-tools.md))
- advice articles
- newsletter: sign-up with welcome email, one-click unsubscribe link (confirmed by a POST)
- legal pages (legal notice, terms of sale, privacy policy) with a scroll-spy table of contents
- custom error pages (404, 500…); 5xx pages render without any database query

### Authentication and security

- registration (first and last name) with email verification and a welcome email once verified
- login / logout, login blocked until the email address is verified
- password reset by email
- shared password rule: 8+ characters, an uppercase letter, a digit and a special character (`StrongPassword`), shown live in the forms
- CSRF protection on every state-changing action
- server-side validation (`Assert` constraints on entities and forms)
- roles `ROLE_USER` and `ROLE_ADMIN`

### Customer account

- dashboard, profile and password change
- order history and order detail (cancel a pending order)
- address book with a default address
- newsletter switch
- demo replenishment plans for consumables (no payment or delivery is triggered)

### Cart and checkout

- session cart for visitors, persistent cart for logged-in users, merged at login
- stock checks when adding, updating (quantity capped at the stock) and ordering
- delivery step with a saved or a new address
- order creation with stock decrement, then payment with Stripe Checkout (test mode)
- order status `pending` → `paid` on return from Stripe, confirmation email sent once
- confirmation page built from ViewModels

### Administration

- dashboard
- products: search, category filter, image upload, stock replenishment
- categories
- orders: search, status filter, pagination, status update
- users: search, verification filter, detail, role change

## Tech stack

- PHP 8.2+, Symfony 7.4, Doctrine ORM and Migrations, MySQL 8
- Twig, AssetMapper (no Node build), Stimulus and Turbo (Symfony UX), Bootstrap 5
- Stripe Checkout (test mode)
- Symfony Mailer (Mailpit in development)
- PHPUnit, PHPStan (level 6), Node test runner for the pool tools JavaScript
- Docker Compose (MySQL, phpMyAdmin, Mailpit)

## Architecture

- `Controller -> Repository / Service -> Twig`
- business logic in services (`CartManager`, `CheckoutService`, `StripeCheckoutService`, `FileUploader`)
- ViewModels for the checkout summary and the confirmation page
- ownership checks on customer data (orders, addresses)
- `UserChecker` blocks unverified accounts; a login subscriber merges the session cart
- dedicated exception for stock errors (`InsufficientStockException`)
- Stimulus controllers for the interactive parts (address choice, add to cart, quantity, password rules, pool tools, table of contents)
- shared email layout (`templates/email/_layout.html.twig`): table-based HTML with inline styles and PNG icons, for email clients

## Requirements

- PHP 8.2 or higher, Composer
- Docker and Docker Compose
- Symfony CLI (optional, to run the dev server)
- Node.js 18+ (optional, only for the JavaScript tests)

## Installation

### 1. Clone the project

```bash
git clone https://github.com/Valerian0507/store.git
cd store
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Start the Docker services

```bash
docker compose up -d
```

Services from `compose.yaml`:

- MySQL on `127.0.0.1:3307` (user `store_user`, database `store_db`)
- phpMyAdmin on `http://127.0.0.1:8080`
- Mailpit on `http://127.0.0.1:8025` (every email sent in development lands here)

### 4. Configure the local environment

Create a `.env.local` file (never committed):

```env
DATABASE_URL="mysql://store_user:your_password@127.0.0.1:3307/store_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://localhost:1025
STRIPE_SECRET_KEY=sk_test_your_stripe_test_key
```

The Stripe test key is free from the Stripe dashboard (test mode): <https://dashboard.stripe.com/test/apikeys>.

### 5. Create the database and run the migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Load the demo users

```bash
php bin/console doctrine:fixtures:load
```

### 7. Import the catalogue

```bash
php bin/console app:import-products
```

The command reads `data/catalogue.json` and copies the product photos from `data/products/` to `public/uploads/products/`.
Running it again updates the products by reference (title, price, description, category) but keeps the stock and any photo uploaded from the admin.

Another file can be given as argument: `php bin/console app:import-products path/to/file.json`.

### 8. Start the application

```bash
symfony server:start
```

The site is available at <http://127.0.0.1:8000>.

## Demo accounts

After loading the fixtures:

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@store.com` | `password` |
| Customer | `user@store.com` | `password` |

Stripe test card: `4242 4242 4242 4242`, any future expiry date, any CVC.

## Product photos

- photos uploaded from the admin are stored in `public/uploads/products/` as `<reference>-<random>.<ext>` (this folder is not versioned)
- replacing or deleting a photo, or deleting the product, removes the file, **unless a past order still shows it** (order history and confirmation emails keep the image name)
- the versioned catalogue photos live in `data/products/` and are copied by `app:import-products`

## Tests and code quality

### PHPUnit

The tests use a dedicated database, `store_db_test`, which is **emptied before each functional test**. One-time setup (the MySQL user needs rights on the test database):

```bash
docker exec store-database sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS store_db_test; GRANT ALL PRIVILEGES ON store_db_test.* TO \"store_user\"@\"%\";"'
php bin/console --env=test doctrine:migrations:migrate -n
```

Run the suite:

```bash
php bin/phpunit
```

Covered: entities, access control, password rule, cart (JSON add, stock limits, quantity capped at the stock), newsletter (sign-up, duplicates, unsubscribe, account switch), admin (403 for customers, list filters, photo deletion), Stripe return and confirmation email (Stripe replaced by a fake service), legal and error pages, catalogue import, password reset.

### JavaScript (pool tools)

```bash
node --test tests/JavaScript/pool_tools.test.mjs
```

### PHPStan

```bash
php -d memory_limit=512M vendor/bin/phpstan analyse
```

The code passes **PHPStan level 6** with no error.

## Main entities

`User`, `Address`, `Product`, `Category`, `Cart`, `CartItem`, `Order`, `OrderItem`, `NewsletterSubscriber`, `CarePlan`, `ResetPasswordRequest`

## Screenshots

### Home page

Header and product universes:

![Home page - header and universes](docs/screenshots/home-hero.png)

Spa banner, product selection and advice:

![Home page - spa, selection and advice](docs/screenshots/home-sections.png)

Pool tools, newsletter and footer:

![Home page - tools, newsletter and footer](docs/screenshots/home-tools-newsletter.png)

### Pool tools

Pool volume calculator:

![Pool volume calculator](docs/screenshots/tool-pool-volume.png)

Water test comparison:

![Water test comparison](docs/screenshots/tool-water-analysis.png)

### Catalogue

![Catalogue](docs/screenshots/catalogue.png)

### Cart

![Cart](docs/screenshots/cart.png)

### Checkout

![Checkout - delivery address](docs/screenshots/checkout.png)

### Customer account

![Customer account](docs/screenshots/account.png)

Profile and password:

![Profile](docs/screenshots/account-profile.png)

Order history:

![Orders](docs/screenshots/account-orders.png)

### Administration

Dashboard:

![Admin - dashboard](docs/screenshots/admin-dashboard.png)

Products:

![Admin - products](docs/screenshots/admin-products.png)

Categories:

![Admin - categories](docs/screenshots/admin-categories.png)

## Known limitations

- Stripe in test mode only; the payment is confirmed on the return URL (no Stripe webhook yet)
- emails are sent synchronously (no Messenger worker needed)
- free delivery, no shipping cost calculation
- the hosting section of the legal notice must be filled in at deployment

## Author

Project by **O. Chahinian** for the **TP DWWM** training.
