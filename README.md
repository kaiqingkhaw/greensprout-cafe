# GreenSprout Café

A café ordering application built with **PHP, MySQL and vanilla JavaScript**. Originally a university project, updated with a responsive customer storefront and an administrator workspace.

## Features

**Customers:** create an account, browse and filter dishes, add special requests, place demo orders, retrieve invoices and check order status.

**Administrators:** manage products, customers, orders and reviews; view date-filtered analytics; export records as CSV.

- Server-calculated prices, saved order items and duplicate-request protection.
- Role-checked sessions, password hashing, prepared statements and CSRF protection.
- Validated profile image uploads and editable account details.
- Keyboard-accessible controls, reduced-motion support and a remembered video pause preference.
- Local starter food photos, with an illustration if a custom image fails.

## Screenshots

### Sign in

![Sign-in screen](docs/screenshots/login.png)

### Customer menu

![Menu and category filters](docs/screenshots/menu.png)

### Admin workspace

![Administrator dashboard](docs/screenshots/admin.png)

### About

![About GreenSprout](docs/screenshots/about.png)

[Mobile product management](docs/screenshots/admin-mobile.png)

## Run locally

Requires PHP 8.1+, MySQL or compatible MariaDB, and the `mysqli`, `mbstring` and `fileinfo` PHP extensions. Tested with XAMPP PHP 8.2.4. Node.js 22+ is needed only for the checks.

1. Copy this folder to `C:/xampp/htdocs/GreenSproutCafe-Portfolio`.
2. Start **Apache** and **MySQL** in XAMPP.
3. For a new installation, import `database/schema.sql` using phpMyAdmin.
4. For an existing database, back it up and run `C:/xampp/php/php.exe scripts/migrate.php`. Do not re-import the starter schema over existing data.
5. Open [the local site](http://localhost/GreenSproutCafe-Portfolio/).
6. Use Sign up for a customer account. Create an admin from the project directory:

```powershell
C:/xampp/php/php.exe scripts/create_admin.php your_admin admin@example.com "YOUR_UNIQUE_PASSWORD"
```

Use at least 12 characters for the admin password. No account passwords are bundled.

Database settings use server environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASSWORD`. `.env.example` lists the keys; the app does not automatically load `.env` files. Defaults target local XAMPP.

## Project structure

| Location | Purpose |
| --- | --- |
| `MainMenu.html`, `menu.html`, `About.us.html` | Customer storefront |
| `login.php`, `signup.php`, `profile.php` | Accounts and profile |
| `admin_*.php` | Admin pages and actions |
| `create_order.php`, `order-data.php`, `get_*order*.php` | Checkout, order records and invoices |
| `config.php` | Database connection, sessions and shared validation |
| `assets/` | Styles, JavaScript, photos and local icons |
| `database/`, `scripts/` | Database setup, migration and admin creation |
| `tests/` | Regression checks and verification reports |

## Checks

Frontend checks need no package installation:

```sh
npm test
php tests/product-media.php
```

The full localhost suite creates and removes its own temporary records:

```powershell
$env:ALLOW_TEST_WRITES = "1"
npm run test:http
```

Latest results: **106 HTTP checks**, **25 desktop/mobile page checks**, and a complete customer-to-admin order journey. See the [verification report](tests/QA-2026-09-18.md) for coverage and limits.

GitHub Actions runs frontend checks, PHP syntax checks and the bundled-photo test on pushes and pull requests.

## Demo scope

- Card and wallet payments are simulated. No real payment details or money are collected.
- Order tracking shows saved café status; it is not live GPS.
- Contact messages are stored in MySQL. Email delivery and password-reset email are not configured.
- Cart drafts are stored in the current browser tab. Orders and invoices are saved in the database.
- Profile uploads accept JPG/PNG/GIF up to 2 MB and 6000 pixels per side. Previous photos are retained when replaced.

A live deployment needs PHP and MySQL; GitHub Pages alone cannot run this backend. Configure HTTPS, restricted database credentials and equivalent upload protection when using a server other than Apache. Keep local credentials, customer uploads and database dumps out of the repository.

## Media credits

Food photographs are from Unsplash; [file sources and license](assets/images/CREDITS.md) are included. Font Awesome Free 6.4.0 is bundled with its [upstream license](assets/vendor/fontawesome/LICENSE.txt). Original café videos and the logo are retained from the university project.
